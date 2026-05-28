<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Modules\Seats\Models\SeatAcceptance;
use Modules\Seats\Models\SeatAllocation;
use Modules\Seats\Services\SeatAllocator;

/**
 * Daily-cron job. Finds allotments whose acceptance window has passed and
 * the student has not responded → marks them 'expired' and promotes the
 * next waitlisted candidate via SeatAllocator::recordAction.
 *
 * Skipped for locked rounds (admin manually called Lock Allotment).
 */
#[Signature('seats:rollover-expired')]
#[Description('Mark expired allotments as expired and promote next waitlisted candidates.')]
class RolloverExpiredSeats extends Command
{
    public function handle(SeatAllocator $allocator): int
    {
        $expired = SeatAllocation::query()
            ->where('status', SeatAllocation::STATUS_ALLOTTED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereHas('round', fn ($q) => $q->whereNull('allotment_locked_at'))
            ->get();

        $count = 0;
        foreach ($expired as $alloc) {
            $allocator->recordAction(
                $alloc,
                SeatAcceptance::ACTION_EXPIRE,
                null,
                'Acceptance window passed without student response.',
            );
            $count++;
        }

        $this->info("Rolled over {$count} expired allotment(s).");

        return self::SUCCESS;
    }
}
