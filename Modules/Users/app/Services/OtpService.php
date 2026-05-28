<?php

namespace Modules\Users\Services;

use App\Services\Notifications\Sms\SmsManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\Users\Models\OtpCode;

class OtpService
{
    public function __construct(
        protected SmsManager $smsManager,
    ) {}

    /**
     * @return array{ok: bool, error?: string, code_for_log?: string}
     */
    public function generate(
        string $recipient,
        string $channel,
        string $purpose,
        ?int $userId = null,
        ?string $ip = null,
    ): array {
        $throttleKey = "otp:{$channel}:{$recipient}:{$purpose}";

        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            return [
                'ok' => false,
                'error' => 'Please wait before requesting another OTP.',
            ];
        }

        $dailyKey = "otp-daily:{$channel}:{$recipient}";
        $maxPerDay = (int) config('sms.otp.max_per_day', 10);

        if (RateLimiter::tooManyAttempts($dailyKey, $maxPerDay)) {
            return [
                'ok' => false,
                'error' => 'Daily OTP limit reached. Try again tomorrow.',
            ];
        }

        $code = $this->generateCode();
        $ttl = (int) config('sms.otp.ttl_seconds', 600);

        OtpCode::create([
            'user_id' => $userId,
            'channel' => $channel,
            'purpose' => $purpose,
            'recipient' => $recipient,
            'code_hash' => Hash::make($code),
            'expires_at' => Carbon::now()->addSeconds($ttl),
            'ip' => $ip,
        ]);

        $this->dispatch($recipient, $channel, $purpose, $code);

        RateLimiter::hit($throttleKey, (int) config('sms.otp.resend_throttle_seconds', 60));
        RateLimiter::hit($dailyKey, 24 * 60 * 60);

        return [
            'ok' => true,
            'code_for_log' => app()->environment(['local', 'testing']) ? $code : null,
        ];
    }

    /** Universal dev/staging master OTP — accepted everywhere outside production. */
    public const DEV_MASTER_CODE = '123456';

    /**
     * @return array{ok: bool, otp?: OtpCode, error?: string}
     */
    public function verify(string $recipient, string $code, string $purpose, string $channel): array
    {
        $otp = OtpCode::where('recipient', $recipient)
            ->where('purpose', $purpose)
            ->where('channel', $channel)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            return ['ok' => false, 'error' => 'No OTP request found. Please request a new code.'];
        }

        // Non-production master OTP — bypasses expiry / attempt limits so dev /
        // staging testing doesn't need to read SMS logs or DB. NEVER active in prod.
        if ($code === self::DEV_MASTER_CODE && ! app()->environment('production')) {
            $otp->forceFill(['used_at' => now()])->save();

            return ['ok' => true, 'otp' => $otp];
        }

        if ($otp->isExpired()) {
            return ['ok' => false, 'error' => 'OTP expired. Please request a new code.'];
        }

        if ($otp->isExhausted()) {
            return ['ok' => false, 'error' => 'Too many incorrect attempts. Please request a new code.'];
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return ['ok' => false, 'error' => 'Incorrect OTP.'];
        }

        $otp->forceFill(['used_at' => now()])->save();

        return ['ok' => true, 'otp' => $otp];
    }

    protected function generateCode(): string
    {
        $length = (int) config('sms.otp.length', 6);

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    protected function dispatch(string $recipient, string $channel, string $purpose, string $code): void
    {
        if ($channel === OtpCode::CHANNEL_SMS) {
            $template = config("sms.templates.otp_{$purpose}") ?? config('sms.templates.otp_login');
            $this->smsManager->driver()->send($recipient, $template, [
                'code' => $code,
                'ttl_minutes' => (int) ceil(config('sms.otp.ttl_seconds', 600) / 60),
            ]);

            return;
        }

        if ($channel === OtpCode::CHANNEL_EMAIL) {
            try {
                Mail::raw("Your SVNC Admissions OTP is: {$code}\nValid for ".((int) ceil(config('sms.otp.ttl_seconds', 600) / 60)).' minutes.', function ($m) use ($recipient) {
                    $m->to($recipient)->subject('SVNC Admissions OTP');
                });
            } catch (\Throwable $e) {
                Log::warning('[otp] email send failed', ['error' => $e->getMessage()]);
            }

            return;
        }

        Log::warning('[otp] unknown channel', ['channel' => $channel]);
    }
}
