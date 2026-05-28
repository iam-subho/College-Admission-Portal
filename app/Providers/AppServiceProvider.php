<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    /**
     * Phase 12 hardening — named rate limiters for auth endpoints.
     * Each limiter combines per-IP and per-email keys so a single bad actor
     * can't burn the limit for everyone behind the same NAT.
     */
    protected function configureRateLimiters(): void
    {
        // Login: 10/min per (ip + email). Application-level lockout (5 wrong tries
        // → user account locked) runs first; this limiter is the outer ceiling.
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email', '');
            $key = strtolower($email).'|'.$request->ip();

            return Limit::perMinute(10)->by($key)->response(function () {
                abort(429, 'Too many login attempts. Try again in a minute.');
            });
        });

        // Register: 3 attempts per hour per IP — prevents account stuffing
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(3)->by($request->ip())->response(function () {
                abort(429, 'Too many registration attempts from this network. Try again in an hour.');
            });
        });

        // OTP send: 3 per minute per (mobile/email + ip) — SMS gateways charge per send
        RateLimiter::for('otp-send', function (Request $request) {
            $target = (string) ($request->input('mobile') ?: $request->input('email') ?: '');
            $key = $target.'|'.$request->ip();

            return Limit::perMinute(3)->by($key)->response(function () {
                abort(429, 'Too many OTP requests. Wait a minute before requesting another.');
            });
        });

        // OTP verify: 10/min per (mobile/email + ip). OtpService already invalidates
        // the OTP after 5 wrong attempts; this limiter is the outer ceiling.
        RateLimiter::for('otp-verify', function (Request $request) {
            $target = (string) ($request->input('mobile') ?: $request->input('email') ?: '');
            $key = $target.'|'.$request->ip();

            return Limit::perMinute(10)->by($key)->response(function () {
                abort(429, 'Too many verification attempts.');
            });
        });

        // Password reset: 3 per hour per (email + ip) — abuse hits SMS / mail bill
        RateLimiter::for('password-reset', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));
            $key = $email.'|'.$request->ip();

            return Limit::perHour(3)->by($key)->response(function () {
                abort(429, 'Too many password reset attempts. Try again in an hour.');
            });
        });
    }
}
