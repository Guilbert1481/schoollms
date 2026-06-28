<?php

use App\Http\Controllers\Staff\Admissions\SectionsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:admission_manager,admin,superadmin'])
    ->prefix('admission')
    ->name('admission.')
    ->group(function () {
        Route::get('sections', [SectionsController::class, 'index'])->name('sections.index');
        Route::post('sections', [SectionsController::class, 'store'])->name('sections.store');
        Route::post('sections/auto-generate', [SectionsController::class, 'autoGenerate'])->name('sections.auto-generate');
        Route::post('sections/publish-all', [SectionsController::class, 'publishAll'])->name('sections.publish-all');
        Route::post('sections/{section}/publish', [SectionsController::class, 'publish'])->name('sections.publish');
        Route::post('sections/{section}/unpublish', [SectionsController::class, 'unpublish'])->name('sections.unpublish');
        Route::delete('sections/{section}', [SectionsController::class, 'destroy'])->name('sections.destroy');
    });
