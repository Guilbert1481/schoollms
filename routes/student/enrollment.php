<?php

use App\Http\Controllers\Public\EnrollmentController;
use App\Http\Controllers\Public\EnrollmentQrLandingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Enrolment Wizard — 9-step unified flow
|--------------------------------------------------------------------------
|  1. Personal Info        → public.apply.show / store
|  2. Contact Details      → public.apply.step2 / step2.store
|  3. Family & Emergency   → public.apply.family / family.store
|  4. Learning Pathway     → public.apply.pathway / pathway.store
|  5. Academic Background  → public.apply.academic / academic.store  (skipped for student_type=regular)
|  6. Health Information   → public.apply.health / health.store
|  7. Financial Assessment → public.apply.financial / financial.store
|  8. Review & Submit      → public.apply.review / submit
|  9. Confirmation         → public.apply.confirmation
*/

Route::prefix('apply')->name('public.apply.')->middleware('enrollment.open')->group(function () {

    // QR-code landing (public — handles auth state internally). The POSTs are
    // unauthenticated, so they are IP-throttled (H6).
    Route::get('/qr/{term}', [EnrollmentQrLandingController::class, 'show'])->name('qr');
    Route::post('/qr/{term}/login', [EnrollmentQrLandingController::class, 'login'])->name('qr.login')->middleware('throttle:public-apply');
    Route::post('/qr/{term}/register', [EnrollmentQrLandingController::class, 'register'])->name('qr.register')->middleware('throttle:public-apply');

    // GET fallbacks: if a user hits these via refresh / back-button / direct URL,
    // bounce them back to the landing page instead of throwing 405.
    Route::get('/qr/{term}/login', fn ($term) => redirect()->route('public.apply.qr', $term));
    Route::get('/qr/{term}/register', fn ($term) => redirect()->route('public.apply.qr', $term));

    Route::get('/{term}', [EnrollmentController::class, 'show'])->name('show');

    Route::middleware(['auth', 'role:student'])->group(function () {

        // Step 1 — Personal Info (photo/ID uploads — H6 throttled)
        Route::post('/{term}', [EnrollmentController::class, 'store'])->name('store')->middleware('throttle:uploads');
        Route::post('/{term}/draft', [EnrollmentController::class, 'saveDraft'])->name('draft');
        Route::get('/{term}/exit', [EnrollmentController::class, 'exitToDashboard'])->name('exit');

        // Step 2 — Contact Details
        Route::get('/{term}/step-2', [EnrollmentController::class, 'showStep2'])->name('step2');
        Route::post('/{term}/step-2', [EnrollmentController::class, 'storeStep2'])->name('step2.store');

        // Step 3 — Family & Emergency
        Route::get('/{term}/family', [EnrollmentController::class, 'showFamily'])->name('family');
        Route::post('/{term}/family', [EnrollmentController::class, 'storeFamily'])->name('family.store');

        // Step 4 — Learning Pathway (cascading education node + program)
        Route::get('/{term}/pathway', [EnrollmentController::class, 'showPathway'])->name('pathway');
        Route::post('/{term}/pathway', [EnrollmentController::class, 'storePathway'])->name('pathway.store')->middleware('throttle:uploads'); // H6 — file-bearing step
        Route::get('/{term}/pathway/branch/{node}', [EnrollmentController::class, 'pathwayBranch'])
            ->name('pathway.branch');
        Route::get('/{term}/pathway/subjects', [EnrollmentController::class, 'pathwaySubjects'])
            ->name('pathway.subjects');

        // Step 5 — Academic Background (skipped when student_type=regular)
        Route::get('/{term}/academic', [EnrollmentController::class, 'showAcademic'])->name('academic');
        Route::post('/{term}/academic', [EnrollmentController::class, 'storeAcademic'])->name('academic.store');

        // Step 6 — Health Information
        Route::get('/{term}/health', [EnrollmentController::class, 'showHealth'])->name('health');
        Route::post('/{term}/health', [EnrollmentController::class, 'storeHealth'])->name('health.store');

        // Step 7 — Diagnostic / Admission Test (pre-submission; conditional per
        // the school's "who must take the admission exam" config)
        Route::get('/{term}/diagnostic', [EnrollmentController::class, 'showDiagnostic'])->name('diagnostic');
        Route::post('/{term}/diagnostic', [EnrollmentController::class, 'storeDiagnostic'])->name('diagnostic.store');

        // Step 8 — Financial Assessment (fee review + payment-plan selection)
        Route::get('/{term}/financial', [EnrollmentController::class, 'showFinancial'])->name('financial');
        Route::post('/{term}/financial', [EnrollmentController::class, 'storeFinancial'])->name('financial.store');

        // Step 9 — Review & Submit
        Route::get('/{term}/review', [EnrollmentController::class, 'showReview'])->name('review');
        Route::post('/{term}/submit', [EnrollmentController::class, 'submit'])->name('submit');

        // Step 10 — Confirmation
        Route::get('/{term}/confirmation/{enrollment}', [EnrollmentController::class, 'confirmation'])
            ->name('confirmation');

        // Optional online Admission / Diagnostic Exam (shown right after submission
        // when the school requires it for the applicant's enrollee type).
        Route::get('/{term}/exam/{enrollment}', [EnrollmentController::class, 'exam'])
            ->name('exam');
        Route::post('/{term}/exam/{enrollment}', [EnrollmentController::class, 'submitExam'])
            ->name('exam.submit');

        // Legacy redirects (old /track funnels into pathway)
        Route::get('/{term}/track', [EnrollmentController::class, 'showTrack'])->name('track');
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
                Route::get("/{term}/legacy/{$legacyTrack}/{$legacyStep}", fn ($term) => redirect()->route('public.apply.pathway', $term)
                )->name("{$legacyTrack}.{$legacyStep}");

                Route::post("/{term}/legacy/{$legacyTrack}/{$legacyStep}-store", fn ($term) => redirect()->route('public.apply.pathway', $term)
                )->name("{$legacyTrack}.{$legacyStep}.store");
            }
        }
    });
});
