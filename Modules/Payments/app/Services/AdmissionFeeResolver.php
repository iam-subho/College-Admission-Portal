<?php

namespace Modules\Payments\Services;

use Modules\Academics\Models\ProgramAdmissionFee;
use Modules\Admissions\Models\Application;

/**
 * Resolves the admission fee due for an application (paid AFTER seat
 * allotment). Mirrors FeeResolver::applicationFeeFor but uses the
 * `programs.admission_fee` + `program_admission_fees` per-category overrides.
 *
 * Resolution order:
 *   1. program_admission_fees row for (program, student.reservation_category)
 *   2. programs.admission_fee
 *   3. 0 (fee is intentionally not configured)
 */
class AdmissionFeeResolver
{
    /**
     * @return array{amount: float, source: string}
     *               source ∈ {'override','programme','unset'}
     */
    public function admissionFeeFor(Application $application): array
    {
        $student = $application->student ?? $application->load('student')->student;
        $categoryId = $student?->reservation_category_id;

        if ($categoryId) {
            $override = ProgramAdmissionFee::query()
                ->where('program_id', $application->program_id)
                ->where('reservation_category_id', $categoryId)
                ->value('admission_fee');

            if ($override !== null) {
                return ['amount' => (float) $override, 'source' => 'override'];
            }
        }

        $program = $application->program ?? $application->load('program')->program;
        if ($program?->admission_fee !== null) {
            return ['amount' => (float) $program->admission_fee, 'source' => 'programme'];
        }

        return ['amount' => 0.0, 'source' => 'unset'];
    }
}
