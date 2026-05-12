<?php

use App\Http\Controllers\Tools\DriveController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/drive')
    ->name('tools.drive.')
    ->group(function () {
        // Static routes FIRST (before any /{file} catch-all).
        Route::get('/',               [DriveController::class, 'index'])->name('index');
        Route::get('/items',          [DriveController::class, 'items'])->name('items');
        Route::get('/users/search',   [DriveController::class, 'searchUsers'])->name('users.search');
        Route::post('/folders',       [DriveController::class, 'storeFolder'])->name('folders.store');
        Route::post('/files',         [DriveController::class, 'storeFiles'])->name('files.store');

        // Per-file operations.
        Route::get('/{file}/preview',  [DriveController::class, 'preview'])->name('preview');
        Route::get('/{file}/download', [DriveController::class, 'download'])->name('download');
        Route::get('/{file}/zip',      [DriveController::class, 'zip'])->name('zip');
        Route::get('/{file}/shares',   [DriveController::class, 'shares'])->name('shares');
        Route::get('/{file}/content',  [DriveController::class, 'getContent'])->name('content.get');
        Route::put('/{file}/content',  [DriveController::class, 'putContent'])->name('content.put');
        Route::get('/{file}/history',  [DriveController::class, 'history'])->name('history');
        Route::post('/{file}/rename',  [DriveController::class, 'rename'])->name('rename');
        Route::post('/{file}/replace', [DriveController::class, 'replace'])->name('replace');
        Route::post('/{file}/share',   [DriveController::class, 'share'])->name('share');
        Route::delete('/{file}',       [DriveController::class, 'destroy'])->name('destroy');
    });
