<?php

namespace App\Modules\AcadEnrolment\Services\Validation;

use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentValidator;
use App\Modules\AcadEnrolment\Services\Contracts\ValidationResult;
use App\Modules\AcadEnrolment\Services\Policy\AcademicPolicyResolver;
use Illuminate\Support\Facades\DB;

/**
 * If the resolved policy requires payment to enrol, checks that the
 * student has paid at least `min_payment_percent` of the assessed
 * amount for this enrolment.
 *
 * Acts as the gate between status=billed/partially_paid and status=enrolled.
 */
class PaymentGateValidator implements EnrolmentValidator
{
    public function __construct(protected AcademicPolicyResolver $policies) {}

    public function key(): string
    {
        return 'payment_gate';
    }

    public function validate(EnrolmentContext $ctx): ValidationResult
    {
        $policy = $this->policies->resolve(
            $ctx->effectiveSchoolId(),
            $ctx->educationLevel,
            $ctx->programId,
            $ctx->term->id
        );

        if (!$policy->requires_payment_to_enrol) {
            return ValidationResult::pass();
        }

        if (!$ctx->enrollment) {
            // No enrolment row yet (still in selection) — gate not applicable.
            return ValidationResult::pass();
        }

        // Look at invoices linked to this enrolment via student_enrollment_id
        // (assumed FK; if the schema uses a different link this query needs to
        // be updated). We sum issued totals vs. payments received.
        $invoiceIds = DB::table('invoices')
            ->where('student_enrollment_id', $ctx->enrollment->id)
            ->pluck('id');

        if ($invoiceIds->isEmpty()) {
            return ValidationResult::fail(
                'Payment is required to enrol but no invoice has been issued yet.',
                ['enrollment_id' => $ctx->enrollment->id]
            );
        }

        $billed = (float) DB::table('invoices')->whereIn('id', $invoiceIds)->sum('total_amount');
        $paid   = (float) DB::table('payments')->whereIn('invoice_id', $invoiceIds)->sum('amount');

        if ($billed <= 0) {
            return ValidationResult::pass();
        }

        $required = $billed * ((float) $policy->min_payment_percent / 100);

        if ($paid + 0.01 < $required) {
            return ValidationResult::fail(
                sprintf(
                    'Minimum payment not met: %.2f required (%.0f%% of %.2f), %.2f paid.',
                    $required, $policy->min_payment_percent, $billed, $paid
                ),
                ['billed' => $billed, 'paid' => $paid, 'required' => $required]
            );
        }

        return ValidationResult::pass();
    }
}
