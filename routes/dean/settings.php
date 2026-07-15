<?php

use App\Http\Controllers\Settings\AttendanceSettingsController;
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
    });
