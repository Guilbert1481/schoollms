<?php

namespace App\Modules\AcadEnrolment\Services\Validation;

use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentValidator;
use App\Modules\AcadEnrolment\Services\Contracts\ValidationResult;
use App\Modules\AcadEnrolment\Services\Policy\AcademicPolicyResolver;
use Illuminate\Support\Facades\DB;

/**
 * Section capacity check (Basic Ed / SHS where students are placed in a
 * single homeroom-style section).
 */
class SectionCapacityValidator implements EnrolmentValidator
{
    public function __construct(protected AcademicPolicyResolver $policies) {}

    public function key(): string
    {
        return 'section_capacity';
    }

    public function validate(EnrolmentContext $ctx): ValidationResult
    {
        if (!$ctx->sectionId) {
            return ValidationResult::pass();
        }

        $section = DB::table('sections')->where('id', $ctx->sectionId)->first();
        if (!$section) {
            return ValidationResult::fail('Selected section does not exist.', ['section_id' => $ctx->sectionId]);
        }

        $policy = $this->policies->resolve(
            $ctx->effectiveSchoolId(),
            $ctx->educationLevel,
            $ctx->programId,
            $ctx->term->id
        );

        $cap = $policy->max_section_capacity_override
            ?: ($section->capacity ?: null);

        if (!$cap) {
            return ValidationResult::pass();
        }

        $taken = DB::table('student_enrollments')
            ->where('section_id', $ctx->sectionId)
            ->where('term_id', $ctx->term->id)
            ->whereIn('status', ['enrolled', 'billed', 'partially_paid', 'assessed', 'submitted'])
            ->when($ctx->enrollment, fn ($q) => $q->where('id', '!=', $ctx->enrollment->id))
            ->count();

        if ($taken >= $cap) {
            return ValidationResult::fail(
                "Section \"{$section->name}\" is full ({$taken}/{$cap}).",
                ['section_id' => $ctx->sectionId, 'taken' => $taken, 'capacity' => $cap]
            );
        }

        return ValidationResult::pass();
    }
}
