<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainee\SessionController;

Route::middleware(['web', 'auth'])
    ->prefix('training/trainee')
    ->name('training.trainee.')
    ->group(function () {
        Route::get('/sessions', [SessionController::class, 'index'])
            ->name('sessions');
    });