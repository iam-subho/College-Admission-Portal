<?php

return [
    'default' => env('DIGILOCKER_DRIVER', 'stub'),

    'enabled' => (bool) env('DIGILOCKER_ENABLED', true),

    'sandbox_mode' => (bool) env('DIGILOCKER_SANDBOX', true),

    'client_id' => env('DIGILOCKER_CLIENT_ID'),
    'client_secret' => env('DIGILOCKER_CLIENT_SECRET'),

    'redirect_uri' => env('DIGILOCKER_REDIRECT_URI', '/digilocker/callback'),
];
