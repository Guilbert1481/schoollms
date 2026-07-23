<?php

use App\Http\Controllers\Student\AttendanceController;
use App\Http\Controllers\Student\ScanAttendanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:student'])
    ->group(function () {
        // Landed on by scanning the teacher's live QR.
        Route::get('/attendance/scan', [ScanAttendanceController::class, 'scan'])->name('attendance.scan');
    });

// Academics → Attendance: the student's own attendance history.
Route::middleware(['web', 'auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    });
