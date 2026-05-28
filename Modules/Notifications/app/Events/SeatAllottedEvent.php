<?php

namespace Modules\Notifications\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Seats\Models\SeatAllocation;

class SeatAllottedEvent implements NotifiableEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public SeatAllocation $allocation) {}

    public function eventKey(): string
    {
        return 'seat_allotted';
    }

    public function recipient(): ?User
    {
        return $this->allocation->application?->student?->user;
    }

    public function variables(): array
    {
        $a = $this->allocation;

        return [
            'name' => $a->application?->student?->user?->name ?? 'Applicant',
            'application_number' => $a->application?->application_number,
            'programme' => $a->round?->program?->code,
            'category' => $a->category?->code ?? 'UR',
            'expires_at' => optional($a->expires_at)->format('d M Y, h:i A'),
            'window_days' => $a->round?->acceptance_window_days,
        ];
    }
}
