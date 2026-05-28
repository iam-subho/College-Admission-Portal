<?php

namespace Modules\Seats\Services;

use Illuminate\Support\Facades\DB;
use Modules\Admissions\Models\AdmissionRound;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ProgramReservation;
use Modules\Merit\Models\MeritList;
use Modules\Merit\Models\MeritListEntry;
use Modules\Seats\Models\SeatAcceptance;
use Modules\Seats\Models\SeatAllocation;

/**
 * Generates initial seat allotments from a published merit list. For each
 * reservation category (vertical), takes the top N=seats_available qualifying
 * candidates and creates seat_allocations with status='allotted'.
 *
 * Re-running on a round is idempotent — only allots seats for applications
 * that don't already have an allocation in this round.
 */
class SeatAllocator
{
    /**
     * @return array{allotted: int, skipped: int}
     */
    public function generate(AdmissionRound $round): array
    {
        $meritList = MeritList::where('admission_round_id', $round->id)
            ->where('status', MeritList::STATUS_PUBLISHED)
            ->first();

        abort_unless($meritList, 422, 'Merit list for this round is not published yet.');
        abort_if($round->allotment_locked_at !== null, 422, 'Allotment for this round is already locked.');

        return DB::transaction(function () use ($round, $meritList) {
            $allotted = 0;
            $skipped = 0;

            // Already-allocated applications in this round (idempotency guard).
            $existingAppIds = SeatAllocation::where('admission_round_id', $round->id)
                ->pluck('application_id')->all();

            $reservations = ProgramReservation::query()
                ->where('program_id', $round->program_id)
                ->where('academic_session_id', $round->academic_session_id)
                ->with('category')
                ->get();

            $windowDays = max(1, (int) $round->acceptance_window_days);
            $expiresAt = now()->addDays($windowDays);

            foreach ($reservations as $reservation) {
                if ($reservation->category?->is_horizontal) {
                    // Horizontal categories (PwD, Sports etc.) overlay across
                    // verticals — not allocated as separate buckets here.
                    continue;
                }

                $seats = (int) $reservation->seats;
                if ($seats <= 0) {
                    continue;
                }

                $entries = MeritListEntry::query()
                    ->where('merit_list_id', $meritList->id)
                    ->where('reservation_category_id', $reservation->reservation_category_id)
                    ->where('is_qualifying', true)
                    ->where('is_absent', false)
                    ->whereNotIn('application_id', $existingAppIds)
                    ->orderBy('category_rank')
                    ->limit($seats)
                    ->get();

                foreach ($entries as $entry) {
                    $alloc = SeatAllocation::create([
                        'admission_round_id' => $round->id,
                        'application_id' => $entry->application_id,
                        'reservation_category_id' => $entry->reservation_category_id,
                        'status' => SeatAllocation::STATUS_ALLOTTED,
                        'source' => SeatAllocation::SOURCE_MERIT,
                        'rank_at_allotment' => $entry->overall_rank,
                        'category_rank_at_allotment' => $entry->category_rank,
                        'allotted_at' => now(),
                        'expires_at' => $expiresAt,
                    ]);
                    event(new \Modules\Notifications\Events\SeatAllottedEvent($alloc));
                    $allotted++;
                    $existingAppIds[] = $entry->application_id;
                }
            }

            $round->forceFill(['allotment_generated_at' => now()])->save();

            return ['allotted' => $allotted, 'skipped' => $skipped];
        });
    }

