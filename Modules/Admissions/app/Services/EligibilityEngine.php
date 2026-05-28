<?php

namespace Modules\Admissions\Services;

use Carbon\Carbon;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\EligibilityRule;
use Modules\Students\Models\StudentAcademicRecord;

class EligibilityEngine
{
    /**
     * Returns ['verdict' => 'pass'|'fail'|'pending', 'reasons' => string[]]
     *
     * Never throws. Never mutates the application. Caller decides whether
     * to persist the verdict.
     *
     * @return array{verdict: string, reasons: array<int, string>}
     */
    public function run(Application $application): array
    {
        $student = $application->student()->with('academicRecords')->first();
        $program = $application->program;

        if (! $student || ! $program) {
            return ['verdict' => Application::VERDICT_PENDING, 'reasons' => ['Student or programme missing.']];
        }

        $rules = EligibilityRule::where('program_id', $program->id)
            ->where('is_active', true)
            ->get();

        if ($rules->isEmpty()) {
            return ['verdict' => Application::VERDICT_PASS, 'reasons' => []];
        }

        $failures = [];

        foreach ($rules as $rule) {
            $reason = $this->evaluateRule($rule, $student, $application);
            if ($reason !== null) {
                $failures[] = $reason;
            }
        }

        return [
            'verdict' => empty($failures) ? Application::VERDICT_PASS : Application::VERDICT_FAIL,
            'reasons' => $failures,
        ];
    }

    /**
     * Evaluate one rule. Returns null on pass, or a failure-reason string.
     */
    protected function evaluateRule(EligibilityRule $rule, $student, Application $application): ?string
    {
        $params = $rule->params ?? [];

        return match ($rule->rule_type) {
            EligibilityRule::TYPE_MIN_PERCENTAGE => $this->checkMinPercentage($student, $params, $rule->label),
            EligibilityRule::TYPE_BOARD_IN => $this->checkBoardIn($student, $params, $rule->label),
            EligibilityRule::TYPE_SUBJECT_MINIMUM => $this->checkSubjectMinimum($student, $params, $rule->label),
            EligibilityRule::TYPE_AGE_BAND => $this->checkAgeBand($student, $application, $params, $rule->label),
            EligibilityRule::TYPE_GAP_YEAR_MAX => $this->checkGapYearMax($student, $application, $params, $rule->label),
            default => null,
        };
    }

    protected function checkMinPercentage($student, array $params, ?string $label): ?string
    {
        $level = $params['level'] ?? '12th';
        // Accept new key `min` (form-side) with `value` as legacy fallback.
        $threshold = (float) ($params['min'] ?? $params['value'] ?? 0);

        $record = $student->academicRecords->firstWhere('level', $level);
        if (! $record || $record->percentage === null) {
            return ($label ?? 'Minimum percentage').": no {$level} record uploaded.";
        }

        return ((float) $record->percentage) >= $threshold
            ? null
            : ($label ?? "Minimum {$threshold}% in {$level}").": got {$record->percentage}%.";
    }

    protected function checkBoardIn($student, array $params, ?string $label): ?string
    {
        $record = $student->academicRecords->firstWhere('level', $params['level'] ?? '12th');
        if (! $record || ! $record->board) {
            return ($label ?? 'Board').": no board specified.";
        }

        $boards = collect($params['boards'] ?? [])->map(fn ($b) => strtolower(trim($b)))->all();
        $studentBoard = strtolower(trim($record->board));

        foreach ($boards as $allowed) {
            if (str_contains($studentBoard, $allowed) || str_contains($allowed, $studentBoard)) {
                return null;
            }
        }

        return ($label ?? 'Board').": '{$record->board}' is not in the allowed list.";
    }

