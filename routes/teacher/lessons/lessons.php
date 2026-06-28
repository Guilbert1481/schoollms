<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\Lesson\LessonController;

Route::middleware(['web', 'auth', 'role:teacher'])
    ->prefix('teacher/lessons')
    ->name('teacher.lessons.')
    ->group(function () {
        Route::get('/', [LessonController::class, 'index'])->name('index');
    });
