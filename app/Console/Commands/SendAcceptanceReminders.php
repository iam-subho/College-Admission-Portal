<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Modules\Notifications\Events\AcceptanceReminderEvent;
use Modules\Seats\Models\SeatAllocation;

/**
 * Daily cron: fires AcceptanceReminderEvent for allotments where the
 * acceptance window expires within the next 36 hours and the student
 * has not yet responded.
 */
#[Signature('seats:send-acceptance-reminders')]
#[Description('Send a reminder to students whose acceptance window expires within 36 hours.')]
class SendAcceptanceReminders extends Command
{
    public function handle(): int
    {
        $window = SeatAllocation::query()
            ->where('status', SeatAllocation::STATUS_ALLOTTED)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addHours(36)])
            ->get();

        $sent = 0;
        foreach ($window as $alloc) {
            event(new AcceptanceReminderEvent($alloc));
            $sent++;
        }

        $this->info("Acceptance-reminder events fired for {$sent} allotment(s).");

        return self::SUCCESS;
    }
}
