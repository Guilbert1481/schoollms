<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dean\DashboardController;

Route::middleware(['auth'])
    ->prefix('dean')
    ->name('dean.')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

    });