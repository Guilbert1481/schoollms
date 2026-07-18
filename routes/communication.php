<?php

use App\Http\Controllers\Communication\AnnouncementController;
use App\Http\Controllers\Communication\ChatController;
use App\Http\Controllers\Communication\CommunicationController;
use App\Http\Controllers\Communication\DeadlineController;
use Illuminate\Support\Facades\Route;

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
            Route::get('/users/search', [ChatController::class, 'searchUsers'])->name('users.search');
            Route::post('/direct/{user}', [ChatController::class, 'startDirect'])->name('direct');
            Route::get('/create', [ChatController::class, 'create'])->name('create');
            Route::post('/', [ChatController::class, 'store'])->name('store');

            // Attachment routes MUST be declared before /{thread} so the
            // model-binding catch-all does not swallow /attachments/* URLs.
            Route::get('/attachments/{message}', [ChatController::class, 'attachment'])->name('attachment');
            Route::get('/attachments/{message}/preview', [ChatController::class, 'attachmentPreview'])->name('attachment.preview');
            Route::get('/attachments/{message}/download', [ChatController::class, 'downloadAttachment'])->name('attachment.download');

            Route::get('/{thread}', [ChatController::class, 'show'])->name('show');
            Route::get('/{thread}/messages', [ChatController::class, 'threadMessages'])->name('messages');
            Route::post('/{thread}/message', [ChatController::class, 'storeMessage'])->name('message.store')
                ->middleware('throttle:chat'); // H6 — message/attachment flood guard

            Route::delete('/{thread}', [ChatController::class, 'destroy'])->name('destroy');
            Route::get('/{thread}/members', [ChatController::class, 'members'])->name('members');
            Route::post('/{thread}/members', [ChatController::class, 'addMembers'])->name('members.add');
            Route::delete('/{thread}/members/{user}', [ChatController::class, 'removeMember'])->name('members.remove');

        });

        // ANNOUNCEMENT ACKNOWLEDGE ROUTE (must be inside group for correct naming)
        Route::post('announcements/{id}/acknowledge', [AnnouncementController::class, 'acknowledge'])
            ->name('announcements.acknowledge');
    });
