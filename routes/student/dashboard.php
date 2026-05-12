<?php

use App\Http\Controllers\Student\DashboardController;
use Illuminate\Support\Facades\Route;

// Individual definition with prefix and name
Route::get('student/dashboard', [DashboardController::class, 'index'])
    ->middleware(['web', 'auth']) // Ensure session/auth is active
    ->name('student.dashboard');