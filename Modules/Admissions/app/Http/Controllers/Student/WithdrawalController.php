<?php

namespace Modules\Admissions\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\WithdrawalRequest;
use Modules\Payments\Services\RefundPolicyEngine;

class WithdrawalController extends Controller
{
    public function store(Request $request, Application $application, RefundPolicyEngine $engine): RedirectResponse
    {
        abort_unless(
            $application->student?->user_id === $request->user()->id,
            403,
            'Not your application.',
        );

        // Cannot withdraw a draft (just delete it) or an already-withdrawn app.
        if (! in_array($application->status, [
            Application::STATUS_SUBMITTED,
            Application::STATUS_VERIFIED,
        ], true)) {
            return back()->with('flash', ['error' => 'This application cannot be withdrawn in its current state.']);
        }

        if (WithdrawalRequest::where('application_id', $application->id)
            ->where('status', WithdrawalRequest::STATUS_PENDING)->exists()) {
            return back()->with('flash', ['error' => 'A withdrawal request is already pending review.']);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $preview = $engine->compute($application);

        WithdrawalRequest::create([
            'application_id' => $application->id,
            'requested_by' => $request->user()->id,
            'reason' => $data['reason'],
            'status' => WithdrawalRequest::STATUS_PENDING,
            'requested_at' => now(),
            'estimated_refund' => $preview['refund_amount'],
            'estimated_deduction' => $preview['deduction_amount'],
            'estimated_slab' => $preview['slab_label'],
        ]);

        return back()->with('flash', [
            'success' => 'Withdrawal request submitted. The admissions office will review and respond shortly.',
        ]);
    }
}
