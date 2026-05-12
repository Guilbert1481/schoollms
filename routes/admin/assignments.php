<?php

use App\Http\Controllers\Admin\School\Settings\Assignments\CollegesController;
use App\Http\Controllers\Admin\School\Settings\Assignments\ProgramsController;
use App\Http\Controllers\Admin\School\Settings\Assignments\OfficesController;
use App\Http\Controllers\School\Settings\SignatoryController;
use App\Http\Controllers\School\Settings\Organization\DepartmentController;

Route::prefix('admin/assignments')
    ->name('admin.assignments.')
    ->middleware(['auth'])
    ->group(function () {

        // COLLEGES
        Route::get('/colleges', [CollegesController::class, 'indexColleges'])
            ->name('indexColleges');

        // Create a new college
        Route::post('/colleges/create', [CollegesController::class, 'createCollege'])
            ->name('createCollege');

        // Update college info (code/name/description)
        Route::put('/colleges/info/{id}', [CollegesController::class, 'updateCollegeInfo'])
            ->name('updateCollegeInfo');

        // Delete a college
        Route::delete('/colleges/{id}', [CollegesController::class, 'destroyCollege'])
            ->name('destroyCollege');

        // Assign dean
        Route::post('/colleges/store', [CollegesController::class, 'storeColleges'])
            ->name('storeColleges');

        Route::put('/colleges/update/{id}', [CollegesController::class, 'updateColleges'])
            ->name('updateColleges');

        // PROGRAMS
        Route::get('/programs', [ProgramsController::class, 'indexPrograms'])
            ->name('indexPrograms');

        Route::post('/programs/create', [ProgramsController::class, 'createProgram'])
            ->name('createProgram');

        Route::put('/programs/info/{id}', [ProgramsController::class, 'updateProgramInfo'])
            ->name('updateProgramInfo');

        Route::delete('/programs/{id}', [ProgramsController::class, 'destroyProgram'])
            ->name('destroyProgram');

        Route::post('/programs/store', [ProgramsController::class, 'storeProgramhead'])
            ->name('storeProgramhead');

        Route::put('/programs/update/{id}', [ProgramsController::class, 'updateProgramhead'])
            ->name('updateProgramhead');

        // OFFICES
        Route::get('/offices', [OfficesController::class, 'indexOffices'])
            ->name('indexOffices');

        Route::post('/offices/create', [OfficesController::class, 'createOffice'])
            ->name('createOffice');

        Route::put('/offices/info/{id}', [OfficesController::class, 'updateOfficeInfo'])
            ->name('updateOfficeInfo');

        Route::delete('/offices/{id}', [OfficesController::class, 'destroyOffice'])
            ->name('destroyOffice');

        Route::post('/offices/store', [OfficesController::class, 'storeOffices'])
            ->name('storeOffices');

        Route::put('/offices/update/{id}', [OfficesController::class, 'updateOffices'])
            ->name('updateOffices');

        // SIGNATORY
        Route::get('/signatory', [SignatoryController::class, 'indexSignatory'])
            ->name('indexSignatory');

        // DEPARTMENTS
        Route::get('/departments', [DepartmentController::class, 'indexAssignmentsDepartments'])
            ->name('indexDepartments');

});