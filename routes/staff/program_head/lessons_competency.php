<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\ProgramHead\LessonCompetencyController;

Route::middleware(['auth', 'role:program_head,admin,superadmin'])
    ->prefix('staff/program-head')
    ->name('program_head.')
    ->group(function () {

    // ===============================
    // Competency Management (Under Lesson)
    // ===============================
Route::post('/lessons/{lesson}/competencies', [LessonCompetencyController::class, 'store'])
    ->name('lessons.competencies.store');
});