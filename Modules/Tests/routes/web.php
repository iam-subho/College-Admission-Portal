<?php

use Illuminate\Support\Facades\Route;
use Modules\Tests\Http\Controllers\TestsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('tests', TestsController::class)->names('tests');
});
