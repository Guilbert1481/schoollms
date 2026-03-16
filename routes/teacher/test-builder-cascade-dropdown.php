<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\Test\TestBuilder\TestBuilderController;

/*
|--------------------------------------------------------------------------
| Test Builder Cascading Dropdown Routes
|--------------------------------------------------------------------------
| These routes fetch curriculum data directly from the database:
| - Topics by Subject
| - Lessons by Topic
| - Competencies by Lesson
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {


    // SUBJECT → TOPICS
    Route::get('/teacher/tests/available-topics/{subjectId}', [TestBuilderController::class, 'getAvailableTopics']);

    // TOPIC → LESSONS
    Route::get('/teacher/tests/available-lessons/{topicId}', [TestBuilderController::class, 'getAvailableLessons']);

    // LESSON → COMPETENCIES
    Route::get('/teacher/tests/competencies', 
        [TestBuilderController::class, 'getCompetencies']
    )->name('teacher.tests.competencies');

});


