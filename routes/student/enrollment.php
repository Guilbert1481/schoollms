<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\EnrollmentController;

/*
|--------------------------------------------------------------------------
| Public Enrolment Wizard — 7-step unified flow
|--------------------------------------------------------------------------
|  1. Personal Info        → public.apply.show / store
|  2. Contact Details      → public.apply.step2 / step2.store
|  3. Family & Emergency   → public.apply.family / family.store
|  4. Learning Pathway     → public.apply.pathway / pathway.store
|  5. Academic Background  → public.apply.academic / academic.store  (skipped for student_type=regular)
|  6. Review & Submit      → public.apply.review / submit
|  7. Confirmation         → public.apply.confirmation
*/

Route::prefix('apply')->name('public.apply.')->group(function () {

    Route::get('/{term}', [EnrollmentController::class, 'show'])->name('show');

    Route::middleware(['auth', 'role:student'])->group(function () {

        // Step 1 — Personal Info
        Route::post('/{term}', [EnrollmentController::class, 'store'])->name('store');
        Route::post('/{term}/draft', [EnrollmentController::class, 'saveDraft'])->name('draft');
        Route::get('/{term}/exit', [EnrollmentController::class, 'exitToDashboard'])->name('exit');

        // Step 2 — Contact Details
        Route::get('/{term}/step-2',  [EnrollmentController::class, 'showStep2'])->name('step2');
        Route::post('/{term}/step-2', [EnrollmentController::class, 'storeStep2'])->name('step2.store');

        // Step 3 — Family & Emergency
        Route::get('/{term}/family',  [EnrollmentController::class, 'showFamily'])->name('family');
        Route::post('/{term}/family', [EnrollmentController::class, 'storeFamily'])->name('family.store');

        // Step 4 — Learning Pathway (cascading education node + program)
        Route::get('/{term}/pathway',  [EnrollmentController::class, 'showPathway'])->name('pathway');
        Route::post('/{term}/pathway', [EnrollmentController::class, 'storePathway'])->name('pathway.store');
        Route::get('/{term}/pathway/branch/{node}', [EnrollmentController::class, 'pathwayBranch'])
            ->name('pathway.branch');
        Route::get('/{term}/pathway/subjects', [EnrollmentController::class, 'pathwaySubjects'])
            ->name('pathway.subjects');

        // Step 5 — Academic Background (skipped when student_type=regular)
        Route::get('/{term}/academic',  [EnrollmentController::class, 'showAcademic'])->name('academic');
        Route::post('/{term}/academic', [EnrollmentController::class, 'storeAcademic'])->name('academic.store');

        // Step 6 — Review & Submit
        Route::get('/{term}/review',  [EnrollmentController::class, 'showReview'])->name('review');
        Route::post('/{term}/submit', [EnrollmentController::class, 'submit'])->name('submit');

        // Step 7 — Confirmation
        Route::get('/{term}/confirmation/{enrollment}', [EnrollmentController::class, 'confirmation'])
            ->name('confirmation');

        // Legacy redirects (old /track funnels into pathway)
        Route::get('/{term}/track',  [EnrollmentController::class, 'showTrack'])->name('track');
        Route::post('/{term}/track', [EnrollmentController::class, 'storeTrack'])->name('track.store');

        /*
        | Legacy per-track URLs (basic-ed, shs, higher-regular, higher-irregular)
        | were removed when the wizard was unified to a single 7-step flow.
        | Forward any stale bookmarks / links to the new pathway step so users
        | don't see a 404. We also generate placeholder route names for any old
        | Blade `route('public.apply.<track>.<step>')` calls still on disk so
        | rendering legacy views (if reached) doesn't throw.
        */
        foreach (['basic', 'shs', 'higher_regular', 'higher_irregular'] as $legacyTrack) {
            $hyphen = str_replace('_', '-', $legacyTrack);
            Route::any("/{term}/{$hyphen}/{any?}", function ($term) {
                return redirect()->route('public.apply.pathway', $term);
            })->where('any', '.*')->name("{$legacyTrack}.legacy");

            // Named-route placeholders so old Blade `route(...)` calls resolve.
            foreach (['step3', 'step4', 'step5', 'step6', 'step7', 'submit'] as $legacyStep) {
                Route::get("/{term}/legacy/{$legacyTrack}/{$legacyStep}", fn ($term) =>
                    redirect()->route('public.apply.pathway', $term)
                )->name("{$legacyTrack}.{$legacyStep}");

                Route::post("/{term}/legacy/{$legacyTrack}/{$legacyStep}-store", fn ($term) =>
                    redirect()->route('public.apply.pathway', $term)
                )->name("{$legacyTrack}.{$legacyStep}.store");
            }
        }
    });
});
