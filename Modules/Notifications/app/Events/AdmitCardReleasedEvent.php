<?php

namespace Modules\Notifications\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Tests\Models\AdmissionTestCandidate;

class AdmitCardReleasedEvent implements NotifiableEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public AdmissionTestCandidate $candidate) {}

    public function eventKey(): string
    {
        return 'admit_card_released';
    }

    public function recipient(): ?User
    {
        return $this->candidate->application?->student?->user;
    }

    public function variables(): array
    {
        $c = $this->candidate;
        $schedule = $c->schedule;

        return [
            'name' => $c->application?->student?->user?->name ?? 'Applicant',
            'roll_number' => $c->roll_number,
            'application_number' => $c->application?->application_number,
            'programme' => $c->application?->program?->code,
            'test_date' => optional($schedule?->test_date)->format('d M Y'),
            'venue' => $schedule?->venue,
            'reporting_time' => $schedule?->reporting_time,
        ];
    }
}
