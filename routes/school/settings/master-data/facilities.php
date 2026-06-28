<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\School\Settings\MasterData\FacilitiesController;

Route::prefix('school/settings')
    ->name('school.settings.')
    ->middleware(['auth', 'role:admin,superadmin'])
    ->group(function () {

        Route::get('/master-data/facilities', [FacilitiesController::class, 'index'])
            ->name('master.facilities.index');

        Route::post('/master-data/facilities/store', [FacilitiesController::class, 'store'])
            ->name('master.facilities.store');

        Route::post('/master-data/facilities/update', [FacilitiesController::class, 'update'])
            ->name('master.facilities.update');

        Route::delete('/master-data/facilities/delete/{id}', [FacilitiesController::class, 'delete'])
            ->name('master.facilities.delete');

});