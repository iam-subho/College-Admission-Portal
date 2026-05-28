<?php

use Illuminate\Validation\ValidationException;
use Modules\Academics\Models\Department;
use Modules\Academics\Models\Program;
use Modules\Admissions\Actions\SyncReservationMatrixAction;
use Modules\Admissions\Database\Seeders\ReservationCategorySeeder;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\ProgramReservation;
use Modules\Admissions\Models\ReservationCategory;

beforeEach(function () {
    $this->seed(ReservationCategorySeeder::class);
});

function setupSessionAndProgram(int $intake = 100): array
{
    $session = AcademicSession::factory()->active()->create();
    $dept = Department::factory()->create();
    $program = Program::factory()->create([
        'department_id' => $dept->id,
        'intake_capacity' => $intake,
    ]);

    return [$session, $program];
}

it('accepts matrix whose vertical seats sum to intake_capacity', function () {
    [$session, $program] = setupSessionAndProgram(100);

    $cats = ReservationCategory::whereIn('code', ['UR', 'OBC_NCL', 'SC', 'ST', 'EWS'])
        ->get()
        ->keyBy('code');

    $rows = [
        ['category_id' => $cats['UR']->id, 'seats' => 40],
        ['category_id' => $cats['OBC_NCL']->id, 'seats' => 27],
        ['category_id' => $cats['SC']->id, 'seats' => 15],
        ['category_id' => $cats['ST']->id, 'seats' => 8],
        ['category_id' => $cats['EWS']->id, 'seats' => 10],
    ];

    app(SyncReservationMatrixAction::class)->execute($session, $program, $rows);

    expect(ProgramReservation::where('program_id', $program->id)->sum('seats'))->toBe(100);
});

it('rejects matrix whose vertical seats do not equal intake_capacity', function () {
    [$session, $program] = setupSessionAndProgram(100);

    $cats = ReservationCategory::whereIn('code', ['UR', 'SC'])->get()->keyBy('code');

    $rows = [
        ['category_id' => $cats['UR']->id, 'seats' => 50],
        ['category_id' => $cats['SC']->id, 'seats' => 30], // total 80, not 100
    ];

    expect(fn () => app(SyncReservationMatrixAction::class)->execute($session, $program, $rows))
        ->toThrow(ValidationException::class);

    expect(ProgramReservation::where('program_id', $program->id)->count())->toBe(0);
});

it('allows horizontal seats not to count against intake', function () {
    [$session, $program] = setupSessionAndProgram(100);

    $ur = ReservationCategory::where('code', 'UR')->first();
    $pwd = ReservationCategory::where('code', 'PWD')->first();

    $rows = [
        ['category_id' => $ur->id, 'seats' => 100],
        ['category_id' => $pwd->id, 'seats' => 5], // horizontal overlay, doesn't count
    ];

    app(SyncReservationMatrixAction::class)->execute($session, $program, $rows);

    expect(ProgramReservation::where('program_id', $program->id)->sum('seats'))->toBe(105);
});

it('overwrites existing matrix on resync', function () {
    [$session, $program] = setupSessionAndProgram(50);

    $ur = ReservationCategory::where('code', 'UR')->first();

    app(SyncReservationMatrixAction::class)->execute($session, $program, [
        ['category_id' => $ur->id, 'seats' => 50],
    ]);

    expect(ProgramReservation::where('program_id', $program->id)->sum('seats'))->toBe(50);

    // Resync with different distribution
    $sc = ReservationCategory::where('code', 'SC')->first();
    app(SyncReservationMatrixAction::class)->execute($session, $program, [
        ['category_id' => $ur->id, 'seats' => 35],
        ['category_id' => $sc->id, 'seats' => 15],
    ]);

    expect(ProgramReservation::where('program_id', $program->id)->sum('seats'))->toBe(50);
    expect(ProgramReservation::where('program_id', $program->id)->count())->toBe(2);
});
