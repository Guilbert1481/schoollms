<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Student\EnrollmentController;

/*
|--------------------------------------------------------------------------
| Student Enrollment Routes
|--------------------------------------------------------------------------
|
| These routes handle the multi-step enrollment process.
|
*/

Route::prefix('apply')->name('public.apply.')->group(function () {

    // Step 1: Show Form (Accessible to Guests/Public)
    Route::get('/{semester}', [EnrollmentController::class, 'show'])
        ->name('show');

    // Protected Routes: Require Student Authentication
    Route::middleware(['auth', 'role:student'])->group(function () {
        
        // Step 1: Submit/Store
        Route::post('/{semester}', [EnrollmentController::class, 'store'])
            ->name('store');

        // Save Progress
        Route::post('/{semester}/draft', [EnrollmentController::class, 'saveDraft'])
            ->name('draft');

        // Step 2: Show and Store
        Route::get('/{semester}/step-2', [EnrollmentController::class, 'showStep2'])
            ->name('step2');
            
        Route::post('/{semester}/step-2', [EnrollmentController::class, 'storeStep2'])
            ->name('step2.store');

        // You can add steps 3-7 here as you develop them
    });
});