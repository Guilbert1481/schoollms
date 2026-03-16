<?php

use App\Http\Controllers\Communication\ChatController;
use App\Http\Controllers\Communication\AdminChatController;

/*
|--------------------------------------------------------------------------
| User Chat (Thread Based)
|--------------------------------------------------------------------------
*/


Route::prefix('communication')->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])
        ->name('chat.index');

    Route::get('/chat/create', [ChatController::class, 'create'])
        ->name('chat.create');

    Route::post('/chat', [ChatController::class, 'store'])
        ->name('chat.store');

    Route::get('/chat/{thread}', [ChatController::class, 'show'])
        ->name('chat.show');

    Route::post('/chat/{thread}/message', [ChatController::class, 'storeMessage'])
        ->name('chat.message');
});


/*
|--------------------------------------------------------------------------
| Admin Chat Moderation
|--------------------------------------------------------------------------
*/

Route::get('/admin/chats', [AdminChatController::class, 'index'])
    ->name('admin.chats.index');

Route::get('/admin/chats/{chat}', [AdminChatController::class, 'show'])
    ->name('admin.chats.show');

Route::post('/admin/chats/{chat}/approve', [AdminChatController::class, 'approve'])
    ->name('admin.chats.approve');

Route::post('/admin/chats/{chat}/escalate', [AdminChatController::class, 'escalate'])
    ->name('admin.chats.escalate');

Route::post('/admin/chats/{chat}/close', [AdminChatController::class, 'close'])
    ->name('admin.chats.close');
