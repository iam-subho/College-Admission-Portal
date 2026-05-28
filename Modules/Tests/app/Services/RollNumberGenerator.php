<?php

namespace Modules\Tests\Services;

use Illuminate\Support\Facades\DB;
use Modules\Tests\Models\AdmissionTestCandidate;
use Modules\Tests\Models\AdmissionTestSchedule;

class RollNumberGenerator
{
    /**
     * Generate the next sequential roll number for a schedule.
     * Format: TEST/{YYYY}/{PROG_CODE}/{0000NN}
     * Uses a transactional max() lookup over candidates of the same schedule
     * to avoid clashes during bulk admit-card publishing.
     */
    public function next(AdmissionTestSchedule $schedule): string
    {
        $config = $schedule->config()->with('program:id,code', 'session:id,code')->first();
        $progCode = $config->program?->code ?? 'PROG';
        $sessionCode = $config->session?->code ?? date('Y');

        return DB::transaction(function () use ($schedule, $progCode, $sessionCode) {
            $existing = AdmissionTestCandidate::query()
                ->where('admission_test_schedule_id', $schedule->id)
                ->whereNotNull('roll_number')
                ->lockForUpdate()
                ->count();

            $serial = $existing + 1;

            return sprintf(
                'TEST/%s/%s/%06d',
                $sessionCode,
                strtoupper($progCode),
                $serial,
            );
        });
    }
}
