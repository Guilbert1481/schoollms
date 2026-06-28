<?php

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\StudentEnrollment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The student-ledger engine. Every financial event becomes an append-only
 * LedgerEntry carrying a running balance so a Statement of Account can be
 * reconstructed for any period.
 *
 * Convention: positive balance = the student owes the school.
 *   charge  -> debit  (balance up)
 *   payment -> credit (balance down)
 */
class LedgerService
{
    /** Current running balance for a student at a school (0 if no entries). */
    public function currentBalance(int $schoolId, int $studentId): float
    {
        $last = LedgerEntry::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->orderByDesc('id')
            ->first();

        return $last ? (float) $last->balance_after : 0.0;
    }

    /**
     * Append a ledger entry, computing balance_after atomically against the
     * latest entry for that student. Always call inside (or it will open) a
     * transaction so the running balance stays consistent under concurrency.
     */
    public function record(array $data): LedgerEntry
    {
        return DB::transaction(function () use ($data) {
            $schoolId  = (int) $data['school_id'];
            $studentId = (int) $data['student_id'];

            $last = LedgerEntry::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $previous = $last ? (float) $last->balance_after : 0.0;
            $debit    = round((float) ($data['debit'] ?? 0), 2);
            $credit   = round((float) ($data['credit'] ?? 0), 2);

            return LedgerEntry::create([
                'school_id'             => $schoolId,
                'student_id'            => $studentId,
                'student_enrollment_id' => $data['student_enrollment_id'] ?? null,
                'invoice_id'            => $data['invoice_id'] ?? null,
                'payment_id'            => $data['payment_id'] ?? null,
                'academic_year_id'      => $data['academic_year_id'] ?? null,
                'term_id'               => $data['term_id'] ?? null,
                'entry_date'            => $data['entry_date'] ?? Carbon::now()->toDateString(),
                'type'                  => $data['type'] ?? LedgerEntry::TYPE_ADJUSTMENT,
                'description'           => $data['description'] ?? '',
                'reference'             => $data['reference'] ?? null,
                'debit'                 => $debit,
                'credit'                => $credit,
                'balance_after'         => round($previous + $debit - $credit, 2),
                'created_by'            => $data['created_by'] ?? null,
            ]);
        });
    }

    /** Post the charge for a freshly issued invoice. */
    public function postInvoiceCharge(Invoice $invoice, ?int $actorId = null): LedgerEntry
    {
        return $this->record([
            'school_id'             => (int) $invoice->school_id,
            'student_id'            => (int) $invoice->student_id,
            'student_enrollment_id' => $invoice->student_enrollment_id,
            'invoice_id'            => $invoice->id,
            'academic_year_id'      => $invoice->academic_year_id,
            'term_id'               => $invoice->term_id,
            'entry_date'            => optional($invoice->issue_date)->toDateString() ?? Carbon::now()->toDateString(),
            'type'                  => LedgerEntry::TYPE_CHARGE,
            'description'           => 'Invoice '.$invoice->invoice_number,
            'reference'             => $invoice->invoice_number,
            'debit'                 => (float) $invoice->total_amount,
            'created_by'            => $actorId,
        ]);
    }

    /** Post the credit for a recorded payment. */
    public function postPaymentCredit(Payment $payment, ?Invoice $invoice = null, ?int $actorId = null): LedgerEntry
    {
        return $this->record([
            'school_id'             => (int) $payment->school_id,
            'student_id'            => (int) $payment->student_id,
            'student_enrollment_id' => $invoice?->student_enrollment_id,
            'invoice_id'            => $payment->invoice_id ?? $invoice?->id,
            'payment_id'            => $payment->id,
            'academic_year_id'      => $invoice?->academic_year_id,
            'term_id'               => $invoice?->term_id,
            'entry_date'            => optional($payment->paid_at)->toDateString() ?? Carbon::now()->toDateString(),
            'type'                  => LedgerEntry::TYPE_PAYMENT,
            'description'           => $invoice
                ? 'Payment for '.$invoice->invoice_number
                : 'Payment ('.ucfirst((string) $payment->payment_type).')',
            'reference'             => $payment->reference_number,
            'credit'                => (float) $payment->amount,
            'created_by'            => $actorId,
        ]);
    }

    /** Post a manual adjustment (debit increases balance, credit decreases it). */
    public function postAdjustment(
        int $schoolId,
        int $studentId,
        float $debit,
        float $credit,
        string $description,
        ?int $actorId = null,
        ?int $enrollmentId = null
    ): LedgerEntry {
        return $this->record([
            'school_id'             => $schoolId,
            'student_id'            => $studentId,
            'student_enrollment_id' => $enrollmentId,
            'type'                  => LedgerEntry::TYPE_ADJUSTMENT,
            'description'           => $description,
            'debit'                 => $debit,
            'credit'                => $credit,
            'created_by'            => $actorId,
        ]);
    }
}
