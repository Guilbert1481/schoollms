<?php

use App\Http\Controllers\Tools\ToolsIndexController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools')
    ->name('tools.')
    ->group(function () {
        Route::get('/', [ToolsIndexController::class, 'index'])->name('index');
    });
