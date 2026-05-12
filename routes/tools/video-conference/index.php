<?php

use App\Http\Controllers\Tools\VideoConference\VideoConferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/', [VideoConferenceController::class, 'index'])->name('index');
    });
