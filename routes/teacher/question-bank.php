<?php

use App\Http\Controllers\Teacher\QuestionBankController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Question Bank (list of the teacher's own authored questions)
|--------------------------------------------------------------------------
| Authoring stays in the builders (Create Questions flow); this page is
| browse / inspect / guarded delete only.
*/

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher,course_architect,subject_coordinator,trainor'])
    ->group(function () {

        // URL: /teacher/question_bank
        Route::get('question_bank', [QuestionBankController::class, 'index'])
            ->name('question_bank.index');

        // Detail JSON for the view modal.
        Route::get('question_bank/{question}', [QuestionBankController::class, 'show'])
            ->whereNumber('question')
            ->name('question_bank.show');

        Route::delete('question_bank/{question}', [QuestionBankController::class, 'destroy'])
            ->whereNumber('question')
            ->name('question_bank.destroy');
    });
