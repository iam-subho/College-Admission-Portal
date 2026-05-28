<?php

use Illuminate\Support\Facades\Event;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\AdmissionRound;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ReservationCategory;
use Modules\Merit\Events\MeritListPublished;
use Modules\Merit\Models\MeritList;
use Modules\Merit\Services\MeritGenerator;
use Modules\Students\Models\Student;
use Modules\Students\Models\StudentAcademicRecord;
use Modules\Tests\Models\AdmissionTestCandidate;
use Modules\Tests\Models\AdmissionTestConfig;
use Modules\Tests\Models\AdmissionTestSchedule;
use Modules\Tests\Models\AdmissionTestScore;
use Modules\Users\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeMeritCandidate(Program $program, AcademicSession $session, float $boardPct, ?string $dob = null): Application
{
    $student = Student::factory()->create(['dob' => $dob ?? '2007-01-01']);

    StudentAcademicRecord::factory()->create([
        'student_id' => $student->id,
        'level' => StudentAcademicRecord::LEVEL_12TH,
        'percentage' => $boardPct,
    ]);

    return Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_SUBMITTED,
        'payment_status' => Application::PAYMENT_PAID,
        'application_number' => 'SVNC/'.$program->code.'/'.fake()->unique()->numerify('######'),
        'submitted_at' => now()->subDays(rand(1, 30)),
    ]);
}

it('generates pure-marks merit list when test is disabled', function () {
    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create(['type' => 'UG']);
    $round = AdmissionRound::create([
        'academic_session_id' => $session->id,
        'program_id' => $program->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'status' => 'open',
    ]);

    makeMeritCandidate($program, $session, 88.00);
    makeMeritCandidate($program, $session, 92.00);
    makeMeritCandidate($program, $session, 75.00);

    $list = app(MeritGenerator::class)->generate($round);

    expect($list->total_candidates)->toBe(3);
    expect($list->status)->toBe(MeritList::STATUS_DRAFT);

    $entries = $list->entries()->orderBy('overall_rank')->get();
    expect((float) $entries[0]->marks_pct)->toBe(92.0);
    expect((float) $entries[0]->total_score)->toBe(92.0);
    expect((float) $entries[1]->marks_pct)->toBe(88.0);
    expect((float) $entries[2]->marks_pct)->toBe(75.0);

    expect($list->formula_snapshot['test_enabled'])->toBeFalse();
    expect((float) $list->formula_snapshot['marks_weight'])->toBe(100.0);
});

it('generates weighted merit list with test + board marks combined', function () {
    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create(['type' => 'UG']);
    $round = AdmissionRound::create([
        'academic_session_id' => $session->id,
        'program_id' => $program->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'status' => 'open',
    ]);

    $config = AdmissionTestConfig::create([
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'is_test_enabled' => true,
        'max_marks' => 100,
        'qualifying_marks' => 33,
        'test_weight' => 60,
        'marks_weight' => 40,
    ]);
    $schedule = AdmissionTestSchedule::create([
        'admission_test_config_id' => $config->id,
        'test_date' => now()->addDays(10)->toDateString(),
        'venue' => 'Hall A',
    ]);

    // A: test 80, board 90 → 60*80 + 40*90 = 48 + 36 = 84.00
    $a = makeMeritCandidate($program, $session, 90.00);
    // B: test 95, board 70 → 60*95 + 40*70 = 57 + 28 = 85.00
    $b = makeMeritCandidate($program, $session, 70.00);

    foreach ([[$a, 80], [$b, 95]] as [$app, $marks]) {
        $cand = AdmissionTestCandidate::create([
            'application_id' => $app->id,
            'admission_test_schedule_id' => $schedule->id,
        ]);
        AdmissionTestScore::create([
            'admission_test_candidate_id' => $cand->id,
            'raw_marks' => $marks,
            'attendance' => 'present',
            'entered_at' => now(),
        ]);
    }

    $list = app(MeritGenerator::class)->generate($round);
    $entries = $list->entries()->orderBy('overall_rank')->get();

    expect((float) $entries[0]->total_score)->toBe(85.0);
    expect($entries[0]->application_id)->toBe($b->id);
    expect((float) $entries[1]->total_score)->toBe(84.0);
    expect($entries[1]->application_id)->toBe($a->id);
});

