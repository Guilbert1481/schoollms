<?php

use App\Http\Controllers\Teacher\Test\TestBuilder\TestBuilderController;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['auth', 'role:teacher,course_architect,trainor'])->group(function () {

    // LEVEL(S) → SUBJECTS (only subjects with questions at the selected level[s])
    Route::get('/teacher/tests/available-subjects', [TestBuilderController::class, 'getAvailableSubjects'])
        ->name('teacher.tests.available_subjects');

    // SUBJECT → TOPICS
    Route::get('/teacher/tests/available-topics/{subjectId}', [TestBuilderController::class, 'getAvailableTopics']);

    // TOPIC → LESSONS
    Route::get('/teacher/tests/available-lessons/{topicId}', [TestBuilderController::class, 'getAvailableLessons']);

    // LESSON → COMPETENCIES
    Route::get('/teacher/tests/competencies',
        [TestBuilderController::class, 'getCompetencies']
    )->name('teacher.tests.competencies');

});
