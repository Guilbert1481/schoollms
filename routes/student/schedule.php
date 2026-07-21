<?php

use App\Http\Controllers\Student\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('student/schedule', [ScheduleController::class, 'index'])
    ->middleware(['web', 'auth', 'role:student'])
    ->name('student.schedule.index');
