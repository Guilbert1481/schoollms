<?php

use App\Http\Controllers\CourseArchitect\AssessmentLabController;
use App\Http\Controllers\CourseArchitect\LessonStudioController;
use Illuminate\Support\Facades\Route;

/*
 * The Lesson Studio is split along the structure/content line:
 *
 *   STRUCTURE (Topics / Lessons / Competencies) — who owns the outline
 *   CONTENT   (lesson resources: video / pdf / ppt) — who fills it in
 *
 * Basic education : Principal owns Subjects, subject_coordinator owns both the
 *                   structure and the content.
 * Higher education: program_head owns Subjects AND the structure (see
 *                   routes/staff/program_head/subjects.php); course_architect
 *                   only adds content.
 *
 * Hence the two groups below: content is shared, structure is not.
 */

// ── Browsing + CONTENT — shared by both roles ────────────────────────────────
Route::middleware(['web', 'auth', 'role:course_architect,subject_coordinator'])
    ->prefix('course-architect')
    ->name('course-architect.')
    ->group(function () {

        Route::prefix('lesson-studio')->name('lesson-studio.')->group(function () {
            // Folder browse (hierarchical, URL-persistent)
            Route::get('/', [LessonStudioController::class, 'index'])->name('index');
            Route::get('/s/{subject}', [LessonStudioController::class, 'index'])->name('subject');
            Route::get('/s/{subject}/t/{topic}', [LessonStudioController::class, 'index'])->name('topic');
            Route::get('/s/{subject}/t/{topic}/l/{lesson}', [LessonStudioController::class, 'index'])->name('lesson');

            // Lesson resource CRUD — the content half of the studio.
            Route::post('/', [LessonStudioController::class, 'store'])->name('store');
            Route::get('/{lesson_resource}/preview', [LessonStudioController::class, 'preview'])->name('preview');
            Route::get('/{lesson_resource}/edit', [LessonStudioController::class, 'edit'])->name('edit');
            Route::put('/{lesson_resource}', [LessonStudioController::class, 'update'])->name('update');
            Route::delete('/{lesson_resource}', [LessonStudioController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('assessment-lab')->name('assessment-lab.')->group(function () {
            Route::get('/', [AssessmentLabController::class, 'index'])->name('index');
        });
    });

// ── STRUCTURE — subject_coordinator only ─────────────────────────────────────
// course_architect is deliberately excluded: for higher education the outline
// belongs to the Program Head. The controller additionally fences every one of
// these writes to basic-ed subjects, so a coordinator cannot reach a college
// subject by typing its id either (route gating alone would not stop that).
Route::middleware(['web', 'auth', 'role:subject_coordinator'])
    ->prefix('course-architect')
    ->name('course-architect.')
    ->group(function () {

        Route::prefix('lesson-studio')->name('lesson-studio.')->group(function () {
            // Folder creation (Topic / Lesson / Competency depending on level)
            Route::post('/folder', [LessonStudioController::class, 'createFolder'])->name('folder.store');
            Route::post('/folder/reorder', [LessonStudioController::class, 'reorder'])->name('folder.reorder');
            Route::delete('/folder/{type}/{id}', [LessonStudioController::class, 'destroyFolder'])
                ->whereIn('type', ['topic', 'lesson', 'competency'])
                ->name('folder.destroy');
            // Rename only — `subject` is excluded on purpose: subjects belong to the
            // Principal and are read-only in the Lesson Studio.
            Route::put('/folder/{type}/{id}', [LessonStudioController::class, 'updateFolder'])
                ->whereIn('type', ['topic', 'lesson', 'competency'])
                ->name('folder.update');
        });
    });
