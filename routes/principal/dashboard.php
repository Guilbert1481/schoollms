<?php

use App\Http\Controllers\Principal\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:principal'])
    ->prefix('principal')
    ->name('principal.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
