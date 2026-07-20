<?php

use App\Http\Controllers\Teacher\Test\TestBuilder\PrintHubController;
use App\Http\Controllers\Teacher\Test\TestBuilder\PrintTestController;
use Illuminate\Support\Facades\Route;

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher,course_architect,subject_coordinator,trainor'])
    ->group(function () {

        Route::get('tests/{test}/print-hub', [PrintHubController::class, 'show'])
            ->name('tests.print-hub');

        Route::post('tests/{test}/reshuffle', [PrintHubController::class, 'reshuffle'])
            ->name('tests.reshuffle');

        Route::get('tests/{test}/print', [PrintTestController::class, 'print'])
            ->name('tests.print')
            ->missing(function () {
                abort(404, 'No valid test found.');
            });

        Route::get('tests/{test}/answer-key', [PrintTestController::class, 'answerKey'])
            ->name('tests.answer-key')
            ->missing(function () {
                abort(404, 'No valid test found.');
            });

    });
