<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeUserWithRole(string $role): User
{
    $u = User::create([
        'name' => ucfirst($role),
        'email' => "{$role}@svnc.test",
        'mobile' => '99880'.random_int(10000, 99999),
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $u->assignRole($role);

    return $u;
}

it('redirects guests away from /admin', function () {
    $this->get('/admin')->assertRedirect('/login');
    $this->get('/admin/sessions')->assertRedirect('/login');
});

it('blocks student from /admin', function () {
    $this->actingAs(makeUserWithRole('student'))
        ->get('/admin')
        ->assertForbidden();

    $this->get('/admin/sessions')->assertForbidden();
    $this->get('/admin/programmes')->assertForbidden();
});

it('grants staff access to the admin panel for operational pages', function () {
    $this->actingAs(makeUserWithRole('staff'));

    $this->get('/admin')->assertOk();
    $this->get('/admin/applications')->assertOk();
    $this->get('/admin/documents')->assertOk();
    $this->get('/admin/reports')->assertOk();
    $this->get('/admin/audit-log')->assertOk();
});

it('blocks staff from admin-only setup + config pages', function () {
    $this->actingAs(makeUserWithRole('staff'));

    $this->get('/admin/sessions')->assertForbidden();
    $this->get('/admin/programmes')->assertForbidden();
    $this->get('/admin/fees')->assertForbidden();
    $this->get('/admin/gateways')->assertForbidden();
    $this->get('/admin/refunds')->assertForbidden();
    $this->get('/admin/dpdp-consents')->assertForbidden();
    $this->get('/admin/notices')->assertForbidden();
    $this->get('/admin/spot-admission')->assertForbidden();
});

it('grants admin access to /admin', function () {
    $this->actingAs(makeUserWithRole('admin'))
        ->get('/admin')
        ->assertOk();

    $this->get('/admin/sessions')->assertOk();
    $this->get('/admin/programmes')->assertOk();
    $this->get('/admin/departments')->assertOk();
    $this->get('/admin/reservation-categories')->assertOk();
    $this->get('/admin/fees')->assertOk();
});

it('grants super_admin access to /admin', function () {
    $this->actingAs(makeUserWithRole('super_admin'))
        ->get('/admin')
        ->assertOk();
});
