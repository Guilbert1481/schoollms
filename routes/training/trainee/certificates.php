<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainee\CertificateController;

Route::middleware(['web', 'auth'])
    ->prefix('training/trainee')
    ->name('training.trainee.')
    ->group(function () {
        Route::get('/certificates', [CertificateController::class, 'index'])
            ->name('certificates');

        Route::get('/certificates/{id}/preview', [CertificateController::class, 'preview'])
            ->name('certificates.preview');
    });