it('breaks ties deterministically: test marks first, then older DOB', function () {
    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create(['type' => 'UG']);
    $round = AdmissionRound::create([
        'academic_session_id' => $session->id,
        'program_id' => $program->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'status' => 'open',
    ]);

    $config = AdmissionTestConfig::create([
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'is_test_enabled' => true,
        'max_marks' => 100,
        'qualifying_marks' => 33,
        'test_weight' => 50,
        'marks_weight' => 50,
    ]);
    $schedule = AdmissionTestSchedule::create([
        'admission_test_config_id' => $config->id,
        'test_date' => now()->addDays(10)->toDateString(),
        'venue' => 'Hall A',
    ]);

    // Both candidates produce total_score 80.
    // A: test 80, board 80 → 50*80 + 50*80 = 80
    // B: test 90, board 70 → 50*90 + 50*70 = 80 — higher test marks wins
    $a = makeMeritCandidate($program, $session, 80.00, '2006-01-01');
    $b = makeMeritCandidate($program, $session, 70.00, '2007-01-01');

    foreach ([[$a, 80], [$b, 90]] as [$app, $marks]) {
        $cand = AdmissionTestCandidate::create([
            'application_id' => $app->id,
            'admission_test_schedule_id' => $schedule->id,
        ]);
        AdmissionTestScore::create([
            'admission_test_candidate_id' => $cand->id,
            'raw_marks' => $marks,
            'attendance' => 'present',
            'entered_at' => now(),
        ]);
    }

    $list = app(MeritGenerator::class)->generate($round);
    $entries = $list->entries()->orderBy('overall_rank')->get();

    expect((float) $entries[0]->total_score)->toBe(80.0);
    expect((float) $entries[1]->total_score)->toBe(80.0);
    expect($entries[0]->application_id)->toBe($b->id);
    expect($entries[1]->application_id)->toBe($a->id);
});

it('refuses to regenerate a published merit list', function () {
    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create(['type' => 'UG']);
    $round = AdmissionRound::create([
        'academic_session_id' => $session->id,
        'program_id' => $program->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'status' => 'open',
    ]);
    makeMeritCandidate($program, $session, 80.00);

    $generator = app(MeritGenerator::class);
    $list = $generator->generate($round);
    $generator->publish($list, 1);

    expect(fn () => $generator->generate($round))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('publishing locks admission_test_scores and fires event', function () {
    Event::fake([MeritListPublished::class]);

    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create(['type' => 'UG']);
    $round = AdmissionRound::create([
        'academic_session_id' => $session->id,
        'program_id' => $program->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'status' => 'open',
    ]);
    $config = AdmissionTestConfig::create([
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'is_test_enabled' => true,
        'max_marks' => 100,
        'test_weight' => 60,
        'marks_weight' => 40,
    ]);
    $schedule = AdmissionTestSchedule::create([
        'admission_test_config_id' => $config->id,
        'test_date' => now()->addDays(10)->toDateString(),
        'venue' => 'Hall A',
    ]);

    $app = makeMeritCandidate($program, $session, 90.00);
    $cand = AdmissionTestCandidate::create([
        'application_id' => $app->id,
        'admission_test_schedule_id' => $schedule->id,
    ]);
    $score = AdmissionTestScore::create([
        'admission_test_candidate_id' => $cand->id,
        'raw_marks' => 75,
        'attendance' => 'present',
        'entered_at' => now(),
        'is_locked' => false,
    ]);

    $generator = app(MeritGenerator::class);
    $list = $generator->generate($round);
    $generator->publish($list, 1);

    Event::assertDispatched(MeritListPublished::class);
    expect($score->fresh()->is_locked)->toBeTrue();
    expect($round->fresh()->status)->toBe(AdmissionRound::STATUS_MERIT_PUBLISHED);
});
