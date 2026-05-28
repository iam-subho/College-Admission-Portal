<?php

use Illuminate\Support\Facades\Route;
use Modules\Tests\Http\Controllers\TestsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('tests', TestsController::class)->names('tests');
});
