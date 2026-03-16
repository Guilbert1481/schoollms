<?php

use Illuminate\Support\Facades\Route;
// Note: Ensure the namespace matches your controller location
use App\Http\Controllers\CascadingDropdownController; 

Route::middleware('web')->group(function () {
    Route::get('/api/subjects', [CascadingDropdownController::class, 'subjects']);
    Route::get('/api/subjects/{subject}/topics', [CascadingDropdownController::class, 'topics']);
    Route::get('/api/topics/{topicId}/lessons', [CascadingDropdownController::class, 'lessons']);
    Route::get('/api/lessons/{lesson}/competencies', [CascadingDropdownController::class, 'competencies']);
});