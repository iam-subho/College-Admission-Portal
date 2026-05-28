<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Students\Models\Student;
use Modules\Tests\Models\AdmissionTestCandidate;
use Modules\Tests\Models\AdmissionTestConfig;
use Modules\Tests\Models\AdmissionTestSchedule;
use Modules\Tests\Models\AdmissionTestScore;
use Modules\Tests\Services\MarksCsvImporter;
use Modules\Tests\Services\TestCandidateRegistrar;
use Modules\Users\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeStaffUser(): User
{
    $u = User::create([
        'name' => 'Admissions Officer',
        'email' => fake()->unique()->safeEmail(),
        'mobile' => '9988'.fake()->numerify('######'),
        'password' => bcrypt('Secret123'),
        'status' => 'active',
    ]);
    $u->assignRole('admin');

    return $u;
}

function makePaidApplication(Program $program, AcademicSession $session): Application
{
    $student = Student::factory()->create();

    return Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_SUBMITTED,
        'payment_status' => Application::PAYMENT_PAID,
        'application_number' => 'SVNC/UG/2026/'.fake()->unique()->numerify('######'),
    ]);
}

it('skips the test step when admission_test_config.is_test_enabled is false', function () {
    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create();
    $app = makePaidApplication($program, $session);

    AdmissionTestConfig::create([
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'is_test_enabled' => false,
        'test_weight' => 0,
        'marks_weight' => 100,
    ]);

    $candidate = app(TestCandidateRegistrar::class)->ensureCandidateFor($app);

    expect($candidate)->toBeNull();
    expect(AdmissionTestCandidate::count())->toBe(0);
});

it('registers only paid applications as candidates when test is enabled', function () {
    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create();
    makePaidApplication($program, $session);
    makePaidApplication($program, $session);

    // Unpaid applicant should NOT be registered
    Application::factory()->create([
        'student_id' => Student::factory()->create()->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_SUBMITTED,
        'payment_status' => Application::PAYMENT_PENDING,
        'application_number' => 'SVNC/UG/2026/UNPAID',
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
        'test_date' => now()->addDays(30)->toDateString(),
        'venue' => 'SVNC Main Hall',
    ]);

    $created = app(TestCandidateRegistrar::class)->registerForSchedule($schedule);
    expect($created)->toBe(2);

    // Idempotent on re-run
    expect(app(TestCandidateRegistrar::class)->registerForSchedule($schedule))->toBe(0);
});

it('publishes admit cards and generates sequential roll numbers', function () {
    $admin = makeStaffUser();
    $session = AcademicSession::factory()->active()->create(['code' => '2026-27']);
    $program = Program::factory()->create(['code' => 'UGCS01']);
    makePaidApplication($program, $session);
    makePaidApplication($program, $session);

    $config = AdmissionTestConfig::create([
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'is_test_enabled' => true,
        'max_marks' => 100,
        'test_weight' => 60,
        'marks_weight' => 40,
    ]);
    AdmissionTestSchedule::create([
        'admission_test_config_id' => $config->id,
        'test_date' => now()->addDays(30)->toDateString(),
        'venue' => 'Main Hall',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.admission-tests.publish-admit-cards', $config))
        ->assertRedirect();

    $candidates = AdmissionTestCandidate::orderBy('id')->get();
    expect($candidates)->toHaveCount(2);
    expect($candidates[0]->roll_number)->toBe('TEST/2026-27/UGCS01/000001');
    expect($candidates[1]->roll_number)->toBe('TEST/2026-27/UGCS01/000002');
    expect($candidates[0]->admit_card_published)->toBeTrue();
});

it('previews CSV with errors and only commits valid rows, skipping locked', function () {
    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create();
    $a1 = makePaidApplication($program, $session);
    $a2 = makePaidApplication($program, $session);

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
        'test_date' => now()->addDays(30)->toDateString(),
        'venue' => 'Main Hall',
    ]);
    app(TestCandidateRegistrar::class)->registerForSchedule($schedule);

    // Lock a1's score (simulate merit-published lock)
    $c1 = AdmissionTestCandidate::where('application_id', $a1->id)->first();
    AdmissionTestScore::create([
        'admission_test_candidate_id' => $c1->id,
        'raw_marks' => 50,
        'attendance' => 'present',
        'is_locked' => true,
        'locked_at' => now(),
        'entered_at' => now(),
    ]);

    $csv = "application_number,raw_marks\n"
        ."{$a1->application_number},80\n"  // locked
        ."{$a2->application_number},75\n"  // valid
        ."BOGUS/APP/999,42\n"              // unknown
        ."{$a2->application_number},200\n"; // exceeds max

    $tmp = tempnam(sys_get_temp_dir(), 'csv').'.csv';
    file_put_contents($tmp, $csv);
    $file = new UploadedFile($tmp, 'marks.csv', 'text/csv', null, true);

    $importer = app(MarksCsvImporter::class);
    $preview = $importer->preview($file, $config);

    expect($preview['summary']['total'])->toBe(4);
    expect($preview['summary']['errors'])->toBe(3);
    expect($preview['summary']['will_create'])->toBe(1);
    expect($preview['summary']['locked'])->toBe(1);

    $written = $importer->commit($preview['rows'], $config, makeStaffUser()->id);
    expect($written)->toBe(1);

    $c1->refresh();
    expect((float) $c1->score->raw_marks)->toBe(50.0); // locked, unchanged

    $c2 = AdmissionTestCandidate::where('application_id', $a2->id)->first()->load('score');
    expect((float) $c2->score->raw_marks)->toBe(75.0);
    expect($c2->score->entered_via)->toBe('csv');
});

it('blocks admit card download when fee not paid', function () {
    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create();
    $student = Student::factory()->create();
    $student->user->assignRole('student');
    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_SUBMITTED,
        'payment_status' => Application::PAYMENT_PENDING,
        'application_number' => 'SVNC/UG/2026/000099',
    ]);
    $config = AdmissionTestConfig::create([
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'is_test_enabled' => true,
        'test_weight' => 60,
        'marks_weight' => 40,
        'max_marks' => 100,
    ]);
    AdmissionTestSchedule::create([
        'admission_test_config_id' => $config->id,
        'test_date' => now()->addDays(30)->toDateString(),
        'venue' => 'Main Hall',
        'admit_cards_published' => true,
    ]);

    $this->actingAs($student->user)
        ->get(route('student.admit-card.download', $app))
        ->assertRedirect(route('student.admit-card.show', $app));
});
