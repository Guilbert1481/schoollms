<?php

namespace App\Services\Finance;

use App\Models\FinanceFeeSetup;
use App\Models\FinanceSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\StudentEnrollment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds an itemised invoice for an enrollment from the active fee setups,
 * persists the line items, and posts the resulting charge to the student
 * ledger. This is the bridge between "fees defined" and "student owes money".
 */
class InvoiceService
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    /**
     * Fee setups that apply to an enrollment. A null scope column on a fee is a
     * wildcard (applies to everyone); a set column must match the enrollment.
     */
    public function applicableFees(StudentEnrollment $enrollment): Collection
    {
        return FinanceFeeSetup::query()
            ->where('school_id', $enrollment->school_id)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('academic_year_id')->orWhere('academic_year_id', $enrollment->academic_year_id))
            ->where(fn ($q) => $q->whereNull('term_id')->orWhere('term_id', $enrollment->term_id))
            ->where(fn ($q) => $q->whereNull('education_node_id')->orWhere('education_node_id', $enrollment->education_node_id))
            ->where(fn ($q) => $q->whereNull('program_id')->orWhere('program_id', $enrollment->program_id))
            ->where(fn ($q) => $q->whereNull('year_level')->orWhere('year_level', $enrollment->year_level))
            ->orderBy('fee_type')
            ->orderBy('code')
            ->get();
    }

    /**
     * Generate the invoice for an enrollment. Strictly idempotent: one invoice
     * per enrollment. If an invoice already exists it is returned unchanged so
     * re-assessing an enrollment (or a double submit) never double-charges the
     * ledger. Re-issuing is intentionally a separate void+reissue concern.
     *
     * Returns null when the enrollment cannot be invoiced — no linked user
     * account, or no applicable fees are configured yet.
     */
    public function generateForEnrollment(StudentEnrollment $enrollment, ?int $actorId = null): ?Invoice
    {
        $studentUserId = $enrollment->student?->user_id;
        if (! $studentUserId) {
            return null;
        }

        $existing = Invoice::query()
            ->where('school_id', $enrollment->school_id)
            ->where('student_enrollment_id', $enrollment->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $fees = $this->applicableFees($enrollment);
        if ($fees->isEmpty()) {
            return null;
        }

        $units = (float) ($enrollment->total_units ?? 0);

        return DB::transaction(function () use ($enrollment, $studentUserId, $fees, $units, $actorId) {
            $setting  = FinanceSetting::forSchool((int) $enrollment->school_id);
            $issueDate = Carbon::now();
            $dueDate   = $enrollment->payment_due_at
                ? Carbon::parse($enrollment->payment_due_at)
                : $issueDate->copy()->addDays((int) $setting->invoice_due_days);

            $invoice = Invoice::create([
                'invoice_number'        => $this->generateInvoiceNumber((int) $enrollment->school_id),
                'school_id'             => $enrollment->school_id,
                'student_id'            => $studentUserId,
                'student_enrollment_id' => $enrollment->id,
                'academic_year_id'      => $enrollment->academic_year_id,
                'term_id'               => $enrollment->term_id,
                'subtotal_amount'       => 0,
                'discount_amount'       => 0,
                'total_amount'          => 0,
                'paid_amount'           => 0,
                'balance'               => 0,
                'status'                => Invoice::STATUS_UNPAID,
                'issue_date'            => $issueDate->toDateString(),
                'due_date'              => $dueDate->toDateString(),
                'issued_by'             => $actorId,
            ]);

            $subtotal = 0.0;

            foreach ($fees as $fee) {
                $isPerUnit = $fee->billing_basis === 'per_unit';
                $quantity  = $isPerUnit ? max($units, 0) : 1.0;
                $unit      = (float) $fee->amount;
                $amount    = round($quantity * $unit, 2);

                InvoiceItem::create([
                    'invoice_id'           => $invoice->id,
                    'school_id'            => $enrollment->school_id,
                    'finance_fee_setup_id' => $fee->id,
                    'fee_type'             => $fee->fee_type,
                    'description'          => $fee->name,
                    'billing_basis'        => $fee->billing_basis,
                    'quantity'             => $quantity,
                    'unit_amount'          => $unit,
                    'amount'               => $amount,
                    'discount_amount'      => 0,
                    'net_amount'           => $amount,
                ]);

                $subtotal += $amount;
            }

            $subtotal = round($subtotal, 2);

            $invoice->update([
                'subtotal_amount' => $subtotal,
                'discount_amount' => 0,
                'total_amount'    => $subtotal,
                'balance'         => $subtotal,
            ]);

            // Reflect the assessment on the student's individual ledger.
            $this->ledger->postInvoiceCharge($invoice->fresh(), $actorId);

            return $invoice->fresh(['items']);
        });
    }

    public function generateInvoiceNumber(int $schoolId): string
    {
        $prefix = 'INV-'.Carbon::now()->format('Ymd').'-';

        do {
            $candidate = $prefix.strtoupper(Str::random(6));
            $exists = Invoice::query()
                ->where('school_id', $schoolId)
                ->where('invoice_number', $candidate)
                ->exists();
        } while ($exists);

        return $candidate;
    }
}
