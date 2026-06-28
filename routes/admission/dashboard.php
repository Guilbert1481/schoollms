<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\Admissions\AdmissionsController;
use App\Http\Controllers\Staff\Admissions\AdmissionSettingsController;
use App\Http\Controllers\Admin\DashboardController;

Route::middleware([
        'web',
        'auth',
        'role:admission_manager,admin,superadmin,registrar,dean,finance_manager',
        'subscription'
    ])
    ->prefix('admission')
    ->name('admission.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD
        |--------------------------------------------------------------------------
        */

       


        /*
        |--------------------------------------------------------------------------
        | APPLICATIONS
        |--------------------------------------------------------------------------
        */

        Route::get('/applications', [AdmissionsController::class, 'applications'])
            ->name('applications');

        /*
        |--------------------------------------------------------------------------
        | APPLICANT DIRECTORY
        |--------------------------------------------------------------------------
        */
        Route::get('/applicants', [AdmissionsController::class, 'applicants'])
            ->name('applicants');

        // Permanent record of officially enrolled applicants + applicants
        // whose enrollment window has expired.
        Route::get('/applicant-directory', [AdmissionsController::class, 'applicantDirectory'])
            ->name('applicant-directory');


        /*
        |--------------------------------------------------------------------------
        | STUDENTS
        |--------------------------------------------------------------------------
        */

        Route::get('/students', [AdmissionsController::class, 'students'])
            ->name('students');


        /*
        |--------------------------------------------------------------------------
        | INTERVIEWS
        |--------------------------------------------------------------------------
        */

        Route::get('/interviews', [AdmissionsController::class, 'interviews'])
            ->name('interviews');


        /*
        |--------------------------------------------------------------------------
        | DOCUMENTS
        |--------------------------------------------------------------------------
        */

        Route::get('/documents', [AdmissionsController::class, 'documents'])
            ->name('documents');


        /*
        |--------------------------------------------------------------------------
        | ENROLLMENT
        |--------------------------------------------------------------------------
        */

        Route::get('/enrollment', [AdmissionsController::class, 'enrollment'])
            ->name('enrollment');


        /*
        |--------------------------------------------------------------------------
        | REPORTS
        |--------------------------------------------------------------------------
        */

        Route::get('/reports', [AdmissionsController::class, 'reports'])
            ->name('reports');


        /*
        |--------------------------------------------------------------------------
        | COMMUNICATIONS
        |--------------------------------------------------------------------------
        */

        Route::get('/communications', [AdmissionsController::class, 'communications'])
            ->name('communications');


        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        Route::get('/search', [AdmissionsController::class, 'search'])
            ->name('search');


        /*
        |--------------------------------------------------------------------------
        | SETTINGS
        |--------------------------------------------------------------------------
        */

        Route::get('/settings', [AdmissionSettingsController::class, 'edit'])
            ->name('settings');

        Route::post('/settings', [AdmissionSettingsController::class, 'update'])
            ->name('settings.update');

    });
