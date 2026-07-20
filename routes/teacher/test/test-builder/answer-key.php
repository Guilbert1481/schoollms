<?php

use App\Http\Controllers\Teacher\Test\TestBuilder\PrintKeyController;
use Illuminate\Support\Facades\Route;

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher,course_architect,subject_coordinator,trainor'])
    ->group(function () {
        // The dedicated answer key print page (separated controller)
        Route::get('tests/{test}/print-answer-key', [PrintKeyController::class, 'printAnswerKey'])
            ->name('tests.print-answer-key')
            ->missing(function () {
                abort(404, 'No valid test found.');
            });
    });
