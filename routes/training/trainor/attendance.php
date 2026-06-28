<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainor\AttendanceController;

Route::middleware(['web', 'auth', 'role:trainor'])
    ->prefix('training/trainor')
    ->name('training.trainor.')
    ->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])
            ->name('attendance');

        Route::post('/attendance', [AttendanceController::class, 'store'])
            ->name('attendance.store');
    });
