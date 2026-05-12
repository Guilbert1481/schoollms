<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Certificates\CertificateTemplateController;
use App\Http\Controllers\Certificates\CertificateEventController;
use App\Http\Controllers\Certificates\CertificateEventAwardController;

/*
|--------------------------------------------------------------------------
| CERTIFICATES ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])
    ->prefix('school/settings/master-data/certificates')
    ->name('school.settings.master-data.certificates.')
    ->group(function () {

        Route::get('/', [CertificateTemplateController::class, 'index'])->name('index');

        Route::get('/builder', [CertificateTemplateController::class, 'builder'])->name('builder');

        Route::get('/{id}/edit', [CertificateTemplateController::class, 'edit'])->name('builder.edit');

        Route::post('/', [CertificateTemplateController::class, 'store'])->name('store');

        Route::put('/{id}', [CertificateTemplateController::class, 'update'])->name('update');

        Route::delete('/{id}', [CertificateTemplateController::class, 'destroy'])->name('destroy');

        Route::post('/save', [CertificateTemplateController::class, 'saveTemplate'])->name('save');

        Route::get('/{id}/preview', [CertificateTemplateController::class, 'preview'])->name('preview');

        Route::get('/preview/sample', [CertificateTemplateController::class, 'previewSample'])->name('preview.sample');

        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/', [CertificateEventController::class, 'index'])->name('index');
            Route::post('/', [CertificateEventController::class, 'store'])->name('store');
            Route::get('/{eventId}', [CertificateEventController::class, 'show'])->name('show');
            Route::put('/{eventId}', [CertificateEventController::class, 'update'])->name('update');
            Route::delete('/{eventId}', [CertificateEventController::class, 'destroy'])->name('destroy');

            Route::get('/{eventId}/awards', [CertificateEventAwardController::class, 'index'])->name('awards.index');
            Route::post('/{eventId}/awards', [CertificateEventAwardController::class, 'store'])->name('awards.store');
            Route::delete('/{eventId}/awards', [CertificateEventAwardController::class, 'destroy'])->name('awards.destroy');

            Route::post('/{eventId}/recipients', [CertificateEventController::class, 'storeRecipient'])->name('recipients.store');
            Route::post('/{eventId}/recipients/bulk', [CertificateEventController::class, 'bulkStoreRecipients'])->name('recipients.bulkStore');
            Route::put('/{eventId}/recipients/{recipientId}', [CertificateEventController::class, 'updateRecipient'])->name('recipients.update');
            Route::delete('/{eventId}/recipients/{recipientId}', [CertificateEventController::class, 'destroyRecipient'])->name('recipients.destroy');
        });

        // OPTIONAL (if you want builder with ID)
        Route::get('/builder/{id?}', [CertificateTemplateController::class, 'builder'])->name('builder.withId');

    });
