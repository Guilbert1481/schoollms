<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainor\CertificateController;

Route::middleware(['web', 'auth'])
    ->prefix('training/trainor')
    ->name('training.trainor.')
    ->group(function () {
        Route::get('/certificates', [CertificateController::class, 'index'])
            ->name('certificates');

        Route::post('/certificates/{enrollment}/issue', [CertificateController::class, 'issue'])
            ->whereNumber('enrollment')
            ->name('certificates.issue');
    });
