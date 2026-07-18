<?php

use App\Http\Controllers\Student\HomeworkController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:student'])
    ->prefix('student/homework')
    ->name('student.homework.')
    ->group(function () {
        Route::get('/', [HomeworkController::class, 'index'])->name('index');
        Route::get('/file/{submission}', [HomeworkController::class, 'downloadFile'])->name('file');
        Route::post('/{homework}/submit', [HomeworkController::class, 'submit'])->name('submit');
    });
