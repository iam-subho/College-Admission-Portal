<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Database\Seeders\RoleSeeder;
use Modules\Users\Models\DpdpConsent;
use Modules\Users\Models\OtpCode;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('renders the register page', function () {
    $this->get('/register')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Auth/Register'));
});

it('creates a user, records dpdp consent, and dispatches an OTP', function () {
    $payload = [
        'name' => 'Aarav Patel',
        'email' => 'aarav@example.test',
        'mobile' => '9876543210',
        'password' => 'Secret123',
        'password_confirmation' => 'Secret123',
        'dpdp_consent' => true,
    ];

    $this->post('/register', $payload)
        ->assertRedirect('/register/verify');

    $user = User::where('email', 'aarav@example.test')->first();
    expect($user)->not->toBeNull();
    expect($user->mobile)->toBe('9876543210');
    expect($user->mobile_verified_at)->toBeNull();
    expect($user->hasRole('student'))->toBeTrue();
    expect(Hash::check('Secret123', $user->password))->toBeTrue();

    expect(DpdpConsent::where('user_id', $user->id)->exists())->toBeTrue();

    $otp = OtpCode::where('recipient', '9876543210')
        ->where('purpose', OtpCode::PURPOSE_REGISTRATION)
        ->first();
    expect($otp)->not->toBeNull();
    expect($otp->code_hash)->not->toBeEmpty();
});

it('blocks registration without DPDP consent', function () {
    $payload = [
        'name' => 'No Consent',
        'email' => 'noconsent@example.test',
        'mobile' => '9876543211',
        'password' => 'Secret123',
        'password_confirmation' => 'Secret123',
        'dpdp_consent' => false,
    ];

    $this->post('/register', $payload)
        ->assertSessionHasErrors('dpdp_consent');

    expect(User::where('email', 'noconsent@example.test')->exists())->toBeFalse();
});

it('rejects an invalid Indian mobile number', function () {
    $this->post('/register', [
        'name' => 'Bad Mobile',
        'email' => 'badmobile@example.test',
        'mobile' => '1234567890',
        'password' => 'Secret123',
        'password_confirmation' => 'Secret123',
        'dpdp_consent' => true,
    ])->assertSessionHasErrors('mobile');
});

it('completes mobile verification with the correct OTP', function () {
    $this->post('/register', [
        'name' => 'Verify Me',
        'email' => 'verify@example.test',
        'mobile' => '9876543212',
        'password' => 'Secret123',
        'password_confirmation' => 'Secret123',
        'dpdp_consent' => true,
    ]);

    $otp = OtpCode::where('recipient', '9876543212')->latest('id')->first();
    expect($otp)->not->toBeNull();

    // OTP plaintext is hashed in storage; rewrite to a known value to verify the flow.
    $known = '123456';
    $otp->forceFill([
        'code_hash' => Hash::make($known),
        'attempts' => 0,
        'used_at' => null,
    ])->save();

    $this->post('/register/verify', [
        'mobile' => '9876543212',
        'code' => $known,
    ])->assertRedirect();

    $user = User::where('mobile', '9876543212')->first();
    expect($user->mobile_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});
