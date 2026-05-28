<?php

namespace Modules\Notifications\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Seats\Models\SeatAllocation;

/**
 * Fired by a daily cron job ~24h before acceptance window expiry for
 * allotments still in 'allotted' state.
 */
class AcceptanceReminderEvent implements NotifiableEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public SeatAllocation $allocation) {}

    public function eventKey(): string
    {
        return 'acceptance_reminder';
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
            'expires_at' => optional($a->expires_at)->format('d M Y, h:i A'),
        ];
    }
}
