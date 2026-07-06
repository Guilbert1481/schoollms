<?php

use App\Http\Controllers\Staff\Registrar\EnrollmentValidationController;
use App\Http\Controllers\Staff\Registrar\Settings\DocumentRequirementController;
use App\Http\Controllers\Staff\Registrar\Settings\StudentIdSettingController;
use App\Http\Controllers\Staff\Registrar\StudentLedgerController;
use App\Http\Controllers\Staff\Registrar\SubjectCreditController;
use App\Http\Controllers\Staff\Registrar\TranscriptOfRecordController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:registrar,admin,superadmin'])
    ->prefix('registrar')
    ->name('registrar.')
    ->group(function () {

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

        // 6. Settings → Documents (enrollment document requirements per student type).
        Route::get('settings/documents', [DocumentRequirementController::class, 'index'])
            ->name('settings.documents.index');
        Route::post('settings/documents', [DocumentRequirementController::class, 'store'])
            ->name('settings.documents.store');
        Route::put('settings/documents/{requirement}', [DocumentRequirementController::class, 'update'])
            ->name('settings.documents.update');
        Route::delete('settings/documents/{requirement}', [DocumentRequirementController::class, 'destroy'])
            ->name('settings.documents.destroy');
    });
