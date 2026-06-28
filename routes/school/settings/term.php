<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\School\Settings\TermController;
use App\Http\Controllers\School\Settings\SubjectOfferingController;

Route::middleware(['web', 'auth', 'role:admin,superadmin'])
    ->prefix('school/settings')
    ->group(function () {

        Route::post('/academic-years/{academicYearId}/terms', [TermController::class, 'storeTerm'])
            ->name('school.settings.term.store');

        Route::put('/academic-years/{academicYearId}/terms/{id}', [TermController::class, 'updateTerm'])
            ->name('school.settings.term.update');

        Route::delete('/academic-years/{academicYearId}/terms/{id}', [TermController::class, 'destroyTerm'])
            ->name('school.settings.term.destroy');

        Route::post('/academic-years/{academicYearId}/terms/{id}/activate', [TermController::class, 'activateTerm'])
            ->name('school.settings.term.activate');

        Route::post('/academic-years/{academicYearId}/terms/{id}/deactivate', [TermController::class, 'deactivateTerm'])
            ->name('school.settings.term.deactivate');

        // Subject offerings (per term)
        Route::post('/subject-offerings', [SubjectOfferingController::class, 'store'])
            ->name('subject-offerings.store');

        Route::delete('/subject-offerings/{id}', [SubjectOfferingController::class, 'destroy'])
            ->name('subject-offerings.destroy');

    });