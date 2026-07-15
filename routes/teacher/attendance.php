<?php

use App\Http\Controllers\Teacher\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:teacher'])
    ->prefix('teacher/attendance')
    ->name('teacher.attendance.')
    ->group(function () {
        // Pick an advisory section (daily) or a class (per-subject session),
        // load the roster for a date, and save marks.
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
    });
