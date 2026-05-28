<?php

namespace Modules\Admissions\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\ProgramReservation;
use Modules\Admissions\Models\ReservationCategory;

class SyncReservationMatrixAction
{
    /**
     * @param  array<int, array{category_id:int, seats:int, relaxation_percent?:float|null}>  $rows
     */
    public function execute(AcademicSession $session, Program $program, array $rows): void
    {
        $rows = collect($rows);

        $categories = ReservationCategory::whereIn('id', $rows->pluck('category_id'))
            ->get()
            ->keyBy('id');

        $verticalSum = 0;
        foreach ($rows as $row) {
            $cat = $categories->get($row['category_id']);
            if (! $cat) {
                throw ValidationException::withMessages(['matrix' => "Unknown category id {$row['category_id']}."]);
            }
            if (! $cat->is_horizontal) {
                $verticalSum += (int) $row['seats'];
            }
        }

        if ($verticalSum !== (int) $program->intake_capacity) {
            throw ValidationException::withMessages([
                'matrix' => "Vertical reservation seats ({$verticalSum}) must equal intake capacity ({$program->intake_capacity}).",
            ]);
        }

        DB::transaction(function () use ($session, $program, $rows) {
            ProgramReservation::where('academic_session_id', $session->id)
                ->where('program_id', $program->id)
                ->delete();

            foreach ($rows as $row) {
                if ((int) $row['seats'] <= 0) {
                    continue;
                }
                ProgramReservation::create([
                    'academic_session_id' => $session->id,
                    'program_id' => $program->id,
                    'reservation_category_id' => $row['category_id'],
                    'seats' => (int) $row['seats'],
                    'relaxation_percent' => $row['relaxation_percent'] ?? null,
                ]);
            }
        });
    }
}
