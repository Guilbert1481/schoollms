<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainor\CourseController;

Route::middleware(['web', 'auth', 'role:trainor'])
    ->prefix('training/trainor')
    ->name('training.trainor.')
    ->group(function () {
        Route::get('/courses', [CourseController::class, 'index'])
            ->name('courses');

        Route::get('/courses/{course}', [CourseController::class, 'show'])
            ->whereNumber('course')
            ->name('courses.show');

        Route::post('/courses/{course}/subjects', [CourseController::class, 'storeSubject'])
            ->whereNumber('course')
            ->name('courses.subjects.store');

        Route::delete('/courses/{course}/subjects/{subject}', [CourseController::class, 'destroySubject'])
            ->whereNumber('course')
            ->whereNumber('subject')
            ->name('courses.subjects.destroy');
    });
