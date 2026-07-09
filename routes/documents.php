<?php

use App\Http\Controllers\Documents\SecureDocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Secure Documents — gated serving of sensitive uploads (Roadmap C2)
|--------------------------------------------------------------------------
| Government IDs and registrar-required enrollment documents live on the
| PRIVATE disk and are only reachable through these authenticated routes.
| Authorization (owner / same-school staff / superadmin) is enforced in
| SecureDocumentController — role middleware alone can't express
| "owner OR staff", so the fine-grained check lives with the record.
*/

Route::middleware('auth')->prefix('documents')->name('documents.')->group(function () {
    Route::get('/student/{student}/id', [SecureDocumentController::class, 'studentId'])
        ->name('student-id');

    Route::get('/enrollment/{document}', [SecureDocumentController::class, 'enrollment'])
        ->name('enrollment');
});
