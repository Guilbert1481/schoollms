<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\School\Settings\Organization\OrganizationController;
use App\Http\Controllers\School\Settings\Organization\PositionController;
use App\Http\Controllers\School\Settings\Organization\OfficeController;
use App\Http\Controllers\School\Settings\Organization\DepartmentController;


Route::middleware(['web', 'auth'])
    ->prefix('school/settings/master-data/organization')
    ->name('school.settings.master-data.organization.')
    ->group(function () {

        // Organization main tabs page
        Route::get('/', [OrganizationController::class, 'indexOrganization'])
            ->name('indexOrganization');

        // Departments
        Route::get('/departments', [DepartmentController::class, 'indexDepartments'])
            ->name('indexDepartments');
        Route::post('/departments', [DepartmentController::class, 'storeDepartments'])
            ->name('storeDepartments');
        Route::put('/departments/{id}', [DepartmentController::class, 'updateDepartments'])
            ->name('updateDepartments');
        Route::delete('/departments/{id}', [DepartmentController::class, 'destroyDepartments'])
            ->name('destroyDepartments');

        // Positions
        Route::get('/positions', [PositionController::class, 'indexPositions'])
            ->name('indexPositions');
        Route::post('/positions', [PositionController::class, 'storePositions'])
            ->name('storePositions');
        Route::put('/positions/{id}', [PositionController::class, 'updatePositions'])
            ->name('updatePositions');
        Route::delete('/positions/{id}', [PositionController::class, 'destroyPositions'])
            ->name('destroyPositions');

        // Offices
        Route::get('/offices', [OfficeController::class, 'indexOffices'])
            ->name('indexOffices');
        Route::post('/offices', [OfficeController::class, 'storeOffices'])
            ->name('storeOffices');
        Route::put('/offices/{id}', [OfficeController::class, 'updateOffices'])
            ->name('updateOffices');
        Route::delete('/offices/{id}', [OfficeController::class, 'destroyOffices'])
            ->name('destroyOffices');

    });