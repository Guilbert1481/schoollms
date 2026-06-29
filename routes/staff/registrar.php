<?php

use App\Http\Controllers\Staff\Registrar\EnrollmentValidationController;
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
        Route::get('transcripts/{student}', [TranscriptOfRecordController::class, 'show'])
            ->name('transcripts.show');
        Route::post('transcripts/{student}/edit-requests', [TranscriptOfRecordController::class, 'storeEditRequest'])
            ->name('transcripts.edit-requests.store');
        Route::post('transcripts/{student}/credit-edits', [TranscriptOfRecordController::class, 'applyCreditEdit'])
            ->name('transcripts.credit-edits.apply');

        // 4. Student Ledgers — detailed records of officially enrolled students
        Route::get('student-ledgers', [StudentLedgerController::class, 'index'])
            ->name('student-ledgers.index');
        Route::post('student-ledgers/import', [StudentLedgerController::class, 'import'])
            ->name('student-ledgers.import');
        Route::get('student-ledgers/export', [StudentLedgerController::class, 'export'])
            ->name('student-ledgers.export');
        Route::get('student-ledgers/import-template', [StudentLedgerController::class, 'importTemplate'])
            ->name('student-ledgers.import-template');
        Route::get('student-ledgers/{student}', [StudentLedgerController::class, 'show'])
            ->name('student-ledgers.show');
        Route::patch('student-ledgers/{student}/status', [StudentLedgerController::class, 'updateStatus'])
            ->name('student-ledgers.status');

        // 5. Settings → Student ID (display options for the digital ID).
        Route::get('settings/student-id', [StudentIdSettingController::class, 'edit'])
            ->name('settings.student-id.edit');
        Route::put('settings/student-id', [StudentIdSettingController::class, 'update'])
            ->name('settings.student-id.update');
    });
