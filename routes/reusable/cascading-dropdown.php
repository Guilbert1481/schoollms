<?php

use Illuminate\Support\Facades\Route;
// Note: Ensure the namespace matches your controller location
use App\Http\Controllers\CascadingDropdownController; 

Route::middleware('web')->group(function () {
    Route::get('/api/programs', [CascadingDropdownController::class, 'programs']);
    Route::get('/api/programs/{program}/subjects', [CascadingDropdownController::class, 'programSubjects']);
    Route::get('/api/subjects', [CascadingDropdownController::class, 'subjects']);
    Route::get('/api/subjects/{subject}/topics', [CascadingDropdownController::class, 'topics']);
    Route::get('/api/subjects/{subject}/programs', [CascadingDropdownController::class, 'subjectPrograms']);
    Route::get('/api/topics/{topicId}/lessons', [CascadingDropdownController::class, 'lessons']);
    Route::get('/api/lessons/{lesson}/competencies', [CascadingDropdownController::class, 'competencies']);
});