<?php

use Modules\Users\Database\Seeders\PermissionSeeder;
use Modules\Users\Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
});

it('seeds the four core roles', function () {
    expect(Role::pluck('name')->all())
        ->toContain('super_admin', 'admin', 'staff', 'student');
});

it('grants super_admin every permission', function () {
    $superAdmin = Role::findByName('super_admin', 'web');
    expect($superAdmin->permissions()->count())
        ->toBe(count(PermissionSeeder::PERMISSIONS));
});

it('grants student only the upload permission', function () {
    $student = Role::findByName('student', 'web');
    expect($student->permissions()->pluck('name')->all())
        ->toBe(['documents.upload']);
});
