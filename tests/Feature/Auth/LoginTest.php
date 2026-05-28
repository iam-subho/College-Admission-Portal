<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeStudent(string $email = 'student@svnc.test', string $password = 'Secret123'): User
{
    $u = User::create([
        'name' => 'Test Student',
        'email' => $email,
        'mobile' => '9876500001',
        'password' => Hash::make($password),
        'status' => 'active',
    ]);
    $u->assignRole('student');

    return $u;
}

it('renders the login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

it('signs the user in with correct credentials', function () {
    $user = makeStudent();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'Secret123',
    ])->assertRedirect(route('student.dashboard'));

    $this->assertAuthenticatedAs($user->fresh());
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects wrong password', function () {
    makeStudent();

    $this->post('/login', [
        'email' => 'student@svnc.test',
        'password' => 'WrongPassword1',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('locks the account after 5 failed attempts', function () {
    makeStudent();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => 'student@svnc.test',
            'password' => 'WrongPassword'.$i,
        ]);
    }

    $this->post('/login', [
        'email' => 'student@svnc.test',
        'password' => 'Secret123',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs out and clears session', function () {
    $user = makeStudent();
    $this->actingAs($user);

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});
