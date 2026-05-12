<?php

use App\Http\Controllers\Tools\VideoConference\BuzzInController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/buzz-in', [BuzzInController::class, 'index'])->name('buzz-in.index');
    });
