<?php

namespace Modules\Users\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Users\Http\Requests\OtpSendRequest;
use Modules\Users\Http\Requests\OtpVerifyRequest;
use Modules\Users\Models\OtpCode;
use Modules\Users\Services\OtpService;

class OtpController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Auth/OtpLogin');
    }

    public function send(OtpSendRequest $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validated();
        $purpose = $data['purpose'];

        $user = User::where('mobile', $data['mobile'])->first();

        if ($purpose === OtpCode::PURPOSE_LOGIN && ! $user) {
            throw ValidationException::withMessages([
                'mobile' => 'No account is registered with this mobile number.',
            ]);
        }

        $result = $otp->generate(
            recipient: $data['mobile'],
            channel: OtpCode::CHANNEL_SMS,
            purpose: $purpose,
            userId: $user?->id,
            ip: $request->ip(),
        );

        if (! $result['ok']) {
            throw ValidationException::withMessages([
                'mobile' => $result['error'],
            ]);
        }

        return back()->with('flash', [
            'success' => 'OTP sent. Check your phone.',
            'otp_sent' => true,
        ]);
    }

    public function verifyLogin(OtpVerifyRequest $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validated();

        $result = $otp->verify(
            recipient: $data['mobile'],
            code: $data['code'],
            purpose: OtpCode::PURPOSE_LOGIN,
            channel: OtpCode::CHANNEL_SMS,
        );

        if (! $result['ok']) {
            throw ValidationException::withMessages(['code' => $result['error']]);
        }

        $user = User::where('mobile', $data['mobile'])->firstOrFail();

        if (! $user->mobile_verified_at) {
            $user->forceFill(['mobile_verified_at' => now()])->save();
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route($user->dashboardRoute()));
    }

    public function verifyRegistration(OtpVerifyRequest $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validated();

        $result = $otp->verify(
            recipient: $data['mobile'],
            code: $data['code'],
            purpose: OtpCode::PURPOSE_REGISTRATION,
            channel: OtpCode::CHANNEL_SMS,
        );

        if (! $result['ok']) {
            throw ValidationException::withMessages(['code' => $result['error']]);
        }

        $user = User::where('mobile', $data['mobile'])->firstOrFail();
        $user->forceFill(['mobile_verified_at' => now()])->save();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('register.pending_mobile');
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->route($user->dashboardRoute());
    }
}
