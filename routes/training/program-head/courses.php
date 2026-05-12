<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\ProgramHead\CourseController;

Route::middleware(['web', 'auth'])
    ->prefix('training/program-head')
    ->name('training.program_head.')
    ->group(function () {
        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [CourseController::class, 'show'])
            ->whereNumber('course')->name('courses.show');
    });
