<?php

use App\Http\Controllers\Staff\Registrar\ClearanceQueueController;
use App\Http\Controllers\Staff\Registrar\EnrollmentValidationController;
use App\Http\Controllers\Staff\Registrar\SectionClassesController;
use App\Http\Controllers\Staff\Registrar\SectioningController;
use App\Http\Controllers\Staff\Registrar\Settings\ClearanceSignatoryController;
use App\Http\Controllers\Staff\Registrar\Settings\DocumentRequirementController;
use App\Http\Controllers\Staff\Registrar\Settings\ParentPortalSettingController;
use App\Http\Controllers\Staff\Registrar\Settings\StudentIdSettingController;
use App\Http\Controllers\Staff\Registrar\StudentLedgerController;
use App\Http\Controllers\Staff\Registrar\StudentRequestsController;
use App\Http\Controllers\Staff\Registrar\SubjectCreditController;
use App\Http\Controllers\Staff\Registrar\TeachingAssignmentController;
use App\Http\Controllers\Staff\Registrar\TranscriptOfRecordController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:registrar,admin,superadmin'])
    ->prefix('registrar')
    ->name('registrar.')
    ->group(function () {

        // Teaching Assignments — assign a teacher to a subject × section (upserts
        // the class row that grading + attendance read as the source of truth).
        Route::get('teaching-assignments', [TeachingAssignmentController::class, 'index'])->name('teaching-assignments.index');
        Route::post('teaching-assignments', [TeachingAssignmentController::class, 'store'])->name('teaching-assignments.store');
        Route::delete('teaching-assignments/{class}', [TeachingAssignmentController::class, 'destroy'])->name('teaching-assignments.destroy');

        // Section Classes — build a basic-ed section's classes (one per learning
        // area, each with a teacher) + set the section adviser, in one form.
        Route::get('sections/{section}/classes', [SectionClassesController::class, 'show'])->name('section-classes.show');
        Route::post('sections/{section}/classes', [SectionClassesController::class, 'store'])->name('section-classes.store');

        // Sectioning Workbench — place enrolled-but-unsectioned basic-ed
        // students into their grade's published sections.
        Route::get('sectioning', [SectioningController::class, 'index'])->name('sectioning.index');
        Route::post('sectioning/assign', [SectioningController::class, 'assign'])->name('sectioning.assign');
        Route::post('sectioning/distribute', [SectioningController::class, 'distribute'])->name('sectioning.distribute');

        // 1. Validate Enrollment
        Route::get('enrollments', [EnrollmentValidationController::class, 'index'])
            ->name('enrollments.index');
        Route::get('enrollments/{enrollment}', [EnrollmentValidationController::class, 'show'])
            ->name('enrollments.show');
        Route::post('enrollments/{enrollment}/validate', [EnrollmentValidationController::class, 'validateEnrollment'])
            ->name('enrollments.validate');
        Route::post('enrollments/{enrollment}/provisional', [EnrollmentValidationController::class, 'approveProvisionally'])
            ->name('enrollments.provisional');
        Route::post('enrollments/{enrollment}/reject', [EnrollmentValidationController::class, 'reject'])
            ->name('enrollments.reject');
        Route::post('enrollments/{enrollment}/documents-complied', [EnrollmentValidationController::class, 'completeDocuments'])
            ->name('enrollments.documents-complied');

        // 2. Evaluate Subject Credits (transferees / irregulars)
        Route::get('subject-credits', [SubjectCreditController::class, 'index'])
            ->name('subject-credits.index');
        Route::post('subject-credits', [SubjectCreditController::class, 'store'])
            ->name('subject-credits.store');
        Route::post('subject-credits/{evaluation}/decide', [SubjectCreditController::class, 'decide'])
            ->name('subject-credits.decide');
        Route::delete('subject-credits/{evaluation}', [SubjectCreditController::class, 'destroy'])
            ->name('subject-credits.destroy');

        // 3. Transcript of Records — master list of students by education level
        Route::get('transcripts', [TranscriptOfRecordController::class, 'index'])
            ->name('transcripts.index');
        Route::get('transcripts/export', [TranscriptOfRecordController::class, 'export'])
            ->name('transcripts.export');
        Route::get('transcripts/{student}', [TranscriptOfRecordController::class, 'show'])
            ->name('transcripts.show');
        Route::post('transcripts/{student}/edit-requests', [TranscriptOfRecordController::class, 'storeEditRequest'])
            ->name('transcripts.edit-requests.store');
        Route::post('transcripts/{student}/credit-edits', [TranscriptOfRecordController::class, 'applyCreditEdit'])
            ->name('transcripts.credit-edits.apply');
        Route::post('transcripts/{student}/form137-grade', [TranscriptOfRecordController::class, 'saveForm137Grade'])
            ->name('transcripts.form137-grade');
        Route::post('transcripts/{student}/report-card-grade', [TranscriptOfRecordController::class, 'saveReportCardGrade'])
            ->name('transcripts.report-card-grade');
        // Per-student grade-view visibility toggles (Grades / Form 137) — hidden
        // from the student until the registrar grants access on request.
        Route::post('transcripts/{student}/grade-visibility', [TranscriptOfRecordController::class, 'updateGradeVisibility'])
            ->name('transcripts.grade-visibility');

        // 4. Student Registry — detailed records of officially enrolled students
        Route::get('student-registry', [StudentLedgerController::class, 'index'])
            ->name('student-registry.index');
        Route::post('student-registry/import', [StudentLedgerController::class, 'import'])
            ->name('student-registry.import');
        Route::get('student-registry/export', [StudentLedgerController::class, 'export'])
            ->name('student-registry.export');
        Route::get('student-registry/import-template', [StudentLedgerController::class, 'importTemplate'])
            ->name('student-registry.import-template');
        Route::get('student-registry/{student}', [StudentLedgerController::class, 'show'])
            ->name('student-registry.show');
        Route::patch('student-registry/{student}/status', [StudentLedgerController::class, 'updateStatus'])
            ->name('student-registry.status');

        // 5. Settings → Student ID (display options for the digital ID).
        Route::get('settings/student-id', [StudentIdSettingController::class, 'edit'])
            ->name('settings.student-id.edit');
        Route::put('settings/student-id', [StudentIdSettingController::class, 'update'])
            ->name('settings.student-id.update');

        // 6. Settings → Parent Portal (auto-provisioning toggle + access links + issues).
        Route::get('settings/parent-portal', [ParentPortalSettingController::class, 'index'])
            ->name('settings.parent-portal.index');
        Route::put('settings/parent-portal', [ParentPortalSettingController::class, 'update'])
            ->name('settings.parent-portal.update');
        Route::post('settings/parent-portal/links/{link}/toggle', [ParentPortalSettingController::class, 'toggleLink'])
            ->name('settings.parent-portal.links.toggle');
        Route::post('settings/parent-portal/links/{link}/reissue', [ParentPortalSettingController::class, 'reissueLink'])
            ->name('settings.parent-portal.links.reissue');
        Route::post('settings/parent-portal/issues/{issue}/resolve', [ParentPortalSettingController::class, 'resolveIssue'])
            ->name('settings.parent-portal.issues.resolve');

        // 7. Settings → Documents (enrollment document requirements per student type).
        Route::get('settings/documents', [DocumentRequirementController::class, 'index'])
            ->name('settings.documents.index');
        Route::post('settings/documents', [DocumentRequirementController::class, 'store'])
            ->name('settings.documents.store');
        Route::put('settings/documents/{requirement}', [DocumentRequirementController::class, 'update'])
            ->name('settings.documents.update');
        Route::delete('settings/documents/{requirement}', [DocumentRequirementController::class, 'destroy'])
            ->name('settings.documents.destroy');

        // 8. Student Requests — modality changes + document requests, one
        //    tabbed queue. Approving a modality request updates the enrollment.
        Route::get('requests', [StudentRequestsController::class, 'index'])
            ->name('requests.index');
        Route::put('requests/modality/{modalityRequest}', [StudentRequestsController::class, 'decideModality'])
            ->name('requests.modality.decide');
        Route::put('requests/documents/{documentRequest}', [StudentRequestsController::class, 'transitionDocument'])
            ->name('requests.documents.transition');

        // 9. Clearances — open-clearance queue + per-item sign-off.
        Route::get('clearances', [ClearanceQueueController::class, 'index'])
            ->name('clearances.index');
        Route::get('clearances/{clearance}', [ClearanceQueueController::class, 'show'])
            ->name('clearances.show');
        Route::put('clearances/{clearance}/items/{item}', [ClearanceQueueController::class, 'updateItem'])
            ->name('clearances.items.update');

        // 10. Settings → Clearance Signatories (per-school sign-off list).
        Route::get('settings/clearance-signatories', [ClearanceSignatoryController::class, 'index'])
            ->name('settings.clearance-signatories.index');
        Route::post('settings/clearance-signatories', [ClearanceSignatoryController::class, 'store'])
            ->name('settings.clearance-signatories.store');
        Route::put('settings/clearance-signatories/{signatory}', [ClearanceSignatoryController::class, 'update'])
            ->name('settings.clearance-signatories.update');
        Route::delete('settings/clearance-signatories/{signatory}', [ClearanceSignatoryController::class, 'destroy'])
            ->name('settings.clearance-signatories.destroy');
    });
