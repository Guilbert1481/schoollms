<?php

use App\Http\Controllers\Settings\AttendanceSettingsController;
use App\Http\Controllers\Settings\GradingSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:dean,admin,superadmin'])
    ->prefix('dean/settings')
    ->name('dean.settings.')
    ->group(function () {
        // Settings → Attendance (per higher-ed level). Band fixed to higher here.
        Route::get('/attendance', [AttendanceSettingsController::class, 'index'])
            ->defaults('band', 'higher')->name('attendance');
        Route::post('/attendance', [AttendanceSettingsController::class, 'update'])
            ->defaults('band', 'higher')->name('attendance.update');

        // Settings → Grading scheme (per higher-ed level).
        Route::get('/grading', [GradingSettingsController::class, 'index'])
            ->defaults('band', 'higher')->name('grading');
        Route::post('/grading', [GradingSettingsController::class, 'update'])
            ->defaults('band', 'higher')->name('grading.update');
    });
