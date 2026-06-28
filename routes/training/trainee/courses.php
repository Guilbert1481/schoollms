<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainee\CourseController;

Route::middleware(['web', 'auth', 'role:trainee'])
    ->prefix('training/trainee')
    ->name('training.trainee.')
    ->group(function () {
        Route::get('/available-courses', [CourseController::class, 'available'])
            ->name('available-courses');

        Route::get('/available-courses/{session}/programs', [CourseController::class, 'availablePrograms'])
            ->whereNumber('session')
            ->name('available-courses.programs');

        Route::get('/available-courses/{session}/programs/{program}', [CourseController::class, 'availableProgramSubjects'])
            ->whereNumber('session')
            ->name('available-courses.program.subjects');

        Route::get('/courses', [CourseController::class, 'index'])
            ->name('courses');

        Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll'])
            ->whereNumber('course')
            ->name('courses.enroll');

        Route::get('/courses/enrollment/{enrollment}/payment', [CourseController::class, 'payment'])
            ->whereNumber('enrollment')
            ->name('courses.payment');

        Route::post('/courses/enrollment/{enrollment}/payment', [CourseController::class, 'confirmPayment'])
            ->whereNumber('enrollment')
            ->name('courses.payment.confirm');
    });