    /**
     * Promote the next eligible waitlisted candidate for the category vacated
     * by a declined/expired allotment. Returns the new SeatAllocation or null
     * if no candidate is available.
     */
    public function promoteNext(SeatAllocation $vacated, ?int $byUserId = null): ?SeatAllocation
    {
        return DB::transaction(function () use ($vacated, $byUserId) {
            $round = $vacated->round ?? $vacated->load('round')->round;
            $meritList = MeritList::where('admission_round_id', $round->id)
                ->where('status', MeritList::STATUS_PUBLISHED)
                ->first();

            if (! $meritList) {
                return null;
            }

            // Find application IDs that are NOT already in any allocation row for this round.
            $allocatedAppIds = SeatAllocation::where('admission_round_id', $round->id)
                ->pluck('application_id')->all();

            $nextEntry = MeritListEntry::query()
                ->where('merit_list_id', $meritList->id)
                ->where('reservation_category_id', $vacated->reservation_category_id)
                ->where('is_qualifying', true)
                ->where('is_absent', false)
                ->whereNotIn('application_id', $allocatedAppIds)
                ->orderBy('category_rank')
                ->first();

            if (! $nextEntry) {
                return null;
            }

            $windowDays = max(1, (int) $round->acceptance_window_days);

            $newAlloc = SeatAllocation::create([
                'admission_round_id' => $round->id,
                'application_id' => $nextEntry->application_id,
                'reservation_category_id' => $nextEntry->reservation_category_id,
                'status' => SeatAllocation::STATUS_ALLOTTED,
                'source' => SeatAllocation::SOURCE_MERIT,
                'rank_at_allotment' => $nextEntry->overall_rank,
                'category_rank_at_allotment' => $nextEntry->category_rank,
                'allotted_at' => now(),
                'expires_at' => now()->addDays($windowDays),
                'audit_remark' => "Promoted from waitlist after allocation #{$vacated->id} {$vacated->status}.",
            ]);

            event(new \Modules\Notifications\Events\SeatAllottedEvent($newAlloc));

            return $newAlloc;
        });
    }

    /**
     * Record an accept/decline/expire/withdraw action and (if a vacancy was
     * created) promote the next waitlisted candidate.
     */
    public function recordAction(
        SeatAllocation $allocation,
        string $action,
        ?int $byUserId,
        ?string $reason = null,
    ): SeatAcceptance {
        $now = now();

        $log = SeatAcceptance::create([
            'seat_allocation_id' => $allocation->id,
            'application_id' => $allocation->application_id,
            'admission_round_id' => $allocation->admission_round_id,
            'action' => $action,
            'reason' => $reason,
            'decided_by_user_id' => $byUserId,
            'decided_at' => $now,
        ]);

        $stateMap = [
            SeatAcceptance::ACTION_ACCEPT => SeatAllocation::STATUS_ACCEPTED,
            SeatAcceptance::ACTION_DECLINE => SeatAllocation::STATUS_DECLINED,
            SeatAcceptance::ACTION_EXPIRE => SeatAllocation::STATUS_EXPIRED,
            SeatAcceptance::ACTION_WITHDRAW => SeatAllocation::STATUS_WITHDRAWN,
        ];

        if (isset($stateMap[$action])) {
            $allocation->forceFill([
                'status' => $stateMap[$action],
                'decided_at' => $now,
                'audit_remark' => $reason,
            ])->save();
        }

        // Trigger rollover when a vacancy is created.
        if (in_array($action, [
            SeatAcceptance::ACTION_DECLINE,
            SeatAcceptance::ACTION_EXPIRE,
            SeatAcceptance::ACTION_WITHDRAW,
        ], true)) {
            $this->promoteNext($allocation, $byUserId);
        }

        return $log;
    }

    /**
     * Direct admin allotment for a walk-in / spot admission. Skips merit-list
     * mediation — the admin chooses any submitted+paid application.
     */
    public function spotAllot(
        AdmissionRound $round,
        Application $application,
        ?int $reservationCategoryId,
        int $adminId,
        ?string $remark = null,
    ): SeatAllocation {
        return DB::transaction(function () use ($round, $application, $reservationCategoryId, $adminId, $remark) {
            // Block dupes: if this application already has any allocation in this round, abort.
            abort_if(
                SeatAllocation::where('admission_round_id', $round->id)->where('application_id', $application->id)->exists(),
                422,
                'This application already has an allocation in this round.',
            );

            $alloc = SeatAllocation::create([
                'admission_round_id' => $round->id,
                'application_id' => $application->id,
                'reservation_category_id' => $reservationCategoryId,
                'status' => SeatAllocation::STATUS_ALLOTTED,
                'source' => SeatAllocation::SOURCE_SPOT,
                'allotted_at' => now(),
                'expires_at' => now()->addDays(max(1, (int) $round->acceptance_window_days)),
                'admitted_by_admin_id' => $adminId,
                'audit_remark' => $remark ?? 'Spot / walk-in admission.',
            ]);

            SeatAcceptance::create([
                'seat_allocation_id' => $alloc->id,
                'application_id' => $application->id,
                'admission_round_id' => $round->id,
                'action' => SeatAcceptance::ACTION_SPOT_ALLOT,
                'reason' => $remark,
                'decided_by_user_id' => $adminId,
                'decided_at' => now(),
            ]);

            event(new \Modules\Notifications\Events\SeatAllottedEvent($alloc));

            return $alloc;
        });
    }
}
