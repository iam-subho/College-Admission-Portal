<?php

namespace Modules\Notifications\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Admissions\Models\Application;

class ApplicationSubmittedEvent implements NotifiableEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public Application $application) {}

    public function eventKey(): string
    {
        return 'application_submitted';
    }

    public function recipient(): ?User
    {
        return $this->application->student?->user;
    }

    public function variables(): array
    {
        $a = $this->application;
        $student = $a->student;

        return [
            'name' => $student?->user?->name ?? $student?->aadhaar_full_name ?? 'Applicant',
            'application_number' => $a->application_number,
            'programme' => $a->program?->code.' — '.$a->program?->name,
            'session' => $a->session?->code,
            'payment_status' => ucfirst((string) $a->payment_status),
            'submitted_at' => optional($a->submitted_at)->format('d M Y, h:i A'),
        ];
    }
}
