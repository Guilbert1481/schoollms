<?php

namespace App\Modules\AcadEnrolment\Services\Validation;

use App\Modules\AcadEnrolment\Services\AcademicStandingService;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentValidator;
use App\Modules\AcadEnrolment\Services\Contracts\ValidationResult;

/**
 * Probationary students still enrol but the routing service uses the
 * warning to escalate to the dean / program head.
 */
class AcademicStandingValidator implements EnrolmentValidator
{
    public function __construct(protected AcademicStandingService $standing) {}

    public function key(): string
    {
        return 'academic_standing';
    }

    public function validate(EnrolmentContext $ctx): ValidationResult
    {
        $status = $this->standing->standingFor($ctx->student);

        return match ($status) {
            AcademicStandingService::STANDING_GOOD      => ValidationResult::pass(),
            AcademicStandingService::STANDING_WARNING   => ValidationResult::warn(
                'Student has one failing grade in the recent term window.'
            ),
            AcademicStandingService::STANDING_PROBATION => ValidationResult::warn(
                'Student is on academic probation and requires dean approval.'
            ),
        };
    }
}
