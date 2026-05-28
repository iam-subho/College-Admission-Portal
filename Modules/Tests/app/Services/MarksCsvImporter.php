<?php

namespace Modules\Tests\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Modules\Admissions\Models\Application;
use Modules\Tests\Models\AdmissionTestCandidate;
use Modules\Tests\Models\AdmissionTestConfig;
use Modules\Tests\Models\AdmissionTestScore;

/**
 * Validates a CSV of `application_number,raw_marks` and produces a preview
 * (rows that would update, rows that would create, rows with errors). Commit
 * step writes the validated rows to admission_test_scores.
 */
class MarksCsvImporter
{
    /**
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    public function preview(UploadedFile $file, AdmissionTestConfig $config): array
    {
        $csv = Reader::createFromPath($file->getRealPath());
        $csv->setHeaderOffset(0);

        $rows = [];
        $summary = ['total' => 0, 'will_update' => 0, 'will_create' => 0, 'errors' => 0, 'locked' => 0];

        $candidateIdsByAppNumber = AdmissionTestCandidate::query()
            ->whereHas('schedule', fn ($q) => $q->where('admission_test_config_id', $config->id))
            ->with('application:id,application_number')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->application?->application_number => $c]);

        foreach ($csv->getRecords() as $i => $record) {
            $summary['total']++;
            $appNumber = trim((string) ($record['application_number'] ?? ''));
            $rawMarks = trim((string) ($record['raw_marks'] ?? ''));
            $errors = [];

            if ($appNumber === '') {
                $errors[] = 'application_number missing';
            }

            $candidate = $candidateIdsByAppNumber[$appNumber] ?? null;
            if (! $candidate) {
                $errors[] = "no candidate registered for {$appNumber}";
            }

            if ($rawMarks !== '' && ! is_numeric($rawMarks)) {
                $errors[] = "raw_marks not numeric ({$rawMarks})";
            } elseif ($rawMarks !== '' && $config->max_marks && (float) $rawMarks > (float) $config->max_marks) {
                $errors[] = "raw_marks {$rawMarks} exceeds max {$config->max_marks}";
            }

            $existingScore = $candidate?->score;
            $isLocked = $existingScore?->is_locked ?? false;
            if ($isLocked) {
                $errors[] = 'score locked (merit already published)';
                $summary['locked']++;
            }

            if (! empty($errors)) {
                $summary['errors']++;
            } elseif ($existingScore) {
                $summary['will_update']++;
            } else {
                $summary['will_create']++;
            }

            $rows[] = [
                'line' => $i + 2, // header is row 1
                'application_number' => $appNumber,
                'raw_marks' => $rawMarks,
                'existing_marks' => $existingScore?->raw_marks,
                'is_locked' => $isLocked,
                'errors' => $errors,
                'valid' => empty($errors),
            ];
        }

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * Persist the validated rows. Skips locked / errored rows.
     */
    public function commit(array $rows, AdmissionTestConfig $config, int $userId): int
    {
        $appNumbers = collect($rows)->where('valid', true)->pluck('application_number')->all();

        $candidates = AdmissionTestCandidate::query()
            ->whereHas('schedule', fn ($q) => $q->where('admission_test_config_id', $config->id))
            ->whereHas('application', fn ($q) => $q->whereIn('application_number', $appNumbers))
            ->with('application:id,application_number')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->application?->application_number => $c]);

        $written = 0;
        DB::transaction(function () use ($rows, $candidates, $userId, &$written) {
            foreach ($rows as $row) {
                if (! $row['valid']) {
                    continue;
                }
                $candidate = $candidates[$row['application_number']] ?? null;
                if (! $candidate) {
                    continue;
                }

                $rawMarks = $row['raw_marks'] === '' ? null : (float) $row['raw_marks'];
                $attendance = $rawMarks === null ? AdmissionTestScore::ATTENDANCE_ABSENT : AdmissionTestScore::ATTENDANCE_PRESENT;

                AdmissionTestScore::updateOrCreate(
                    ['admission_test_candidate_id' => $candidate->id],
                    [
                        'raw_marks' => $rawMarks,
                        'attendance' => $attendance,
                        'entered_via' => AdmissionTestScore::ENTERED_VIA_CSV,
                        'entered_by' => $userId,
                        'entered_at' => now(),
                    ],
                );
                $written++;
            }
        });

        return $written;
    }
}
