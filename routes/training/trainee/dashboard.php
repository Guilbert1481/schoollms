<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainee\DashboardController;

Route::middleware(['web', 'auth'])
    ->prefix('training/trainee')
    ->name('training.trainee.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
    });