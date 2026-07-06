<?php

namespace App\Services\Enrollment;

use App\Models\Invoice;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

/**
 * Turns gate-approved enrollments into official ones.
 *
 * The admission/registrar gate tags each enrollment via
 * student_enrollments.billing_cleared_as:
 *   'assessed'    — required documents complied
 *   'provisional' — important documents still missing
 *
 * Finance then makes it official, two ways:
 *   - automatically, when a verified payment settles the enrollment's
 *     first-due invoice (cash invoice / downpayment / first installment):
 *     docs complied → ENROLLED, docs missing → PROVISIONALLY_ENROLLED.
 *     A payment that doesn't settle it parks the row at PARTIALLY_PAID.
 *   - manually via decide(), with or without payment (scholarships,
 *     promissory notes, discretion) — always audited.
 *
 * When the registrar later marks the missing documents complied,
 * completeDocuments() upgrades the tag and promotes an already-paid
 * PROVISIONALLY_ENROLLED student to ENROLLED.
 */
class EnrollmentActivationService
{
    /** Enrollment statuses that a payment may advance. */
    private const PAYABLE_STATUSES = [
        StudentEnrollment::STATUS_SENT_BILLING,
        StudentEnrollment::STATUS_BILLED,
        StudentEnrollment::STATUS_PARTIALLY_PAID,
    ];

    /** Manual finance decisions and the status each maps to. */
    public const DECISIONS = [
        'officially'    => StudentEnrollment::STATUS_ENROLLED,
        'provisionally' => StudentEnrollment::STATUS_PROVISIONALLY_ENROLLED,
        'rejected'      => StudentEnrollment::STATUS_REJECTED,
    ];

    /** Called after a payment credits $invoice; advances its enrollment if due. */
    public function syncAfterPayment(Invoice $invoice, int $actorId): void
    {
        if (! $invoice->student_enrollment_id) {
            return;
        }

        $enrollment = StudentEnrollment::find($invoice->student_enrollment_id);
        if (! $enrollment || ! in_array($enrollment->status, self::PAYABLE_STATUSES, true)) {
            return;
        }

        if ($this->firstDueInvoiceSettled($enrollment)) {
            $this->activate($enrollment, $actorId, 'payment received');

            return;
        }

        if ($enrollment->status !== StudentEnrollment::STATUS_PARTIALLY_PAID) {
            $enrollment->update(['status' => StudentEnrollment::STATUS_PARTIALLY_PAID]);
        }
    }

    /**
     * Manual finance decision — allowed with or without payment.
     *
     * @param string $decision one of the DECISIONS keys.
     */
    public function decide(StudentEnrollment $enrollment, string $decision, int $actorId, ?string $note = null): string
    {
        $status = self::DECISIONS[$decision]
            ?? throw new \InvalidArgumentException("Unknown enrollment decision [$decision].");

        $label = match ($status) {
            StudentEnrollment::STATUS_ENROLLED => 'Officially enrolled by Finance (manual decision).',
            StudentEnrollment::STATUS_PROVISIONALLY_ENROLLED => 'Provisionally enrolled by Finance (manual decision).',
            default => 'Rejected by Finance (manual decision).',
        };

        $this->applyStatus($enrollment, $status, $actorId, 'finance_manual', $label, $note);

        return $status;
    }

    /**
     * Registrar marks the missing documents as complied: the gate tag becomes
     * 'assessed', and a student who already met the payment condition is
     * promoted from provisionally_enrolled to enrolled.
     */
    public function completeDocuments(StudentEnrollment $enrollment, int $actorId, ?string $note = null): void
    {
        $enrollment->update(['billing_cleared_as' => 'assessed']);

        if ($enrollment->status === StudentEnrollment::STATUS_PROVISIONALLY_ENROLLED) {
            $this->applyStatus(
                $enrollment,
                StudentEnrollment::STATUS_ENROLLED,
                $actorId,
                'registrar_documents',
                'Documents complied — promoted to officially enrolled.',
                $note
            );

            return;
        }

        $this->appendRemark($enrollment, 'Documents complied.', $note, $actorId);
    }

    /** Officially or provisionally enroll, per the gate's document verdict. */
    private function activate(StudentEnrollment $enrollment, int $actorId, string $via): void
    {
        $docsComplied = $enrollment->billing_cleared_as === 'assessed';

        $this->applyStatus(
            $enrollment,
            $docsComplied
                ? StudentEnrollment::STATUS_ENROLLED
                : StudentEnrollment::STATUS_PROVISIONALLY_ENROLLED,
            $actorId,
            'finance_payment',
            $docsComplied
                ? 'Officially enrolled — '.$via.'.'
                : 'Provisionally enrolled — '.$via.'; documents pending.',
            null
        );
    }

    /**
     * The downpayment condition: the enrollment's earliest-billed invoice is
     * fully settled. Matches every plan — the single cash invoice, the
     * downpayment invoice, or installment #1.
     */
    private function firstDueInvoiceSettled(StudentEnrollment $enrollment): bool
    {
        $first = DB::table('invoices')
            ->where('school_id', (int) $enrollment->school_id)
            ->where('student_enrollment_id', $enrollment->id)
            ->orderBy('billing_date')
            ->orderBy('id')
            ->first(['balance']);

        return $first !== null && (float) $first->balance <= 0.005;
    }

    private function applyStatus(
        StudentEnrollment $enrollment,
        string $status,
        int $actorId,
        string $approvalLevel,
        string $auditLabel,
        ?string $note
    ): void {
        $enrollment->update([
            'status'         => $status,
            'approved_by'    => $actorId,
            'approved_at'    => now(),
            'approval_level' => $approvalLevel,
            'remarks'        => $this->mergedRemarks($enrollment, $auditLabel, $note),
        ]);
    }

    private function appendRemark(StudentEnrollment $enrollment, string $auditLabel, ?string $note, int $actorId): void
    {
        $enrollment->update([
            'approved_by' => $actorId,
            'approved_at' => now(),
            'remarks'     => $this->mergedRemarks($enrollment, $auditLabel, $note),
        ]);
    }

    /** Append to the remarks audit trail instead of overwriting it. */
    private function mergedRemarks(StudentEnrollment $enrollment, string $auditLabel, ?string $note): string
    {
        $entry = '['.now()->format('Y-m-d H:i').'] '.$auditLabel.($note ? ' Note: '.trim($note) : '');
        $prior = trim((string) $enrollment->remarks);

        return $prior === '' ? $entry : $prior."\n".$entry;
    }
}
