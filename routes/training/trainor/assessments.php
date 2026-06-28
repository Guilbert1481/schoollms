<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainor\AssessmentController;

Route::middleware(['web', 'auth', 'role:trainor'])
    ->prefix('training/trainor')
    ->name('training.trainor.')
    ->group(function () {
        Route::get('/assessments', [AssessmentController::class, 'index'])
            ->name('assessments');

        Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])
            ->whereNumber('assessment')
            ->name('assessments.show');

        Route::post('/assessments/{assessment}/grade', [AssessmentController::class, 'grade'])
            ->whereNumber('assessment')
            ->name('assessments.grade');
    });
