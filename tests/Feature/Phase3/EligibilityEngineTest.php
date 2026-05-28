<?php

use Modules\Academics\Models\Department;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\EligibilityRule;
use Modules\Admissions\Services\EligibilityEngine;
use Modules\Students\Models\Student;
use Modules\Students\Models\StudentAcademicRecord;

function makeApplicationFor(Student $student, Program $program, ?AcademicSession $session = null): Application
{
    return Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => ($session ?? AcademicSession::factory()->active()->create())->id,
    ]);
}

it('passes when no rules are defined', function () {
    $student = Student::factory()->create();
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);

    $app = makeApplicationFor($student, $program);
    $result = app(EligibilityEngine::class)->run($app);

    expect($result['verdict'])->toBe(Application::VERDICT_PASS);
    expect($result['reasons'])->toBe([]);
});

it('fails min_percentage when below threshold', function () {
    $student = Student::factory()->create();
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);

    StudentAcademicRecord::factory()->create([
        'student_id' => $student->id,
        'level' => '12th',
        'percentage' => 55.00,
    ]);

    EligibilityRule::create([
        'program_id' => $program->id,
        'rule_type' => EligibilityRule::TYPE_MIN_PERCENTAGE,
        'params' => ['level' => '12th', 'value' => 60],
        'label' => 'Min 60% in 12th',
        'is_active' => true,
    ]);

    $result = app(EligibilityEngine::class)->run(makeApplicationFor($student, $program));

    expect($result['verdict'])->toBe(Application::VERDICT_FAIL);
    expect($result['reasons'])->toHaveCount(1);
    expect($result['reasons'][0])->toContain('55');
});

it('passes min_percentage when at or above threshold', function () {
    $student = Student::factory()->create();
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);

    StudentAcademicRecord::factory()->create([
        'student_id' => $student->id,
        'level' => '12th',
        'percentage' => 75.00,
    ]);

    EligibilityRule::create([
        'program_id' => $program->id,
        'rule_type' => EligibilityRule::TYPE_MIN_PERCENTAGE,
        'params' => ['level' => '12th', 'value' => 60],
        'is_active' => true,
    ]);

    expect(app(EligibilityEngine::class)->run(makeApplicationFor($student, $program))['verdict'])
        ->toBe(Application::VERDICT_PASS);
});

it('checks board_in', function () {
    $student = Student::factory()->create();
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);

    StudentAcademicRecord::factory()->create([
        'student_id' => $student->id,
        'level' => '12th',
        'board' => 'Bihar Board',
        'percentage' => 80,
    ]);

    EligibilityRule::create([
        'program_id' => $program->id,
        'rule_type' => EligibilityRule::TYPE_BOARD_IN,
        'params' => ['level' => '12th', 'boards' => ['CBSE', 'ICSE', 'GSHSEB']],
        'is_active' => true,
    ]);

    $result = app(EligibilityEngine::class)->run(makeApplicationFor($student, $program));

    expect($result['verdict'])->toBe(Application::VERDICT_FAIL);
});

it('checks subject_minimum', function () {
    $student = Student::factory()->create();
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);

    StudentAcademicRecord::factory()->create([
        'student_id' => $student->id,
        'level' => '12th',
        'subjects' => ['Mathematics' => 40, 'Physics' => 70],
    ]);

    EligibilityRule::create([
        'program_id' => $program->id,
        'rule_type' => EligibilityRule::TYPE_SUBJECT_MINIMUM,
        'params' => ['level' => '12th', 'subjects' => [['name' => 'Mathematics', 'min' => 50]]],
        'is_active' => true,
    ]);

    $result = app(EligibilityEngine::class)->run(makeApplicationFor($student, $program));

    expect($result['verdict'])->toBe(Application::VERDICT_FAIL);
    expect($result['reasons'][0])->toContain('Mathematics');
});

it('checks age_band', function () {
    $student = Student::factory()->create([
        'dob' => now()->subYears(30)->toDateString(), // too old
    ]);
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);
    $session = AcademicSession::factory()->active()->create(['commencement_date' => now()->toDateString()]);

    EligibilityRule::create([
        'program_id' => $program->id,
        'rule_type' => EligibilityRule::TYPE_AGE_BAND,
        'params' => ['min_years' => 17, 'max_years' => 25],
        'is_active' => true,
    ]);

    $result = app(EligibilityEngine::class)->run(makeApplicationFor($student, $program, $session));

    expect($result['verdict'])->toBe(Application::VERDICT_FAIL);
});

it('checks gap_year_max', function () {
    $student = Student::factory()->create();
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);
    $session = AcademicSession::factory()->active()->create(['commencement_date' => '2026-07-01']);

    StudentAcademicRecord::factory()->create([
        'student_id' => $student->id,
        'level' => '12th',
        'passing_year' => 2020, // 6 years gap
        'percentage' => 80,
    ]);

    EligibilityRule::create([
        'program_id' => $program->id,
        'rule_type' => EligibilityRule::TYPE_GAP_YEAR_MAX,
        'params' => ['max' => 2, 'level' => '12th'],
        'is_active' => true,
    ]);

    $result = app(EligibilityEngine::class)->run(makeApplicationFor($student, $program, $session));

    expect($result['verdict'])->toBe(Application::VERDICT_FAIL);
});
