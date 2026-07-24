<?php

return [
    // Prefix for generated application / order numbers — deliberately not the
    // editable site setting, so renaming the college never changes issued numbers.
    'college_code' => env('COLLEGE_CODE', 'SVNC'),

    // Fallback for `site_settings` when a row is missing.
    'identity' => [
        'portal_name' => env('PORTAL_NAME', 'SVNC Admissions'),
        'college_name' => env('COLLEGE_NAME', 'Sardar Vallabhbhai National College'),
        'college_name_hi' => 'सरदार वल्लभभाई नेशनल कॉलेज',
        'college_name_gu' => 'સરદાર વલ્લભભાઈ નેશનલ કૉલેજ',
        'college_short' => env('COLLEGE_SHORT', 'SVNC'),
        'estd_year' => '1956',
        'naac_grade' => 'A+',
        'ugc_status' => '2(f) · 12(B)',
        'city_state' => 'Anand · Gujarat',

        'hero_pitch' => 'Apply online for Under-graduate and Post-graduate programmes under NEP 2020. DigiLocker-verified documents, merit-based seat allocation, transparent fee schedule.',

        'helpline_phone' => '+91 2692 26 13 13',
        'helpline_email' => 'admissions@svnc.ac.in',
        'helpline_hours' => 'Mon–Sat · 10:00–17:00 IST',
        'address_line1' => 'Mota Bazar, Vallabh Vidyanagar Road,',
        'address_line2' => 'Anand — 388 001, Gujarat, India',
        'anti_ragging_phone' => '1800-180-5522',
    ],
];
