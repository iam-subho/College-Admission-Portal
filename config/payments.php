<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Driver registry
    |--------------------------------------------------------------------------
    | Map gateway `code` (the value stored in `payment_gateways.code`) to its
    | driver class. Adding a new gateway = drop the driver file in place and
    | register it here, then insert a row in `payment_gateways`.
    */
    'drivers' => [
        'razorpay' => \Modules\Payments\Services\Gateways\RazorpayDriver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Application fee fallback (in paise)
    |--------------------------------------------------------------------------
    | Used by FeeResolver when no fee_structure / APPLICATION row exists.
    | Default is ₹500.
    */
    'default_application_fee' => env('PAYMENTS_DEFAULT_APPLICATION_FEE', 50000),

    /*
    |--------------------------------------------------------------------------
    | GST applied to convenience fee (percent)
    |--------------------------------------------------------------------------
    */
    'gst_percent' => env('PAYMENTS_GST_PERCENT', 18),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */
    'currency' => 'INR',

    /*
    |--------------------------------------------------------------------------
    | Order expiry window (minutes)
    |--------------------------------------------------------------------------
    | Initiated orders auto-expire after this many minutes if not paid.
    */
    'order_ttl_minutes' => 30,
];
