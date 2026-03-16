<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AssignmentManagementController;

Route::middleware(['web', 'auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/assignments', 
            [AssignmentManagementController::class, 'index']
        )->name('assignments.index');

        Route::post('/assign-dean', 
            [AssignmentManagementController::class, 'assignDean']
        )->name('assign.dean');

        Route::post('/assign-program-head', 
            [AssignmentManagementController::class, 'assignProgramHead']
        )->name('assign.programHead');

    });