<?php

use App\Http\Controllers\AssignableController;

Route::middleware(['auth'])->group(function () {
    Route::get('/assignables/users', [AssignableController::class, 'users'])
        ->name('assignables.users');
});