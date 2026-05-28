<?php

namespace Modules\Notifications\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Payments\Models\Refund;

class RefundCompletedEvent implements NotifiableEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public Refund $refund) {}

    public function eventKey(): string
    {
        return 'refund_completed';
    }

    public function recipient(): ?User
    {
        return $this->refund->application?->student?->user;
    }

    public function variables(): array
    {
        $r = $this->refund;

        return [
            'name' => $r->application?->student?->user?->name ?? 'Applicant',
            'application_number' => $r->application?->application_number,
            'amount' => '₹'.number_format((float) $r->amount, 2),
            'deduction' => '₹'.number_format((float) $r->deduction_amount, 2),
            'offline_reference' => $r->offline_reference,
            'completed_at' => optional($r->completed_at)->format('d M Y, h:i A'),
        ];
    }
}
