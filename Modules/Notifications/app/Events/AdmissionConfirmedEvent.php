<?php

namespace Modules\Notifications\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Seats\Models\SeatAllocation;

class AdmissionConfirmedEvent implements NotifiableEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public SeatAllocation $allocation) {}

    public function eventKey(): string
    {
        return 'admission_confirmed';
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
            'programme' => $a->round?->program?->code.' — '.$a->round?->program?->name,
            'session' => $a->round?->session?->code,
            'admitted_at' => optional($a->admitted_at)->format('d M Y, h:i A'),
        ];
    }
}
