<?php

use App\Http\Controllers\Principal\AcademicYearController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:principal'])
    ->prefix('principal/ay-terms')
    ->name('principal.ay-terms.')
    ->controller(AcademicYearController::class)
    ->group(function () {

        Route::get('/', 'index')->name('index');

        Route::post('/academic-years',                  'storeAcademicYear')->name('academic_year.store');
        Route::put('/academic-years/{id}',              'updateAcademicYear')->name('academic_year.update');
        Route::delete('/academic-years/{id}',           'destroyAcademicYear')->name('academic_year.destroy');
        Route::post('/academic-years/{id}/activate',    'activateAcademicYear')->name('academic_year.activate');
        Route::post('/academic-years/{id}/deactivate',  'deactivateAcademicYear')->name('academic_year.deactivate');

        Route::post('/academic-years/{academicYearId}/sessions',         'storeSession')->name('session.store');
        Route::put('/academic-years/{academicYearId}/sessions/{id}',     'updateSession')->name('session.update');
        Route::delete('/academic-years/{academicYearId}/sessions/{id}',  'destroySession')->name('session.destroy');
    });
