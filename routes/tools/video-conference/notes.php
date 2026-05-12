<?php

use App\Http\Controllers\Tools\VideoConference\NotesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/notes', [NotesController::class, 'index'])->name('notes.index');
    });
