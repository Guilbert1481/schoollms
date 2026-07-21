<?php

use App\Http\Controllers\Teacher\StudentRosterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:teacher'])
    ->prefix('teacher/students')
    ->name('teacher.students.')
    ->group(function () {
        Route::get('/', [StudentRosterController::class, 'index'])->name('index');
    });
