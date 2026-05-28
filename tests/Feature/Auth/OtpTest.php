<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Database\Seeders\RoleSeeder;
use Modules\Users\Models\OtpCode;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeUserWithMobile(string $mobile = '9876500099'): User
{
    $u = User::create([
        'name' => 'OTP User',
        'email' => 'otpuser@svnc.test',
        'mobile' => $mobile,
        'password' => Hash::make('Secret123'),
        'mobile_verified_at' => now(),
        'status' => 'active',
    ]);
    $u->assignRole('student');

    return $u;
}

it('sends an OTP when account exists', function () {
    makeUserWithMobile();

    $this->post('/otp/send', [
        'mobile' => '9876500099',
        'purpose' => OtpCode::PURPOSE_LOGIN,
    ])->assertRedirect();

    expect(OtpCode::where('recipient', '9876500099')
        ->where('purpose', OtpCode::PURPOSE_LOGIN)
        ->exists())->toBeTrue();
});

it('refuses to send a login OTP for unknown mobile', function () {
    $this->post('/otp/send', [
        'mobile' => '9876500088',
        'purpose' => OtpCode::PURPOSE_LOGIN,
    ])->assertSessionHasErrors('mobile');
});

it('signs in via correct OTP', function () {
    $user = makeUserWithMobile();

    $otp = OtpCode::create([
        'user_id' => $user->id,
        'channel' => OtpCode::CHANNEL_SMS,
        'purpose' => OtpCode::PURPOSE_LOGIN,
        'recipient' => $user->mobile,
        'code_hash' => Hash::make('654321'),
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->post('/otp/verify-login', [
        'mobile' => $user->mobile,
        'code' => '654321',
    ])->assertRedirect(route('student.dashboard'));

    $this->assertAuthenticatedAs($user->fresh());
    expect($otp->fresh()->used_at)->not->toBeNull();
});

it('rejects an expired OTP', function () {
    $user = makeUserWithMobile();

    OtpCode::create([
        'user_id' => $user->id,
        'channel' => OtpCode::CHANNEL_SMS,
        'purpose' => OtpCode::PURPOSE_LOGIN,
        'recipient' => $user->mobile,
        'code_hash' => Hash::make('111111'),
        'expires_at' => now()->subMinutes(5),
    ]);

    $this->post('/otp/verify-login', [
        'mobile' => $user->mobile,
        'code' => '111111',
    ])->assertSessionHasErrors('code');

    $this->assertGuest();
});

it('locks the OTP after 5 wrong attempts', function () {
    $user = makeUserWithMobile();

    OtpCode::create([
        'user_id' => $user->id,
        'channel' => OtpCode::CHANNEL_SMS,
        'purpose' => OtpCode::PURPOSE_LOGIN,
        'recipient' => $user->mobile,
        'code_hash' => Hash::make('999888'),
        'expires_at' => now()->addMinutes(10),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post('/otp/verify-login', [
            'mobile' => $user->mobile,
            'code' => '000000',
        ])->assertSessionHasErrors('code');
    }

    $this->post('/otp/verify-login', [
        'mobile' => $user->mobile,
        'code' => '999888',
    ])->assertSessionHasErrors('code');

    $this->assertGuest();
});

it('rejects malformed code', function () {
    $this->post('/otp/verify-login', [
        'mobile' => '9876500099',
        'code' => 'abc',
    ])->assertSessionHasErrors('code');
});
