<?php

return [
    'default' => env('SMS_DRIVER', 'log_stub'),

    'log_channel' => env('SMS_LOG_CHANNEL', 'stack'),

    'otp' => [
        'length' => 6,
        'ttl_seconds' => 600,
        'resend_throttle_seconds' => 60,
        'max_per_day' => 10,
    ],

    'templates' => [
        'otp_registration' => env('SMS_TPL_OTP_REGISTRATION', 'STUB_TPL_OTP_REG'),
        'otp_login' => env('SMS_TPL_OTP_LOGIN', 'STUB_TPL_OTP_LOGIN'),
        'otp_password_reset' => env('SMS_TPL_OTP_PWD_RESET', 'STUB_TPL_OTP_PWD'),
    ],
];
