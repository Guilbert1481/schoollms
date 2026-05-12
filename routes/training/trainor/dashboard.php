<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainor\DashboardController;

Route::middleware(['web', 'auth'])
    ->prefix('training/trainor')
    ->name('training.trainor.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
    });
