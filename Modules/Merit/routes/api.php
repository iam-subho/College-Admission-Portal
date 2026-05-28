<?php

use Illuminate\Support\Facades\Route;
use Modules\Merit\Http\Controllers\MeritController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('merits', MeritController::class)->names('merit');
});
