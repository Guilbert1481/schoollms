<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\DashboardController;

Route::middleware(['auth:staff', 'role:admission'])
    ->prefix('staff')
    ->group(function () {

        Route::get('/admissions/dashboard', [DashboardController::class, 'index'])
            ->name('staff.admissions.dashboard');

    });
