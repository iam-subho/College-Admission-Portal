<?php

namespace Modules\Notifications\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Admissions\Models\WithdrawalRequest;

class WithdrawalApprovedEvent implements NotifiableEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public WithdrawalRequest $request) {}

    public function eventKey(): string
    {
        return 'withdrawal_approved';
    }

    public function recipient(): ?User
    {
        return $this->request->application?->student?->user;
    }

    public function variables(): array
    {
        $r = $this->request;

        return [
            'name' => $r->application?->student?->user?->name ?? 'Applicant',
            'application_number' => $r->application?->application_number,
            'estimated_refund' => $r->estimated_refund !== null ? '₹'.number_format((float) $r->estimated_refund, 2) : '—',
            'slab' => $r->estimated_slab ?? '—',
            'decided_at' => optional($r->decided_at)->format('d M Y, h:i A'),
        ];
    }
}
