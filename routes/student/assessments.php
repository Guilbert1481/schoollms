<?php

use App\Http\Controllers\Student\AssessmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| STUDENT — ONLINE ASSESSMENTS (take flow)
|--------------------------------------------------------------------------
| The student side of online tests: start page → take screen → autosave →
| submit → result. Availability, enrolment, and the server deadline are all
| re-checked in the controller on every action.
*/

Route::middleware(['web', 'auth', 'role:student'])
    ->prefix('student/assessments')
    ->name('student.assessments.')
    ->group(function () {
        Route::get('/', [AssessmentController::class, 'index'])->name('index');
        Route::get('{test}/start', [AssessmentController::class, 'start'])->name('start');
        Route::post('{test}/begin', [AssessmentController::class, 'begin'])->name('begin');
        Route::get('{test}/take', [AssessmentController::class, 'take'])->name('take');
        Route::get('{test}/result', [AssessmentController::class, 'result'])->name('result');

        Route::post('attempt/{attempt}/answer', [AssessmentController::class, 'answer'])->name('answer');
        Route::post('attempt/{attempt}/event', [AssessmentController::class, 'event'])->name('event');
        Route::post('attempt/{attempt}/submit', [AssessmentController::class, 'submit'])->name('submit');
    });
