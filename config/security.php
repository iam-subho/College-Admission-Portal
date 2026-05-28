<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security headers (Phase 12 hardening)
    |--------------------------------------------------------------------------
    | Toggle individual headers or override values via env. Sensible defaults
    | for a Laravel + Vite + Inertia + Razorpay setup.
    */

    'headers' => [
        'x_frame_options' => env('SEC_X_FRAME_OPTIONS', 'SAMEORIGIN'),
        'x_content_type_options' => env('SEC_X_CONTENT_TYPE', 'nosniff'),
        'referrer_policy' => env('SEC_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env('SEC_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=()'),

        // Strict-Transport-Security — only emit when explicitly enabled (HTTPS-only sites)
        'hsts_enabled' => env('SEC_HSTS_ENABLED', false),
        'hsts_max_age' => env('SEC_HSTS_MAX_AGE', 31536000),  // 1 year
        'hsts_include_subdomains' => env('SEC_HSTS_INCLUDE_SUBDOMAINS', true),
        'hsts_preload' => env('SEC_HSTS_PRELOAD', false),
    ],

    /*
    | Content-Security-Policy
    |
    | Default policy allows: self for scripts/styles/images, Razorpay
    | checkout.js, college helpdesk + admissions email mailto links,
    | and the Laravel dev server in 'unsafe-inline' allow for HMR.
    | Toggle 'enabled' to false to disable CSP enforcement (report-only mode
    | can be enabled by setting 'report_only' to true).
    */
    'csp' => [
        'enabled' => env('SEC_CSP_ENABLED', true),
        'report_only' => env('SEC_CSP_REPORT_ONLY', true),  // off-by-default in v1 to avoid blocking student flow
        'directives' => [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://checkout.razorpay.com https://*.razorpay.com",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:",
            "connect-src 'self' https://api.razorpay.com https://lumberjack.razorpay.com https://*.razorpay.com",
            "frame-src 'self' https://api.razorpay.com https://*.razorpay.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ],
    ],
];
