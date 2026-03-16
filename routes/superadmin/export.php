<?php

use App\Http\Controllers\Superadmin\ExportController;

Route::middleware(['auth', 'role:superadmin'])->group(function () {
    // Other routes...
    Route::get('/export/school/{school}', [ExportController::class, 'export'])
         ->name('superadmin.export.school');
});