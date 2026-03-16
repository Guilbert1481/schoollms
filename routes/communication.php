<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Communication\CommunicationController;
use App\Http\Controllers\Communication\AnnouncementController;
use App\Http\Controllers\Communication\DeadlineController;
use App\Http\Controllers\Communication\ChatController;


Route::middleware(['auth'])
    ->prefix('communication')
    ->name('communication.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD
        |--------------------------------------------------------------------------
        */
        Route::get('/', [CommunicationController::class, 'index'])->name('index');

        /*
        |--------------------------------------------------------------------------
        | ANNOUNCEMENTS
        |--------------------------------------------------------------------------
        */
        Route::resource('announcements', AnnouncementController::class);

        /*
        
        |--------------------------------------------------------------------------
        | DEADLINES & EXTRA ACTIONS
        |--------------------------------------------------------------------------
        */
        
        // Custom "Manage" route MUST come before the resource to avoid ID conflicts
        Route::get('deadlines/{deadline}/manage', [DeadlineController::class, 'manage'])
            ->name('deadlines.manage');

        Route::patch('deadlines/{deadline}/complete', [DeadlineController::class, 'markComplete'])
            ->name('deadlines.complete');

        Route::patch('deadlines/{deadline}/user/{user}/complete', [DeadlineController::class, 'markUserComplete'])
            ->name('deadlines.markUserComplete');

        // Main Resource Routes
        Route::resource('deadlines', DeadlineController::class);

        /*
        |--------------------------------------------------------------------------
        | CHAT (THREAD BASED)
        |--------------------------------------------------------------------------
        */
        Route::prefix('chat')->name('chat.')->group(function () {
            Route::get('/', [ChatController::class, 'index'])->name('index');
            Route::get('/create', [ChatController::class, 'create'])->name('create');
            Route::post('/', [ChatController::class, 'store'])->name('store');
            Route::get('/{thread}', [ChatController::class, 'show'])->name('show');
            Route::post('/{thread}/message', [ChatController::class, 'storeMessage'])->name('message.store');

        });



        // ANNOUNCEMENT ACKNOWLEDGE ROUTE (must be inside group for correct naming)
            Route::post('announcements/{id}/acknowledge', [AnnouncementController::class, 'acknowledge'])
                ->name('announcements.acknowledge');
    });


    