<?php

use App\Http\Controllers\Teacher\QuestionMetadataController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:teacher,course_architect,subject_coordinator,trainor'])->prefix('teacher/tests')->group(function () {

    // GET: This opens the page.
    // Matches Sidebar AND McqController redirect.
    Route::get('/question-metadata', [QuestionMetadataController::class, 'createInfo'])
        ->name('teacher.question.metadata');

    // POST: This saves the data.
    // Matches your Blade Form action.
    Route::post('/save-metadata', [QuestionMetadataController::class, 'storeInfo'])
        ->name('teacher.question.metadata.save');
});
