<?php

namespace Modules\Payments\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Payments\Models\Refund;

class RefundsController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->input('status', 'pending');
        $allowed = [
            Refund::STATUS_PENDING,
            Refund::STATUS_COMPLETED,
            Refund::STATUS_FAILED,
            'all',
        ];
        if (! in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $query = Refund::query()
            ->with([
                'application:id,application_number,student_id,program_id',
                'application.student.user:id,name,email,mobile',
                'application.program:id,code,name',
                'order:id,order_number,total,currency',
                'withdrawalRequest:id,reason,status',
                'approvedBy:id,name',
            ])
            ->orderByDesc('id');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return Inertia::render('Admin/Refunds', [
            'refunds' => $query->paginate(25)->withQueryString(),
            'filter' => ['status' => $status],
            'counts' => [
                'pending' => Refund::where('status', Refund::STATUS_PENDING)->count(),
                'completed' => Refund::where('status', Refund::STATUS_COMPLETED)->count(),
                'failed' => Refund::where('status', Refund::STATUS_FAILED)->count(),
                'all' => Refund::count(),
            ],
        ]);
    }

    public function markPaid(Request $request, Refund $refund): RedirectResponse
    {
        if ($refund->isCompleted()) {
            return back()->with('flash', ['error' => 'This refund is already completed.']);
        }

        $data = $request->validate([
            'offline_reference' => ['required', 'string', 'max:80'],
        ]);

        $refund->forceFill([
            'status' => Refund::STATUS_COMPLETED,
            'refund_method' => Refund::METHOD_OFFLINE,
            'offline_reference' => $data['offline_reference'],
            'completed_at' => now(),
        ])->save();

        event(new \Modules\Notifications\Events\RefundCompletedEvent($refund->fresh()));

        return back()->with('flash', ['success' => 'Refund marked as paid offline.']);
    }

    public function markFailed(Request $request, Refund $refund): RedirectResponse
    {
        if ($refund->isCompleted()) {
            return back()->with('flash', ['error' => 'This refund is already completed; cannot mark as failed.']);
        }

        $data = $request->validate([
            'failure_reason' => ['required', 'string', 'max:500'],
        ]);

        $refund->forceFill([
            'status' => Refund::STATUS_FAILED,
            'reason' => ($refund->reason ?? '').' | FAILED: '.$data['failure_reason'],
        ])->save();

        return back()->with('flash', ['success' => 'Refund marked as failed.']);
    }
}
