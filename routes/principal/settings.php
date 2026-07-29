<?php

use App\Http\Controllers\Principal\GradeSettingsController;
use App\Http\Controllers\Settings\AttendanceSettingsController;
use App\Http\Controllers\Settings\GradingSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:principal'])
    ->prefix('principal/settings')
    ->name('principal.settings.')
    ->group(function () {
        // Settings → Grades (passing threshold + promotion rule for Form 137).
        Route::get('/grades', [GradeSettingsController::class, 'index'])->name('grades');
        Route::post('/grades', [GradeSettingsController::class, 'update'])->name('grades.update');
        // Settings → Grades → Division: name the basic-ed grading periods (label + names).
        Route::post('/grades/division', [GradeSettingsController::class, 'updateDivision'])
            ->name('grades.division');

        // Settings → Attendance (per basic-ed level). Band fixed to basic here.
        Route::get('/attendance', [AttendanceSettingsController::class, 'index'])
            ->defaults('band', 'basic')->name('attendance');
        Route::post('/attendance', [AttendanceSettingsController::class, 'update'])
            ->defaults('band', 'basic')->name('attendance.update');

        // Settings → Grading scheme (per basic-ed level).
        Route::get('/grading', [GradingSettingsController::class, 'index'])
            ->defaults('band', 'basic')->name('grading');
        Route::post('/grading', [GradingSettingsController::class, 'update'])
            ->defaults('band', 'basic')->name('grading.update');
    });
