<?php

use App\Http\Controllers\Student\DashboardController;
use Illuminate\Support\Facades\Route;

// Individual definition with prefix and name
Route::get('student/dashboard', [DashboardController::class, 'index'])
    ->middleware(['web', 'auth', 'role:student']) // Ensure session/auth is active
    ->name('student.dashboard');

// JSON month events for the dashboard calendar's prev/next navigation.
Route::get('student/dashboard/calendar', [DashboardController::class, 'calendar'])
    ->middleware(['web', 'auth', 'role:student'])
    ->name('student.dashboard.calendar');