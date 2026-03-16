<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\ProgramHead\DashboardController;

// We manually add the prefix and name here since they aren't in bootstrap/app.php
Route::middleware(['auth', 'role:program_head'])
    ->prefix('staff/program-head')
    ->name('staff.program_head.')
    ->group(function () {

        // URL: /staff/program-head/dashboard
        // Full Name: staff.program_head.dashboard.index
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    });