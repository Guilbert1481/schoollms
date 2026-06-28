<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\DashboardController; 

// Individual definition with prefix and name
Route::get('teacher/dashboard', [DashboardController::class, 'index'])
    ->middleware(['web', 'auth', 'role:teacher,trainor']) // Ensure session/auth is active
    ->name('teacher.dashboard');