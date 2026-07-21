<?php

use App\Http\Controllers\Student\Services\ClearanceController;
use App\Http\Controllers\Student\Services\DocumentRequestController;
use App\Http\Controllers\Student\Services\ModalityRequestController;
use Illuminate\Support\Facades\Route;

// Student Services: modality change requests, document requests, clearance.
Route::middleware(['web', 'auth', 'role:student'])
    ->prefix('student/services')
    ->name('student.services.')
    ->group(function () {
        Route::get('modality', [ModalityRequestController::class, 'index'])->name('modality.index');
        Route::post('modality', [ModalityRequestController::class, 'store'])->name('modality.store');

        Route::get('documents', [DocumentRequestController::class, 'index'])->name('documents.index');
        Route::post('documents', [DocumentRequestController::class, 'store'])->name('documents.store');

        Route::get('clearance', [ClearanceController::class, 'index'])->name('clearance.index');
        Route::post('clearance', [ClearanceController::class, 'store'])->name('clearance.store');
    });
