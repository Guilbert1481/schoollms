<?php

use App\Http\Controllers\Tools\VideoConference\ParticipantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/participants', [ParticipantController::class, 'index'])->name('participants.index');
    });
