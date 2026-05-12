<?php

use App\Http\Controllers\Tools\VideoConference\RaiseHandController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/raise-hand', [RaiseHandController::class, 'index'])->name('raise-hand.index');
    });
