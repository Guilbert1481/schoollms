<?php


use App\Http\Controllers\Teacher\Question\EnumerationController;

Route::middleware(['auth', 'role:teacher,course_architect,trainor'])->prefix('teacher/tests')->group(function () {
    Route::get('/enumeration', [EnumerationController::class, 'builder'])->name('enumeration.builder');
    Route::post('/enumeration/save', [EnumerationController::class, 'save'])->name('enumeration.save');


    // Optionally, clear Enumeration question session (if you use session-based drafts)
    Route::post('/session/clear', [EnumerationController::class, 'clearQuestionSession'])
        ->name('enumeration.session.clear');
});


