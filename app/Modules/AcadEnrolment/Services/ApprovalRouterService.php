<?php

namespace App\Modules\AcadEnrolment\Services;

use App\Models\Program;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\Policy\AcademicPolicyResolver;

/**
 * Decides which approval lane an enrolment must travel through.
 *
 * Returns one of:
 *   - auto                  : straight-through, no approver needed
 *   - principal             : Basic Ed approver
 *   - subject_coordinator   : SHS strand-level approver
 *   - program_head          : Higher Ed first-line approver
 *   - dean                  : escalation for irregular / overloaded /
 *                             special-case enrolments
 */
class ApprovalRouterService
{
    public function __construct(protected AcademicPolicyResolver $policies) {}

    public function route(EnrolmentContext $ctx, ?float $totalUnits = null): string
    {
        $level = $ctx->educationLevel;

        // ----- Basic Education -----
        if (in_array($level, ['kinder', 'elementary', 'junior_high'], true)) {
            // Continuing students with no irregularities go straight through.
            return $ctx->enrolleeType === 'continuing' && $ctx->programType === 'regular'
                ? 'auto'
                : 'principal';
        }

        // ----- Senior High -----
        if ($level === 'senior_high') {
            // New / transferee / returnee → coordinator; continuing regular → auto
            return $ctx->enrolleeType === 'continuing' && $ctx->programType === 'regular'
                ? 'auto'
                : 'subject_coordinator';
        }

        // ----- Higher Ed (undergrad/grad) -----
        $needsDean = $this->needsDeanEscalation($ctx, $totalUnits);

        if ($needsDean) {
            return 'dean';
        }

        // Irregular / non-degree / cross-enrollee → program head
        if (in_array($ctx->enrolleeType, ['irregular', 'transferee', 'returnee', 'special', 'cross_enrollee'], true)
            || $ctx->programType !== 'regular') {
            return 'program_head';
        }

        return 'auto';
    }

    protected function needsDeanEscalation(EnrolmentContext $ctx, ?float $totalUnits): bool
    {
        // Overload escalates to dean
        if ($totalUnits !== null) {
            $policy = $this->policies->resolve(
                $ctx->effectiveSchoolId(),
                $ctx->educationLevel,
                $ctx->programId,
                $ctx->term->id
            );

            if ($policy->overload_threshold_units
                && $totalUnits > (float) $policy->overload_threshold_units) {
                return true;
            }
        }

        // Cross-enrollee always touches the dean for academic concurrence
        if ($ctx->enrolleeType === 'cross_enrollee') {
            return true;
        }

        return false;
    }
}
