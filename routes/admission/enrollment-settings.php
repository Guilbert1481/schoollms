<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\Admissions\EnrollmentSettingsController;

Route::prefix('admission/enrollment-settings')->name('admission.enrollment-settings.')->middleware(['auth', 'role:admission_manager,admin,superadmin'])->group(function () {

    Route::get('/', [EnrollmentSettingsController::class, 'index'])->name('index');
    Route::post('/store', [EnrollmentSettingsController::class, 'store'])->name('store');
    Route::get('/edit/{id}', [EnrollmentSettingsController::class, 'edit'])->name('edit');
    Route::put('/update/{id}', [EnrollmentSettingsController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [EnrollmentSettingsController::class, 'destroy'])->name('delete');

    // Admission Exam tab
    Route::get('/admission-exam', [EnrollmentSettingsController::class, 'admissionExam'])
        ->name('admission-exam');
    Route::put('/admission-exam', [EnrollmentSettingsController::class, 'admissionExamUpdate'])
        ->name('admission-exam.update');
    
    Route::get('/admission/enrollment-settings/update/{id}', function() {
    dd('GET on update route');
});

});