<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainee\SessionController;

Route::middleware(['web', 'auth', 'role:trainee'])
    ->prefix('training/trainee')
    ->name('training.trainee.')
    ->group(function () {
        Route::get('/progress', [SessionController::class, 'progress'])
            ->name('progress');
    });