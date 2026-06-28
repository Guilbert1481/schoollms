<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainor\SessionController;

Route::middleware(['web', 'auth', 'role:trainor'])
    ->prefix('training/trainor')
    ->name('training.trainor.')
    ->group(function () {
        Route::get('/sessions', [SessionController::class, 'index'])
            ->name('sessions');

        Route::get('/sessions/{session}', [SessionController::class, 'show'])
            ->whereNumber('session')
            ->name('sessions.show');
    });
