<?php

use App\Http\Controllers\Tools\VideoConference\PermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::patch('/permissions/{permission}/toggle', [PermissionController::class, 'toggle'])->whereNumber('permission')->name('permissions.toggle');
    });
