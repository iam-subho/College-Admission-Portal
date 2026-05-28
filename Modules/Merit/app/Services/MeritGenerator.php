<?php

namespace Modules\Merit\Services;

use Illuminate\Support\Facades\DB;
use Modules\Admissions\Models\AdmissionRound;
use Modules\Admissions\Models\Application;
use Modules\Merit\Models\MeritCutoff;
use Modules\Merit\Models\MeritFormula;
use Modules\Merit\Models\MeritList;
use Modules\Merit\Models\MeritListEntry;
use Modules\Students\Models\StudentAcademicRecord;
use Modules\Tests\Models\AdmissionTestConfig;
use Modules\Tests\Models\AdmissionTestScore;

/**
 * Generates a merit list for an admission round.
 *
 * Inputs:
 *   - AdmissionRound (program + session)
 *
 * Outputs:
 *   - MeritList (status=draft) with one MeritListEntry per paid+submitted application
 *   - MeritCutoff rows per reservation category (auto-computed from program reservations)
 *
 * The formula and tie-breakers are pulled from the AdmissionTestConfig of the
 * round's (program, session). When no test config exists OR the test is disabled,
 * the merit is pure-marks (marks_weight=100, test_weight=0).
 */
class MeritGenerator
{
    /**
     * Tie-break ordering used unless the formula overrides it. Test marks have
     * priority, then DOB (older candidate wins).
     */
    public const DEFAULT_TIE_BREAKERS = [
        MeritFormula::TIE_BREAK_TEST_MARKS,
        MeritFormula::TIE_BREAK_DOB,
    ];

    public function generate(AdmissionRound $round, ?int $generatedBy = null): MeritList
    {
        return DB::transaction(function () use ($round, $generatedBy) {
            $existing = MeritList::where('admission_round_id', $round->id)->first();
            if ($existing && $existing->isPublished()) {
                abort(422, 'Merit list for this round is already published. Cannot regenerate.');
            }

            $formula = $this->resolveFormula($round);
            $candidates = $this->collectCandidates($round, $formula);
            $ranked = $this->rankCandidates($candidates, $formula);

            // Reuse the draft row if it exists, else create.
            $list = $existing ?: new MeritList([
                'admission_round_id' => $round->id,
            ]);
            $list->fill([
                'merit_formula_id' => null,
                'status' => MeritList::STATUS_DRAFT,
                'formula_snapshot' => $formula,
                'total_candidates' => count($ranked),
                'max_score' => $ranked[0]['total_score'] ?? null,
                'generated_at' => now(),
                'generated_by' => $generatedBy,
            ])->save();

            // Wipe old entries + cutoffs (re-generate is allowed for drafts).
            MeritListEntry::where('merit_list_id', $list->id)->delete();
            MeritCutoff::where('merit_list_id', $list->id)->delete();

            $this->persistEntries($list, $ranked);
            $this->computeCutoffs($list, $round);

            $round->forceFill(['status' => AdmissionRound::STATUS_MERIT_DRAFTED])->save();

            return $list->fresh(['entries', 'cutoffs']);
        });
    }

    public function publish(MeritList $list, int $publishedBy): MeritList
    {
        if ($list->isPublished()) {
            return $list;
        }

        return DB::transaction(function () use ($list, $publishedBy) {
            $list->forceFill([
                'status' => MeritList::STATUS_PUBLISHED,
                'published_at' => now(),
                'published_by' => $publishedBy,
            ])->save();

            // Lock all admission_test_scores for this round so marks become immutable.
            $round = $list->round()->with(['program:id', 'session:id'])->first();
            AdmissionTestScore::query()
                ->whereHas('candidate.schedule.config', fn ($q) => $q
                    ->where('program_id', $round->program_id)
                    ->where('academic_session_id', $round->academic_session_id)
                )
                ->update([
                    'is_locked' => true,
                    'locked_at' => now(),
                ]);

            $round->forceFill(['status' => AdmissionRound::STATUS_MERIT_PUBLISHED])->save();

            event(new \Modules\Merit\Events\MeritListPublished($list));

            // Per-candidate notification (rank + score).
            \Modules\Merit\Models\MeritListEntry::query()
                ->where('merit_list_id', $list->id)
                ->with('application.student.user', 'meritList.round.program')
                ->get()
                ->each(fn ($entry) => event(new \Modules\Notifications\Events\MeritPublishedNotificationEvent($entry)));

            return $list->fresh();
        });
    }

