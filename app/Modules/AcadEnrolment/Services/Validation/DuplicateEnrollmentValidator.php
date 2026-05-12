<?php

namespace App\Modules\AcadEnrolment\Services\Validation;

use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentValidator;
use App\Modules\AcadEnrolment\Services\Contracts\ValidationResult;
use App\Models\StudentEnrollment;

/**
 * Enforces "one active enrolment per student per term" except for the
 * enrolment currently being edited.
 */
class DuplicateEnrollmentValidator implements EnrolmentValidator
{
    public function key(): string
    {
        return 'duplicate_enrollment';
    }

    public function validate(EnrolmentContext $ctx): ValidationResult
    {
        $existing = StudentEnrollment::query()
            ->where('student_id', $ctx->student->id)
            ->where('term_id', $ctx->term->id)
            ->whereNotIn('status', [
                StudentEnrollment::STATUS_DROPPED,
                StudentEnrollment::STATUS_CANCELLED,
                StudentEnrollment::STATUS_COMPLETED,
            ])
            ->when($ctx->enrollment, fn ($q) => $q->where('id', '!=', $ctx->enrollment->id))
            ->exists();

        if ($existing) {
            return ValidationResult::fail(
                'This student already has an active enrolment for the selected term.',
                ['student_id' => $ctx->student->id, 'term_id' => $ctx->term->id]
            );
        }

        return ValidationResult::pass();
    }
}
