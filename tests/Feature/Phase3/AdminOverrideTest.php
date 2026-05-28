<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Academics\Models\Department;
use Modules\Academics\Models\Program;
use Modules\Admissions\Actions\OverrideEligibilityAction;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Students\Models\Student;
use Modules\Users\Database\Seeders\RoleSeeder;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeAdminAndApp(): array
{
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin-ov@svnc.test',
        'mobile' => '9988001234',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $admin->assignRole('admin');

    $student = Student::factory()->create();
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);
    $session = AcademicSession::factory()->active()->create();

    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_SUBMITTED,
        'eligibility_verdict' => Application::VERDICT_FAIL,
        'eligibility_reasons' => ['Below 60% in 12th'],
    ]);

    return [$admin, $app];
}

it('overrides verdict and stores remark + decider', function () {
    [$admin, $app] = makeAdminAndApp();

    app(OverrideEligibilityAction::class)->execute(
        $app,
        $admin,
        Application::VERDICT_OVERRIDE_PASS,
        'Subject minimum threshold relaxed per Dean memo dated 25-May-2026.',
    );

    $app->refresh();
    expect($app->eligibility_verdict)->toBe(Application::VERDICT_OVERRIDE_PASS);
    expect($app->eligibility_decided_by)->toBe($admin->id);
    expect($app->eligibility_decided_at)->not->toBeNull();
    expect($app->eligibility_remark)->toContain('Dean memo');
});

it('writes an activity log entry', function () {
    [$admin, $app] = makeAdminAndApp();

    app(OverrideEligibilityAction::class)->execute(
        $app, $admin, Application::VERDICT_OVERRIDE_FAIL, 'Document discrepancy.',
    );

    $log = Activity::where('log_name', 'application')
        ->where('subject_id', $app->id)
        ->where('description', 'like', 'Eligibility overridden%')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($admin->id);
    expect($log->properties['verdict'])->toBe(Application::VERDICT_OVERRIDE_FAIL);
    expect($log->properties['remark'])->toContain('discrepancy');
});

it('rejects override via http without admin role', function () {
    $student = User::create([
        'name' => 'Stu',
        'email' => 'stu@svnc.test',
        'mobile' => '9988779999',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $student->assignRole('student');

    [, $app] = makeAdminAndApp();

    $this->actingAs($student)
        ->post("/admin/applications/{$app->id}/override-eligibility", [
            'verdict' => Application::VERDICT_OVERRIDE_PASS,
            'remark' => 'I want in',
        ])
        ->assertForbidden();
});

it('requires a remark on override', function () {
    [$admin, $app] = makeAdminAndApp();

    $this->actingAs($admin)
        ->post("/admin/applications/{$app->id}/override-eligibility", [
            'verdict' => Application::VERDICT_OVERRIDE_PASS,
            'remark' => '',
        ])
        ->assertSessionHasErrors('remark');
});
