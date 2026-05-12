<?php

use App\Http\Controllers\Tools\VideoConference\ScreenShareController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/screen-share', [ScreenShareController::class, 'index'])->name('screen-share.index');
    });
