<?php

use Illuminate\Support\Facades\Route;
use Modules\Seats\Http\Controllers\SeatsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('seats', SeatsController::class)->names('seats');
});
