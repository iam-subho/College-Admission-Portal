<?php

use Illuminate\Support\Facades\Route;
use Modules\Seats\Http\Controllers\SeatsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('seats', SeatsController::class)->names('seats');
});
