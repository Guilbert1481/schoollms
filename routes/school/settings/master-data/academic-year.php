<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\School\Settings\AcademicYearController;

/*
|--------------------------------------------------------------------------
| Academic Year Settings Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])
    ->prefix('school/settings/master-data')
    ->name('school.settings.master-data.')
    ->group(function () {

        Route::get('/', [AcademicYearController::class, 'indexAcademicYear'])
            ->name('academic_year.index');

        Route::post('/academic-years', [AcademicYearController::class, 'storeAcademicYear'])
            ->name('academic_year.store');

        Route::put('/academic-years/{id}', [AcademicYearController::class, 'updateAcademicYear'])
            ->name('academic_year.update');

        Route::delete('/academic-years/{id}', [AcademicYearController::class, 'destroyAcademicYear'])
            ->name('academic_year.destroy');

        Route::post('/academic-years/{id}/activate', [AcademicYearController::class, 'activateAcademicYear'])
            ->name('academic_year.activate');

        Route::post('/academic-years/{id}/deactivate', [AcademicYearController::class, 'deactivateAcademicYear'])
            ->name('academic_year.deactivate');

    });