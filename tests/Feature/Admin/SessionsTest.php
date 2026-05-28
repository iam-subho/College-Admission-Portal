<?php

use App\Models\User;
use Modules\Admissions\Actions\ActivateSessionAction;
use Modules\Admissions\Models\AcademicSession;
use Modules\Users\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeAdmin(): User
{
    $u = User::create([
        'name' => 'Admin',
        'email' => 'admin@svnc.test',
        'mobile' => '9988001100',
        'password' => bcrypt('Secret123'),
        'status' => 'active',
    ]);
    $u->assignRole('admin');

    return $u;
}

it('activating a session deactivates all others', function () {
    $a = AcademicSession::factory()->active()->create(['code' => 'A']);
    $b = AcademicSession::factory()->create(['code' => 'B']);

    expect($a->fresh()->is_active)->toBeTrue();
    expect($b->fresh()->is_active)->toBeFalse();

    app(ActivateSessionAction::class)->execute($b);

    expect($a->fresh()->is_active)->toBeFalse();
    expect($a->fresh()->status)->toBe(AcademicSession::STATUS_ARCHIVED);
    expect($b->fresh()->is_active)->toBeTrue();
    expect($b->fresh()->status)->toBe(AcademicSession::STATUS_ACTIVE);
});

it('admin can create a session via http', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)->post(route('admin.sessions.store'), [
        'code' => '2027-28',
        'name' => 'Session 2027-28',
        'commencement_date' => '2027-07-01',
    ])->assertRedirect();

    expect(AcademicSession::where('code', '2027-28')->exists())->toBeTrue();
});

it('admin can archive an active session', function () {
    $admin = makeAdmin();
    $s = AcademicSession::factory()->active()->create();

    $this->actingAs($admin)->post(route('admin.sessions.archive', $s))->assertRedirect();

    $s->refresh();
    expect($s->is_active)->toBeFalse();
    expect($s->status)->toBe(AcademicSession::STATUS_ARCHIVED);
});

it('only one session can be active at any time after multiple activations', function () {
    $sessions = AcademicSession::factory()->count(3)->create();
    $action = app(ActivateSessionAction::class);

    foreach ($sessions as $s) {
        $action->execute($s);
    }

    expect(AcademicSession::where('is_active', true)->count())->toBe(1);
    expect(AcademicSession::where('is_active', true)->first()->id)->toBe($sessions->last()->id);
});
