<?php

use App\Http\Controllers\Tools\VideoConference\WhiteboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/whiteboard', [WhiteboardController::class, 'index'])->name('whiteboard.index');
    });
