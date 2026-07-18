<?php

use App\Http\Controllers\Scanner\ScannerController;
use App\Http\Controllers\Teacher\Test\TestBuilder\PrintOmrController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SCANNER APP (separate installable PWA)
|--------------------------------------------------------------------------
| Everything the installed Scanner app navigates to must live under /scan —
| that is the manifest's scope, and an out-of-scope navigation would kick the
| user out of the standalone window into a browser tab. So the camera screen is
| re-exposed here (same controller as the portal route) rather than linked
| across to /teacher/... The POST that records a scan is an XHR, not a
| navigation, so it may stay on its portal route.
*/

Route::prefix('scan')
    ->name('scanner.')
    ->middleware(['auth', 'role:teacher,course_architect,trainor', 'scanner.shell'])
    ->group(function () {

        // App home — pick one of my tests that has printed sheets.
        Route::get('/', [ScannerController::class, 'index'])->name('index');

        // Camera screen, rendered in the scanner shell.
        Route::get('/{test}', [PrintOmrController::class, 'scanCamera'])
            ->name('scan-camera')
            ->whereNumber('test')
            ->missing(fn () => abort(404, 'No valid test found.'));

    });
