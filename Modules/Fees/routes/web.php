<?php

use Illuminate\Support\Facades\Route;
use Modules\Fees\Http\Controllers\FeesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('fees', FeesController::class)->names('fees');
});
