<?php

namespace Modules\Tests\Services;

use Modules\Admissions\Models\Application;
use Modules\Tests\Models\AdmissionTestCandidate;
use Modules\Tests\Models\AdmissionTestConfig;
use Modules\Tests\Models\AdmissionTestSchedule;

/**
 * Ensures every paid application for a test-enabled (program, session) has
 * a corresponding AdmissionTestCandidate row. Idempotent — safe to re-run.
 */
class TestCandidateRegistrar
{
    /**
     * Register all paid applications for the given config's programme + session.
     * Returns the count of newly-registered candidates.
     */
    public function registerForSchedule(AdmissionTestSchedule $schedule): int
    {
        $config = $schedule->config()->first();
        if (! $config || ! $config->is_test_enabled) {
            return 0;
        }

        $appIds = Application::query()
            ->where('program_id', $config->program_id)
            ->where('academic_session_id', $config->academic_session_id)
            ->where('status', Application::STATUS_SUBMITTED)
            ->whereIn('payment_status', [
                Application::PAYMENT_PAID,
                Application::PAYMENT_COVERED,
                Application::PAYMENT_NOT_REQUIRED,
            ])
            ->pluck('id');

        $created = 0;
        foreach ($appIds as $appId) {
            $cand = AdmissionTestCandidate::firstOrCreate(
                ['application_id' => $appId],
                ['admission_test_schedule_id' => $schedule->id],
            );
            if ($cand->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    public function ensureCandidateFor(Application $application): ?AdmissionTestCandidate
    {
        $config = AdmissionTestConfig::query()
            ->where('program_id', $application->program_id)
            ->where('academic_session_id', $application->academic_session_id)
            ->where('is_test_enabled', true)
            ->with('schedule')
            ->first();

        if (! $config?->schedule) {
            return null;
        }

        return AdmissionTestCandidate::firstOrCreate(
            ['application_id' => $application->id],
            ['admission_test_schedule_id' => $config->schedule->id],
        );
    }
}
