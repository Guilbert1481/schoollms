<?php

use App\Http\Controllers\Tools\VideoConference\ChatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    });
