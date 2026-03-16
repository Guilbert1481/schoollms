<?php
/*
|--------------------------------------------------------------------------
| routes/profile/photo.php
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfilePhotoController;

Route::get('/', [ProfilePhotoController::class, 'edit'])
    ->name('profile.photo.home');

Route::get('/profile/photo/edit', [ProfilePhotoController::class, 'edit'])
    ->name('profile.photo.edit');

Route::post('/profile/photo', [ProfilePhotoController::class, 'updatePhoto'])
    ->name('profile.photo.update');