    protected function checkSubjectMinimum($student, array $params, ?string $label): ?string
    {
        $level = $params['level'] ?? '12th';
        // Build a list of {name, min} requirements supporting both shapes:
        //   1. New form-side: flat {subject, min, level}
        //   2. Legacy: {subjects: [{name, min}, ...], level}
        $requirements = [];
        if (! empty($params['subjects']) && is_array($params['subjects'])) {
            foreach ($params['subjects'] as $req) {
                $requirements[] = ['name' => $req['name'] ?? '', 'min' => (float) ($req['min'] ?? 0)];
            }
        } elseif (! empty($params['subject'])) {
            $requirements[] = ['name' => $params['subject'], 'min' => (float) ($params['min'] ?? 0)];
        }

        if (empty($requirements)) {
            return ($label ?? 'Subject minimum').': rule misconfigured (no subject specified).';
        }

        $record = $student->academicRecords->firstWhere('level', $level);
        if (! $record || ! $record->subjects) {
            return ($label ?? 'Subject minimum').": no subject marks recorded for {$level}.";
        }

        foreach ($requirements as $req) {
            $pct = $this->subjectPercentage($record->subjects, $req['name']);
            if ($pct === null) {
                return ($label ?? 'Subject minimum').": '{$req['name']}' not recorded.";
            }
            if ($pct < $req['min']) {
                return ($label ?? 'Subject minimum').": '{$req['name']}' below {$req['min']}% (got ".number_format($pct, 2).').';
            }
        }

        return null;
    }

    /**
     * Resolve a subject percentage from either the new rich shape
     * ([{name, full_marks, obtained_marks}]) or the legacy flat shape
     * ([{Subject: percentage}]).
     */
    protected function subjectPercentage(array $subjects, string $name): ?float
    {
        // New shape: list of objects with name/full_marks/obtained_marks
        foreach ($subjects as $row) {
            if (is_array($row) && isset($row['name']) && $row['name'] === $name) {
                $fm = (float) ($row['full_marks'] ?? 0);
                $om = (float) ($row['obtained_marks'] ?? 0);

                return $fm > 0 ? ($om / $fm) * 100 : null;
            }
        }

        // Legacy shape: assoc array keyed by subject name with raw percentage
        if (array_key_exists($name, $subjects) && ! is_array($subjects[$name])) {
            return (float) $subjects[$name];
        }

        return null;
    }

    protected function checkAgeBand($student, Application $application, array $params, ?string $label): ?string
    {
        if (! $student->dob) {
            return ($label ?? 'Age').': DOB missing.';
        }

        // Anchor date — prefer admin-supplied as_of, else session commencement, else today.
        $reference = ! empty($params['as_of'])
            ? Carbon::parse($params['as_of'])
            : (optional($application->session)->commencement_date
                ? Carbon::parse($application->session->commencement_date)
                : Carbon::now());

        $age = (int) Carbon::parse($student->dob)->diffInYears($reference);

        // Accept form-side keys (min_age/max_age) with legacy fallbacks (min_years/max_years).
        $min = $params['min_age'] ?? $params['min_years'] ?? null;
        $max = $params['max_age'] ?? $params['max_years'] ?? null;

        if ($min !== null && $age < (int) $min) {
            return ($label ?? 'Age').": below minimum {$min} years (age {$age}).";
        }
        if ($max !== null && $age > (int) $max) {
            return ($label ?? 'Age').": above maximum {$max} years (age {$age}).";
        }

        return null;
    }

    protected function checkGapYearMax($student, Application $application, array $params, ?string $label): ?string
    {
        $level = $params['level'] ?? '12th';
        // Accept form-side `max_gap` with `max` as legacy fallback.
        $maxGap = (int) ($params['max_gap'] ?? $params['max'] ?? 0);

        $record = $student->academicRecords->firstWhere('level', $level);
        if (! $record || ! $record->passing_year) {
            return ($label ?? 'Gap year').": no {$level} passing year recorded.";
        }

        $currentYear = optional($application->session)->commencement_date
            ? Carbon::parse($application->session->commencement_date)->year
            : Carbon::now()->year;

        $gap = $currentYear - (int) $record->passing_year;

        if ($gap > $maxGap) {
            return ($label ?? 'Gap year').": gap of {$gap} years exceeds maximum {$maxGap}.";
        }

        return null;
    }
}
