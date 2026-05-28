<?php

namespace Modules\Users\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Users\Http\Requests\RegisterRequest;
use Modules\Users\Models\DpdpConsent;
use Modules\Users\Models\OtpCode;
use Modules\Users\Services\OtpService;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'dpdp_version' => config('dpdp.current_version'),
        ]);
    }

    public function store(RegisterRequest $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'],
                'password' => $data['password'],
                'status' => 'active',
            ]);

            $user->assignRole('student');

            app(\Modules\Audit\Services\DpdpConsentRecorder::class)->record(
                scope: DpdpConsent::SCOPE_REGISTRATION,
                userId: $user->id,
                request: $request,
            );

            return $user;
        });

        $otp->generate(
            recipient: $user->mobile,
            channel: OtpCode::CHANNEL_SMS,
            purpose: OtpCode::PURPOSE_REGISTRATION,
            userId: $user->id,
            ip: $request->ip(),
        );

        $request->session()->put('register.pending_mobile', $user->mobile);

        return redirect()->route('register.verify')->with('flash', [
            'success' => 'We sent a 6-digit OTP to your mobile. Enter it to finish signing up.',
        ]);
    }

    public function showVerify(Request $request): Response|RedirectResponse
    {
        $mobile = $request->session()->get('register.pending_mobile');

        if (! $mobile) {
            return redirect()->route('register');
        }

        return Inertia::render('Auth/RegisterVerify', [
            'mobile' => $mobile,
        ]);
    }
}
