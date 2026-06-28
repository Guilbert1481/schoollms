<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Base\BaseCrudController;

Route::prefix('school/system/dynamic')
    ->middleware(['web','auth','role:admin,superadmin,academics,admission_manager,registrar,finance_manager,dean,principal,program_head,course_architect,teacher,guidance_counselor,trainor,training_program_head'])
    ->group(function () {

    // STORE
    Route::post('/store/{table}', [BaseCrudController::class, 'storeRecord'])
        ->name('school.system.dynamic.store');

    // GET RECORD
    Route::get('/get/{table}/{id}', [BaseCrudController::class, 'getRecord'])
    ->name('school.system.dynamic.get');

    // UPDATE (POST or PUT; supports _method spoofing and raw PUT)
    Route::match(['put', 'post'], '/update', [BaseCrudController::class, 'updateRecord'])
        ->name('school.system.dynamic.update');

    // DELETE
    Route::delete('/destroy/{table}/{id}', [BaseCrudController::class, 'deleteRecord'])
        ->name('school.system.dynamic.destroy');

});
