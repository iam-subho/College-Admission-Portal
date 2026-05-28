<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Academics\Models\Department;
use Modules\Academics\Models\Program;
use Modules\Academics\Models\ProgrammeCoursePool;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ApplicationCourseSelection;
use Modules\Students\Models\Student;
use Modules\Users\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeStudentUserAndApp(): array
{
    $user = User::create([
        'name' => 'Auto Save',
        'email' => 'autosave@svnc.test',
        'mobile' => '9988770099',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $user->assignRole('student');

    $student = Student::create(['user_id' => $user->id, 'profile_locked' => true]);
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);
    $session = AcademicSession::factory()->active()->create();

    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_DRAFT,
        'draft_data' => [],
    ]);

    // Seed one pool row per required category so we have something to pick.
    $pools = [];
    foreach (['minor', 'aec', 'sec', 'vac'] as $cat) {
        $pools[$cat] = ProgrammeCoursePool::create([
            'program_id' => $program->id,
            'category' => $cat,
            'course_code' => strtoupper($cat).'-T01',
            'course_name' => ucfirst($cat).' Test Course',
            'credits' => 3,
            'is_active' => true,
        ])->id;
    }

    return [$user, $app, $pools];
}

it('saves course selections via PATCH and persists across reloads', function () {
    [$user, $app, $pools] = makeStudentUserAndApp();

    $this->actingAs($user)
        ->patchJson("/student/applications/{$app->id}/draft", [
            'selections' => [
                'minor' => [$pools['minor']],
                'aec' => [$pools['aec']],
            ],
            'special_request' => 'Hostel preference requested',
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect(ApplicationCourseSelection::where('application_id', $app->id)->count())->toBe(2);
    expect($app->fresh()->special_request)->toBe('Hostel preference requested');
});

it('rejects autosave from a different student', function () {
    [, $app, $pools] = makeStudentUserAndApp();

    $intruder = User::create([
        'name' => 'Intruder',
        'email' => 'intruder@svnc.test',
        'mobile' => '9988770100',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $intruder->assignRole('student');

    $this->actingAs($intruder)
        ->patchJson("/student/applications/{$app->id}/draft", [
            'selections' => ['minor' => [$pools['minor']]],
        ])
        ->assertForbidden();
});

it('rejects autosave on a submitted application', function () {
    [$user, $app, $pools] = makeStudentUserAndApp();
    $app->forceFill(['status' => Application::STATUS_SUBMITTED, 'submitted_at' => now()])->save();

    $this->actingAs($user)
        ->patchJson("/student/applications/{$app->id}/draft", [
            'selections' => ['minor' => [$pools['minor']]],
        ])
        ->assertStatus(422);
});

it('redirects edit page back to list when profile not locked', function () {
    $user = User::create([
        'name' => 'Unlocked Student',
        'email' => 'unlocked-edit@svnc.test',
        'mobile' => '9988770121',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $user->assignRole('student');
    $student = Student::create(['user_id' => $user->id, 'profile_locked' => false]);

    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);
    $session = AcademicSession::factory()->active()->create();
    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_DRAFT,
    ]);

    $this->actingAs($user)
        ->get("/student/applications/{$app->id}")
        ->assertRedirect('/student/applications');
});

it('blocks autosave when profile is not locked', function () {
    $user = User::create([
        'name' => 'No Lock',
        'email' => 'nolock-autosave@svnc.test',
        'mobile' => '9988770122',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $user->assignRole('student');
    $student = Student::create(['user_id' => $user->id, 'profile_locked' => false]);

    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);
    $session = AcademicSession::factory()->active()->create();
    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_DRAFT,
    ]);

    $this->actingAs($user)
        ->patchJson("/student/applications/{$app->id}/draft", ['selections' => []])
        ->assertForbidden();
});

it('blocks starting an application unless profile is locked', function () {
    $user = User::create([
        'name' => 'Locked Out',
        'email' => 'lock@svnc.test',
        'mobile' => '9988770111',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $user->assignRole('student');
    Student::create(['user_id' => $user->id, 'profile_locked' => false]);

    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);
    AcademicSession::factory()->active()->create();

    $this->actingAs($user)
        ->post('/student/applications', ['program_id' => $program->id])
        ->assertRedirect();

    expect(Application::count())->toBe(0);
});
