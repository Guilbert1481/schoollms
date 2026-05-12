<?php

namespace App\Modules\AcadEnrolment\Services;

use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentValidator;
use App\Modules\AcadEnrolment\Services\Contracts\ValidationResult;
use App\Modules\AcadEnrolment\Services\Validation\AcademicStandingValidator;
use App\Modules\AcadEnrolment\Services\Validation\ClassCapacityValidator;
use App\Modules\AcadEnrolment\Services\Validation\DuplicateEnrollmentValidator;
use App\Modules\AcadEnrolment\Services\Validation\PaymentGateValidator;
use App\Modules\AcadEnrolment\Services\Validation\PrerequisiteValidator;
use App\Modules\AcadEnrolment\Services\Validation\ScheduleConflictValidator;
use App\Modules\AcadEnrolment\Services\Validation\SectionCapacityValidator;
use App\Modules\AcadEnrolment\Services\Validation\SectionPublishedValidator;
use App\Modules\AcadEnrolment\Services\Validation\SubjectInCurriculumValidator;
use App\Modules\AcadEnrolment\Services\Validation\TermActiveValidator;
use App\Modules\AcadEnrolment\Services\Validation\UnitsRangeValidator;

/**
 * Runs every enrolment validator and returns a merged ValidationResult.
 *
 * Callers can disable specific validators by key (e.g. skip 'payment_gate'
 * when building the assessed-but-not-yet-billed step of the wizard).
 */
class EnrolmentValidationPipeline
{
    /** @var EnrolmentValidator[] */
    protected array $validators;

    public function __construct(
        TermActiveValidator $termActive,
        DuplicateEnrollmentValidator $duplicate,
        SubjectInCurriculumValidator $inCurriculum,
        PrerequisiteValidator $prereqs,
        ScheduleConflictValidator $schedule,
        SectionCapacityValidator $sectionCap,
        SectionPublishedValidator $sectionPublished,
        ClassCapacityValidator $classCap,
        UnitsRangeValidator $units,
        AcademicStandingValidator $standing,
        PaymentGateValidator $payment,
    ) {
        $this->validators = [
            $termActive,
            $duplicate,
            $inCurriculum,
            $prereqs,
            $schedule,
            $sectionPublished,
            $sectionCap,
            $classCap,
            $units,
            $standing,
            $payment,
        ];
    }

    /**
     * @param string[] $skip Validator keys to skip
     */
    public function run(EnrolmentContext $ctx, array $skip = []): ValidationResult
    {
        $merged = ValidationResult::pass();

        foreach ($this->validators as $v) {
            if (in_array($v->key(), $skip, true)) {
                continue;
            }
            $merged->merge($v->validate($ctx));
        }

        return $merged;
    }
}
