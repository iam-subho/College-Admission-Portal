<?php

namespace Modules\Notifications\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Payments\Models\PaymentOrder;

class PaymentReceivedEvent implements NotifiableEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public PaymentOrder $order) {}

    public function eventKey(): string
    {
        return $this->order->purpose === 'admission_fee'
            ? 'admission_fee_received'
            : 'application_fee_received';
    }

    public function recipient(): ?User
    {
        return $this->order->application?->student?->user;
    }

    public function variables(): array
    {
        $o = $this->order;

        return [
            'name' => $o->application?->student?->user?->name ?? 'Applicant',
            'application_number' => $o->application?->application_number,
            'order_number' => $o->order_number,
            'amount' => '₹'.number_format((float) $o->total, 2),
            'paid_at' => optional($o->paid_at)->format('d M Y, h:i A'),
            'purpose' => str_replace('_', ' ', (string) $o->purpose),
        ];
    }
}
