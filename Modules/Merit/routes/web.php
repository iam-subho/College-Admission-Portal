<?php

use Illuminate\Support\Facades\Route;
use Modules\Merit\Http\Controllers\MeritController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('merits', MeritController::class)->names('merit');
});
