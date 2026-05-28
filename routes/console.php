<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase 5 — daily reconciliation across all active gateways at 02:30 IST.
Schedule::command('payments:reconcile')->dailyAt('02:30');

// Phase 8 — daily rollover of expired seat allotments at 03:00 IST.
Schedule::command('seats:rollover-expired')->dailyAt('03:00');
