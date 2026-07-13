<?php

use App\Http\Controllers\Principal\GradeSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:principal'])
    ->prefix('principal/settings')
    ->name('principal.settings.')
    ->group(function () {
        // Settings → Grades (passing threshold + promotion rule for Form 137).
        Route::get('/grades', [GradeSettingsController::class, 'index'])->name('grades');
        Route::post('/grades', [GradeSettingsController::class, 'update'])->name('grades.update');
    });
