<?php

use App\Http\Controllers\Staff\Registrar\EnrollmentValidationController;
use App\Http\Controllers\Staff\Registrar\SubjectCreditController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('registrar')
    ->name('registrar.')
    ->group(function () {

        // 1. Validate Enrollment
        Route::get('enrollments', [EnrollmentValidationController::class, 'index'])
            ->name('enrollments.index');
        Route::get('enrollments/{enrollment}', [EnrollmentValidationController::class, 'show'])
            ->name('enrollments.show');
        Route::post('enrollments/{enrollment}/validate', [EnrollmentValidationController::class, 'validateEnrollment'])
            ->name('enrollments.validate');
        Route::post('enrollments/{enrollment}/reject', [EnrollmentValidationController::class, 'reject'])
            ->name('enrollments.reject');

        // 2. Evaluate Subject Credits (transferees / irregulars)
        Route::get('subject-credits', [SubjectCreditController::class, 'index'])
            ->name('subject-credits.index');
        Route::post('subject-credits', [SubjectCreditController::class, 'store'])
            ->name('subject-credits.store');
        Route::post('subject-credits/{evaluation}/decide', [SubjectCreditController::class, 'decide'])
            ->name('subject-credits.decide');
        Route::delete('subject-credits/{evaluation}', [SubjectCreditController::class, 'destroy'])
            ->name('subject-credits.destroy');
    });
