<?php

namespace Modules\Notifications\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Admissions\Models\Application;

class EligibilityOverriddenEvent implements NotifiableEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public Application $application) {}

    public function eventKey(): string
    {
        return 'eligibility_overridden';
    }

    public function recipient(): ?User
    {
        return $this->application->student?->user;
    }

    public function variables(): array
    {
        return [
            'name' => $this->application->student?->user?->name ?? 'Applicant',
            'application_number' => $this->application->application_number,
            'verdict' => strtoupper(str_replace('_', ' ', (string) $this->application->eligibility_verdict)),
            'remark' => $this->application->eligibility_remark,
        ];
    }
}
