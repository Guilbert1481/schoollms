<?php

use Illuminate\Support\Facades\Route;
// Match the Communication folder
use App\Http\Controllers\Communication\SuperPriorityController;

Route::middleware(['auth'])->group(function () {
    Route::post('/communication/announcements/{id}/acknowledge', [SuperPriorityController::class, 'acknowledge'])
        ->name('communication.announcements.acknowledge');
});