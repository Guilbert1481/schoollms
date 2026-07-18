<?php

use App\Http\Controllers\Teacher\HomeworkController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:teacher'])
    ->prefix('teacher/homework')
    ->name('teacher.homework.')
    ->group(function () {
        Route::get('/', [HomeworkController::class, 'index'])->name('index');
        Route::post('/', [HomeworkController::class, 'store'])->name('store');
        Route::get('/file/{submission}', [HomeworkController::class, 'downloadFile'])->name('file');
        Route::get('/{homework}', [HomeworkController::class, 'show'])->name('show');
        Route::post('/{homework}/grade', [HomeworkController::class, 'grade'])->name('grade');
    });
