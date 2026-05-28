<?php

use App\Models\User;
use Modules\Academics\Models\Department;
use Modules\Academics\Models\Program;
use Modules\Admissions\Actions\ApplicationSubmitAction;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\EligibilityRule;
use Modules\Students\Models\Student;
use Modules\Students\Models\StudentAcademicRecord;

it('always succeeds, even when eligibility fails', function () {
    $student = Student::factory()->create();
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);
    $session = AcademicSession::factory()->active()->create(['commencement_date' => '2026-07-01']);

    StudentAcademicRecord::factory()->create([
        'student_id' => $student->id,
        'level' => '12th',
        'percentage' => 45.00,
    ]);

    EligibilityRule::create([
        'program_id' => $program->id,
        'rule_type' => EligibilityRule::TYPE_MIN_PERCENTAGE,
        'params' => ['level' => '12th', 'value' => 60],
        'is_active' => true,
    ]);

    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_DRAFT,
    ]);

    $result = app(ApplicationSubmitAction::class)->execute($app);

    expect($result->status)->toBe(Application::STATUS_SUBMITTED);
    expect($result->eligibility_verdict)->toBe(Application::VERDICT_FAIL);
    expect($result->eligibility_reasons)->not->toBeEmpty();
    expect($result->application_number)->not->toBeNull();
    expect($result->submitted_at)->not->toBeNull();
});

it('allocates a unique sequential application number', function () {
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id, 'type' => 'UG']);
    $session = AcademicSession::factory()->active()->create(['commencement_date' => '2026-07-01']);

    $apps = [];
    for ($i = 0; $i < 3; $i++) {
        $student = Student::factory()->create();
        $apps[] = app(ApplicationSubmitAction::class)->execute(
            Application::factory()->create([
                'student_id' => $student->id,
                'program_id' => $program->id,
                'academic_session_id' => $session->id,
            ]),
        );
    }

    $numbers = collect($apps)->pluck('application_number');
    expect($numbers->unique()->count())->toBe(3);
    expect($apps[0]->application_number)->toContain('SVNC/UG/2026/000001');
    expect($apps[2]->application_number)->toContain('SVNC/UG/2026/000003');
});

it('records eligibility_reasons as an array', function () {
    $student = Student::factory()->create();
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);
    $session = AcademicSession::factory()->active()->create();

    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
    ]);

    $result = app(ApplicationSubmitAction::class)->execute($app);

    expect($result->eligibility_reasons)->toBeArray();
});

it('does not resubmit a non-draft application', function () {
    $student = Student::factory()->create();
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);
    $session = AcademicSession::factory()->active()->create();

    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_SUBMITTED,
        'submitted_at' => now()->subDay(),
    ]);

    $beforeSubmittedAt = $app->submitted_at;
    $result = app(ApplicationSubmitAction::class)->execute($app);

    expect($result->submitted_at?->equalTo($beforeSubmittedAt))->toBeTrue();
});
