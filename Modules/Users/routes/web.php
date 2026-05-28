<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\Auth\LoginController;
use Modules\Users\Http\Controllers\Auth\OtpController;
use Modules\Users\Http\Controllers\Auth\PasswordResetController;
use Modules\Users\Http\Controllers\Auth\RegisterController;

Route::middleware(['guest'])->group(function () {
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->middleware('throttle:register');
    Route::get('register/verify', [RegisterController::class, 'showVerify'])->name('register.verify');
    Route::post('register/verify', [OtpController::class, 'verifyRegistration'])
        ->name('register.verify.submit')->middleware('throttle:otp-verify');

    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:login');

    Route::get('login/otp', [OtpController::class, 'showLogin'])->name('login.otp');
    Route::post('otp/send', [OtpController::class, 'send'])->name('otp.send')->middleware('throttle:otp-send');
    Route::post('otp/verify-login', [OtpController::class, 'verifyLogin'])
        ->name('otp.verify-login')->middleware('throttle:otp-verify');

    Route::get('password/forgot', [PasswordResetController::class, 'create'])->name('password.forgot');
    Route::post('password/forgot/send-otp', [PasswordResetController::class, 'sendOtp'])
        ->name('password.forgot.send-otp')->middleware('throttle:password-reset');
    Route::post('password/forgot/reset', [PasswordResetController::class, 'reset'])
        ->name('password.forgot.reset')->middleware('throttle:password-reset');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});
