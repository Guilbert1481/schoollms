<?php

use App\Http\Controllers\Tools\VideoConference\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    });