    /**
     * Resolve the formula for this round. Pulls from AdmissionTestConfig if a
     * row exists; otherwise falls back to pure-marks (100% board) with default
     * tie-breakers.
     */
    protected function resolveFormula(AdmissionRound $round): array
    {
        $config = AdmissionTestConfig::query()
            ->where('program_id', $round->program_id)
            ->where('academic_session_id', $round->academic_session_id)
            ->first();

        if ($config && $config->is_test_enabled) {
            return [
                'name' => 'test_weighted',
                'test_enabled' => true,
                'test_weight' => (float) $config->test_weight,
                'marks_weight' => (float) $config->marks_weight,
                'max_test_marks' => (float) ($config->max_marks ?? 100),
                'qualifying_marks' => $config->qualifying_marks !== null ? (float) $config->qualifying_marks : null,
                'tie_breakers' => self::DEFAULT_TIE_BREAKERS,
            ];
        }

        return [
            'name' => 'pure_marks',
            'test_enabled' => false,
            'test_weight' => 0.0,
            'marks_weight' => 100.0,
            'max_test_marks' => null,
            'qualifying_marks' => null,
            'tie_breakers' => [MeritFormula::TIE_BREAK_DOB],
        ];
    }

    /**
     * Build the raw candidate list with all scoring inputs.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function collectCandidates(AdmissionRound $round, array $formula): array
    {
        $apps = Application::query()
            ->where('program_id', $round->program_id)
            ->where('academic_session_id', $round->academic_session_id)
            ->where('status', Application::STATUS_SUBMITTED)
            ->whereIn('payment_status', [
                Application::PAYMENT_PAID,
                Application::PAYMENT_COVERED,
                Application::PAYMENT_NOT_REQUIRED,
            ])
            ->with([
                'student:id,user_id,reservation_category_id,dob',
                'student.academicRecords:id,student_id,level,percentage',
            ])
            ->get();

        $rows = [];
        foreach ($apps as $app) {
            $student = $app->student;
            $boardPct = $this->resolveBoardPct($student?->academicRecords?->all() ?? [], $round);

            $testScore = null;
            $testPct = null;
            $isAbsent = false;
            $isQualifying = true;

            if ($formula['test_enabled']) {
                $score = AdmissionTestScore::query()
                    ->whereHas('candidate', fn ($q) => $q->where('application_id', $app->id))
                    ->first();

                if ($score) {
                    if ($score->attendance === AdmissionTestScore::ATTENDANCE_ABSENT) {
                        $isAbsent = true;
                        $testScore = null;
                        $testPct = 0.0;
                    } else {
                        $testScore = (float) $score->raw_marks;
                        $testPct = $formula['max_test_marks'] > 0
                            ? ($testScore / $formula['max_test_marks']) * 100
                            : 0;
                    }
                    if ($formula['qualifying_marks'] !== null && $testScore !== null && $testScore < $formula['qualifying_marks']) {
                        $isQualifying = false;
                    }
                } else {
                    // Test required but no score yet — treat as not-qualifying placeholder.
                    $isQualifying = false;
                    $testPct = 0.0;
                }
            }

            $totalScore = $formula['test_enabled']
                ? round(($formula['test_weight'] * ($testPct ?? 0) + $formula['marks_weight'] * $boardPct) / 100, 4)
                : round($boardPct, 4);

            $rows[] = [
                'application_id' => $app->id,
                'student_id' => $student?->id,
                'reservation_category_id' => $student?->reservation_category_id,
                'dob' => $student?->dob?->format('Y-m-d'),
                'submitted_at' => optional($app->submitted_at)->toIso8601String(),
                'total_score' => $totalScore,
                'test_score' => $testScore,
                'test_pct' => $testPct !== null ? round($testPct, 2) : null,
                'marks_pct' => round($boardPct, 2),
                'is_qualifying' => $isQualifying,
                'is_absent' => $isAbsent,
            ];
        }

        return $rows;
    }

    /**
     * Pick the most relevant academic record's percentage. For UG programmes:
     * use 12th. For PG: use UG. Fallback: highest available percentage.
     *
     * @param  array<int, StudentAcademicRecord>  $records
     */
    protected function resolveBoardPct(array $records, AdmissionRound $round): float
    {
        if (empty($records)) {
            return 0.0;
        }

        $program = $round->program ?? $round->load('program')->program;
        $preferredLevel = ($program?->type === 'PG') ? StudentAcademicRecord::LEVEL_UG : StudentAcademicRecord::LEVEL_12TH;

        foreach ($records as $r) {
            if ($r->level === $preferredLevel && $r->percentage !== null) {
                return (float) $r->percentage;
            }
        }

        // Fallback: highest available
        $best = 0.0;
        foreach ($records as $r) {
            if ($r->percentage !== null && (float) $r->percentage > $best) {
                $best = (float) $r->percentage;
            }
        }

        return $best;
    }

