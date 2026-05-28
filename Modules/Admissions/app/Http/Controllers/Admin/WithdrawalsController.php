<?php

namespace Modules\Admissions\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\WithdrawalRequest;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\Refund;
use Modules\Payments\Services\RefundPolicyEngine;

class WithdrawalsController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->input('status', 'pending');
        $allowed = [
            WithdrawalRequest::STATUS_PENDING,
            WithdrawalRequest::STATUS_APPROVED,
            WithdrawalRequest::STATUS_REJECTED,
            'all',
        ];
        if (! in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $query = WithdrawalRequest::query()
            ->with([
                'application:id,application_number,program_id,academic_session_id,student_id,status,payment_status',
                'application.program:id,code,name',
                'application.session:id,code',
                'application.student.user:id,name,email,mobile',
                'requester:id,name',
                'decider:id,name',
            ])
            ->orderByDesc('id');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return Inertia::render('Admin/Withdrawals', [
            'requests' => $query->paginate(25)->withQueryString(),
            'filter' => ['status' => $status],
            'counts' => [
                'pending' => WithdrawalRequest::where('status', WithdrawalRequest::STATUS_PENDING)->count(),
                'approved' => WithdrawalRequest::where('status', WithdrawalRequest::STATUS_APPROVED)->count(),
                'rejected' => WithdrawalRequest::where('status', WithdrawalRequest::STATUS_REJECTED)->count(),
                'all' => WithdrawalRequest::count(),
            ],
        ]);
    }

    public function approve(
        Request $request,
        WithdrawalRequest $withdrawal,
        RefundPolicyEngine $engine,
    ): RedirectResponse {
        if ($withdrawal->status !== WithdrawalRequest::STATUS_PENDING) {
            return back()->with('flash', ['error' => 'Only pending requests can be approved.']);
        }

        $data = $request->validate([
            'admin_remark' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $withdrawal, $engine, $data) {
            $application = $withdrawal->application;

            $preview = $engine->compute($application);

            // Flip application to withdrawn.
            $application->forceFill([
                'status' => Application::STATUS_WITHDRAWN,
                'status_changed_at' => now(),
            ])->save();

            // Mark the withdrawal as approved.
            $withdrawal->forceFill([
                'status' => WithdrawalRequest::STATUS_APPROVED,
                'decided_at' => now(),
                'decided_by' => $request->user()->id,
                'admin_remark' => $data['admin_remark'] ?? null,
            ])->save();

            // Create a Refund row if there is a paid order to refund.
            if ($preview['has_payment'] && $preview['refund_amount'] > 0) {
                $paidOrder = $application->paymentOrders()
                    ->where('status', PaymentOrder::STATUS_PAID)
                    ->latest('id')->first();
                $txn = $paidOrder?->transactions()->latest('id')->first();

                Refund::create([
                    'payment_order_id' => $paidOrder?->id,
                    'payment_transaction_id' => $txn?->id,
                    'application_id' => $application->id,
                    'withdrawal_request_id' => $withdrawal->id,
                    'amount' => $preview['refund_amount'],
                    'deduction_amount' => $preview['deduction_amount'],
                    'policy_slab' => $preview['slab_label'],
                    'status' => Refund::STATUS_PENDING,
                    'refund_method' => Refund::METHOD_OFFLINE,
                    'initiated_by' => $request->user()->id,
                    'approved_at' => now(),
                    'approved_by' => $request->user()->id,
                    'reason' => $withdrawal->reason,
                ]);
            }
        });

        event(new \Modules\Notifications\Events\WithdrawalApprovedEvent($withdrawal->fresh()));

        return back()->with('flash', ['success' => 'Withdrawal approved. Application is now marked withdrawn.']);
    }

    public function reject(Request $request, WithdrawalRequest $withdrawal): RedirectResponse
    {
        if ($withdrawal->status !== WithdrawalRequest::STATUS_PENDING) {
            return back()->with('flash', ['error' => 'Only pending requests can be rejected.']);
        }

        $data = $request->validate([
            'admin_remark' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $withdrawal->forceFill([
            'status' => WithdrawalRequest::STATUS_REJECTED,
            'decided_at' => now(),
            'decided_by' => $request->user()->id,
            'admin_remark' => $data['admin_remark'],
        ])->save();

        return back()->with('flash', ['success' => 'Withdrawal request rejected.']);
    }
}
