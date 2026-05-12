<?php

namespace App\Modules\AcadEnrolment\Services\Validation;

use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentValidator;
use App\Modules\AcadEnrolment\Services\Contracts\ValidationResult;
use App\Modules\AcadEnrolment\Services\Policy\AcademicPolicyResolver;
use Illuminate\Support\Facades\DB;

/**
 * Verifies the total units of selected classes fall inside the policy's
 * [min_units, max_units] range. Overload (between max_units and
 * overload_threshold_units) is downgraded to a warning – the approval
 * router decides whether to escalate to the dean.
 */
class UnitsRangeValidator implements EnrolmentValidator
{
    public function __construct(protected AcademicPolicyResolver $policies) {}

    public function key(): string
    {
        return 'units_range';
    }

    public function validate(EnrolmentContext $ctx): ValidationResult
    {
        if ($ctx->selectedClassIds->isEmpty()) {
            return ValidationResult::pass();
        }

        $policy = $this->policies->resolve(
            $ctx->effectiveSchoolId(),
            $ctx->educationLevel,
            $ctx->programId,
            $ctx->term->id
        );

        // Basic Ed has no per-subject units; skip silently.
        if (!$policy->min_units && !$policy->max_units) {
            return ValidationResult::pass();
        }

        $totalUnits = (float) DB::table('classes as c')
            ->join('curriculum_subjects as cs', 'cs.subject_id', '=', 'c.subject_id')
            ->whereIn('c.id', $ctx->selectedClassIds)
            ->sum('cs.units');

        $result = ValidationResult::pass();

        if ($policy->min_units && $totalUnits < (float) $policy->min_units) {
            $result->add(
                ValidationResult::SEVERITY_FAIL,
                "Total units ({$totalUnits}) is below the minimum required ({$policy->min_units}).",
                ['total_units' => $totalUnits, 'min_units' => $policy->min_units]
            );
        }

        if ($policy->max_units && $totalUnits > (float) $policy->max_units) {
            $isOverload = $policy->overload_threshold_units
                && $totalUnits <= (float) $policy->overload_threshold_units;

            $result->add(
                $isOverload ? ValidationResult::SEVERITY_WARN : ValidationResult::SEVERITY_FAIL,
                $isOverload
                    ? "Overload: {$totalUnits} units exceeds the standard maximum ({$policy->max_units}). Requires dean approval."
                    : "Total units ({$totalUnits}) exceeds the absolute maximum ({$policy->overload_threshold_units}).",
                ['total_units' => $totalUnits, 'max_units' => $policy->max_units]
            );
        }

        return $result;
    }
}
