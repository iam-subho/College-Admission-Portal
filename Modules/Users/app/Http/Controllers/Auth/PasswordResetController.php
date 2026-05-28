<?php

namespace Modules\Users\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Users\Http\Requests\OtpSendRequest;
use Modules\Users\Models\OtpCode;
use Modules\Users\Services\OtpService;

class PasswordResetController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendOtp(OtpSendRequest $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validated();

        if ($data['purpose'] !== OtpCode::PURPOSE_PASSWORD_RESET) {
            throw ValidationException::withMessages(['purpose' => 'Invalid purpose for this endpoint.']);
        }

        $user = User::where('mobile', $data['mobile'])->first();

        if (! $user) {
            return back()->with('flash', [
                'success' => 'If an account exists for that number, an OTP has been sent.',
            ]);
        }

        $otp->generate(
            recipient: $user->mobile,
            channel: OtpCode::CHANNEL_SMS,
            purpose: OtpCode::PURPOSE_PASSWORD_RESET,
            userId: $user->id,
            ip: $request->ip(),
        );

        return back()->with('flash', [
            'success' => 'OTP sent. Check your phone.',
            'otp_sent' => true,
        ]);
    }

    public function reset(Request $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/[A-Z]/', 'regex:/\d/'],
        ]);

        $result = $otp->verify(
            recipient: $data['mobile'],
            code: $data['code'],
            purpose: OtpCode::PURPOSE_PASSWORD_RESET,
            channel: OtpCode::CHANNEL_SMS,
        );

        if (! $result['ok']) {
            throw ValidationException::withMessages(['code' => $result['error']]);
        }

        $user = User::where('mobile', $data['mobile'])->firstOrFail();
        $user->forceFill(['password' => Hash::make($data['password'])])->save();

        return redirect()->route('login')->with('flash', [
            'success' => 'Password reset successful. Please sign in.',
        ]);
    }
}
