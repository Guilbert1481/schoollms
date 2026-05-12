<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\Admissions\EnrollmentSettingsController;

Route::prefix('admission/enrollment-settings')->name('admission.enrollment-settings.')->group(function () {

    Route::get('/', [EnrollmentSettingsController::class, 'index'])->name('index');
    Route::post('/store', [EnrollmentSettingsController::class, 'store'])->name('store');
    Route::get('/edit/{id}', [EnrollmentSettingsController::class, 'edit'])->name('edit');
    Route::put('/update/{id}', [EnrollmentSettingsController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [EnrollmentSettingsController::class, 'destroy'])->name('delete');
    
    Route::get('/admission/enrollment-settings/update/{id}', function() {
    dd('GET on update route');
});

});