    /**
     * Sort with deterministic tie-breakers. Absent / not-qualifying candidates
     * are sorted to the bottom regardless of score.
     */
    protected function rankCandidates(array $rows, array $formula): array
    {
        $tieBreakers = $formula['tie_breakers'] ?? self::DEFAULT_TIE_BREAKERS;

        usort($rows, function ($a, $b) use ($tieBreakers) {
            // Absent / disqualified to the bottom.
            $aDQ = $a['is_absent'] || ! $a['is_qualifying'];
            $bDQ = $b['is_absent'] || ! $b['is_qualifying'];
            if ($aDQ !== $bDQ) {
                return $aDQ ? 1 : -1;
            }

            // Primary: total_score DESC
            if ($a['total_score'] != $b['total_score']) {
                return $b['total_score'] <=> $a['total_score'];
            }

            // Tie-breakers in order
            foreach ($tieBreakers as $key) {
                $cmp = match ($key) {
                    MeritFormula::TIE_BREAK_TEST_MARKS => ($b['test_score'] ?? -1) <=> ($a['test_score'] ?? -1),
                    MeritFormula::TIE_BREAK_BOARD_PCT => ($b['marks_pct'] ?? -1) <=> ($a['marks_pct'] ?? -1),
                    MeritFormula::TIE_BREAK_DOB => ($a['dob'] ?? '9999-12-31') <=> ($b['dob'] ?? '9999-12-31'),
                    MeritFormula::TIE_BREAK_SUBMITTED_AT => ($a['submitted_at'] ?? '9999') <=> ($b['submitted_at'] ?? '9999'),
                    default => 0,
                };
                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            // Final stable break — application_id
            return $a['application_id'] <=> $b['application_id'];
        });

        return $rows;
    }

    protected function persistEntries(MeritList $list, array $ranked): void
    {
        $now = now();
        $categoryCounters = [];

        foreach ($ranked as $i => $row) {
            $catId = $row['reservation_category_id'];
            $categoryRank = null;
            if ($catId && ! $row['is_absent'] && $row['is_qualifying']) {
                $categoryCounters[$catId] = ($categoryCounters[$catId] ?? 0) + 1;
                $categoryRank = $categoryCounters[$catId];
            }

            MeritListEntry::create([
                'merit_list_id' => $list->id,
                'application_id' => $row['application_id'],
                'reservation_category_id' => $catId,
                'overall_rank' => $i + 1,
                'category_rank' => $categoryRank,
                'total_score' => $row['total_score'],
                'test_score' => $row['test_score'],
                'test_pct' => $row['test_pct'],
                'marks_pct' => $row['marks_pct'],
                'tie_break_data' => [
                    'dob' => $row['dob'],
                    'test_score' => $row['test_score'],
                    'submitted_at' => $row['submitted_at'],
                ],
                'is_qualifying' => $row['is_qualifying'],
                'is_absent' => $row['is_absent'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Compute per-category cutoffs from the round's programme reservation
     * matrix (seats per category) — the cutoff_score is the score of the last
     * candidate within seats_available.
     */
    protected function computeCutoffs(MeritList $list, AdmissionRound $round): void
    {
        $reservations = \Modules\Admissions\Models\ProgramReservation::query()
            ->where('program_id', $round->program_id)
            ->where('academic_session_id', $round->academic_session_id)
            ->with('category')
            ->get();

        foreach ($reservations as $reservation) {
            if ($reservation->category?->is_horizontal) {
                continue; // Phase 7 cutoffs are vertical-only.
            }

            $entries = MeritListEntry::query()
                ->where('merit_list_id', $list->id)
                ->where('reservation_category_id', $reservation->reservation_category_id)
                ->where('is_qualifying', true)
                ->where('is_absent', false)
                ->orderBy('category_rank')
                ->get();

            $cutoffScore = null;
            $lastRank = null;
            if ($entries->count() > 0 && $reservation->seats > 0) {
                $lastQualifying = $entries->take($reservation->seats)->last();
                $cutoffScore = $lastQualifying?->total_score;
                $lastRank = $lastQualifying?->overall_rank;
            }

            MeritCutoff::create([
                'merit_list_id' => $list->id,
                'reservation_category_id' => $reservation->reservation_category_id,
                'seats_available' => $reservation->seats,
                'cutoff_score' => $cutoffScore,
                'last_rank' => $lastRank,
                'candidates_in_category' => $entries->count(),
            ]);
        }
    }
}
