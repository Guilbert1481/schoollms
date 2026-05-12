<?php

use App\Http\Controllers\Tools\VideoConference\HistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    });
