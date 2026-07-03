<?php

namespace App\Services\Finance;

use App\Models\FinanceFeeSetup;
use App\Models\FinanceSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentPlan;
use App\Models\StudentEnrollment;
use App\Support\EducationLevels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds the invoice(s) for an enrollment from the active fee setups, persists
 * the line items, and posts the resulting charge to the student ledger. This is
 * the bridge between "fees defined" and "student owes money".
 *
 * Billing follows the payment plan the applicant chose on the Financial
 * Assessment step:
 *   - Cash / no plan   → ONE itemised invoice for the net payable.
 *   - Down payment +   → ONE invoice PER scheduled payment (the down payment
 *     installment /       dated now, each installment dated to its due date),
 *     installment         using the exact same PaymentScheduleService math the
 *                         applicant saw. The gross is never billed in one lump.
 */
class InvoiceService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly PaymentScheduleService $schedules = new PaymentScheduleService(),
    ) {
    }

    /**
     * Fee setups that apply to an enrollment. A null scope column on a fee is a
     * wildcard (applies to everyone); a set column must match the enrollment.
     */
    public function applicableFees(StudentEnrollment $enrollment): Collection
    {
        $rows = FinanceFeeSetup::query()
            ->where('school_id', $enrollment->school_id)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('academic_year_id')->orWhere('academic_year_id', $enrollment->academic_year_id))
            ->where(fn ($q) => $q->whereNull('term_id')->orWhere('term_id', $enrollment->term_id))
            ->where(fn ($q) => $q->whereNull('education_node_id')->orWhereIn('education_node_id', EducationLevels::ancestorIds((int) $enrollment->education_node_id) ?: [-1]))
            ->where(fn ($q) => $q->whereNull('program_id')->orWhere('program_id', $enrollment->program_id))
            ->where(fn ($q) => $q->whereNull('year_level')->orWhere('year_level', $enrollment->year_level))
            ->orderByRaw("CASE WHEN fee_type = 'tuition' THEN 0 ELSE 1 END")
            ->orderBy('fee_type')
            ->orderBy('code')
            ->get();

        // Default + strand/grade overrides: only the most-specific tuition is billed
        // (keeps the invoice in lockstep with the Financial Assessment preview).
        return FinanceFeeSetup::keepMostSpecificTuition($rows, (int) $enrollment->education_node_id);
    }

    /**
     * Generate the invoice(s) for an enrollment. Strictly idempotent: the whole
     * schedule is generated once. If any invoice already exists for the
     * enrollment the first is returned unchanged so re-assessing (or a double
     * submit) never double-charges the ledger. Re-issuing is intentionally a
     * separate void+reissue concern.
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
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $fees = $this->applicableFees($enrollment);
        if ($fees->isEmpty()) {
            return null;
        }

        $units     = (float) ($enrollment->total_units ?? 0);
        [$lineItems, $gross] = $this->buildLineItems($fees, $units);

        // Scholarship persisted on the enrollment at submit (0 when none applies).
        $scholarship = min(max(round((float) ($enrollment->scholarship_amount ?? 0), 2), 0), $gross);
        $net         = round($gross - $scholarship, 2);

        // Resolve the applicant's chosen plan + option so billing follows the
        // same schedule the Financial Assessment previewed.
        $plan   = $enrollment->payment_plan_id
            ? PaymentPlan::where('school_id', $enrollment->school_id)->find($enrollment->payment_plan_id)
            : null;
        $option = (string) ($enrollment->payment_option ?? '');
        $comp   = ($plan && $option !== '')
            ? $this->schedules->computeForOption($plan, $option, $net, $enrollment->term)
            : null;

        return DB::transaction(function () use (
            $enrollment, $studentUserId, $lineItems, $gross, $scholarship, $net, $comp, $option, $plan, $actorId
        ) {
            // Down payment + installment (or installment): one invoice per dated
            // schedule row — but only when there is something to spread. A full
            // scholarship (net 0) falls through to a single settled invoice.
            if ($comp && $net > 0.005 && in_array($option, ['downpayment', 'installment'], true)) {
                return $this->createScheduledInvoices($enrollment, $studentUserId, $comp, $plan, $scholarship, $actorId);
            }

            // Cash / no plan / fully-covered: a single itemised invoice for the net
            // payable. When the plan grants a cash discount, that is folded in too.
            $total = ($comp && $net > 0.005) ? (float) $comp['total_due'] : $net;

            return $this->createItemisedInvoice($enrollment, $studentUserId, $lineItems, $gross, $total, $actorId);
        });
    }

    /**
     * Turn matched fee setups into invoice-item payloads + the gross subtotal.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     */
    protected function buildLineItems(Collection $fees, float $units): array
    {
        $items = [];
        $gross = 0.0;

        foreach ($fees as $fee) {
            $isPerUnit = $fee->billing_basis === 'per_unit';
            $quantity  = $isPerUnit ? max($units, 0) : 1.0;
            $unit      = (float) $fee->amount;
            $amount    = round($quantity * $unit, 2);

            $items[] = [
                'finance_fee_setup_id' => $fee->id,
                'fee_type'             => $fee->fee_type,
                'description'          => $fee->name,
                'billing_basis'        => $fee->billing_basis,
                'quantity'             => $quantity,
                'unit_amount'          => $unit,
                'amount'               => $amount,
                'discount_amount'      => 0,
                'net_amount'           => $amount,
            ];

            $gross += $amount;
        }

        return [$items, round($gross, 2)];
    }

    /**
     * A single itemised invoice (cash / no-plan). The scholarship + any cash
     * discount surface as the header discount so Subtotal − Discount = Total.
     */
    protected function createItemisedInvoice(
        StudentEnrollment $enrollment,
        int $studentUserId,
        array $lineItems,
        float $gross,
        float $total,
        ?int $actorId
    ): Invoice {
        $setting   = FinanceSetting::forSchool((int) $enrollment->school_id);
        $issueDate = Carbon::now();
        $dueDate   = $enrollment->payment_due_at
            ? Carbon::parse($enrollment->payment_due_at)
            : $issueDate->copy()->addDays((int) $setting->invoice_due_days);

        $total    = round(max($total, 0), 2);
        $discount = round(max($gross - $total, 0), 2);

        $invoice = Invoice::create([
            'invoice_number'        => $this->generateInvoiceNumber((int) $enrollment->school_id),
            'school_id'             => $enrollment->school_id,
            'student_id'            => $studentUserId,
            'student_enrollment_id' => $enrollment->id,
            'academic_year_id'      => $enrollment->academic_year_id,
            'term_id'               => $enrollment->term_id,
            'subtotal_amount'       => $gross,
            'discount_amount'       => $discount,
            'total_amount'          => $total,
            'paid_amount'           => 0,
            'balance'               => $total,
            // A fully-covered assessment (net 0 after scholarship) has nothing to
            // collect — surface it as settled rather than a red "Unpaid".
            'status'                => $total <= 0.005 ? Invoice::STATUS_PAID : Invoice::STATUS_UNPAID,
            'issue_date'            => $issueDate->toDateString(),
            'due_date'              => $dueDate->toDateString(),
            'issued_by'             => $actorId,
            'notes'                 => $enrollment->scholarship_label
                ? 'Net of '.$enrollment->scholarship_label
                : null,
        ]);

        foreach ($lineItems as $li) {
            InvoiceItem::create(array_merge($li, [
                'invoice_id' => $invoice->id,
                'school_id'  => $enrollment->school_id,
            ]));
        }

        $this->ledger->postInvoiceCharge($invoice->fresh(), $actorId);

        return $invoice->fresh(['items']);
    }

    /**
     * One invoice per scheduled payment. The first row (the down payment) is
     * billed today; every installment is dated to its own due date. Line and
     * ledger figures reuse the exact schedule the applicant saw — the gross is
     * never billed in one lump.
     */
    protected function createScheduledInvoices(
        StudentEnrollment $enrollment,
        int $studentUserId,
        array $comp,
        PaymentPlan $plan,
        float $scholarship,
        ?int $actorId
    ): Invoice {
        $rows  = $comp['schedule_raw'] ?? [];
        $today = Carbon::now()->startOfDay();
        $first = null;

        foreach (array_values($rows) as $seq => $row) {
            $amount = round((float) $row['amount'], 2);

            // Skip zero rows (e.g. a down payment that already covers the whole
            // net leaves the installments at 0) — no point issuing ₱0 bills. The
            // net-0 case never reaches here (guarded upstream), so ≥1 row remains.
            if ($amount <= 0.005) {
                continue;
            }

            $isDownpayment = str_contains(strtolower((string) $row['description']), 'down');
            $isFirstBill   = $first === null;

            $due = $row['due'] instanceof Carbon ? $row['due']->copy() : Carbon::parse($row['due']);

            // The down payment (billed now) must never be born overdue by a
            // scheduled date that already passed.
            $dueDate = ($isDownpayment && $due->lt($today)) ? $today->copy() : $due;

            $invoice = Invoice::create([
                'invoice_number'        => $this->generateInvoiceNumber((int) $enrollment->school_id),
                'school_id'             => $enrollment->school_id,
                'student_id'            => $studentUserId,
                'student_enrollment_id' => $enrollment->id,
                'academic_year_id'      => $enrollment->academic_year_id,
                'term_id'               => $enrollment->term_id,
                'subtotal_amount'       => $amount,
                'discount_amount'       => 0,
                'total_amount'          => $amount,
                'paid_amount'           => 0,
                'balance'               => $amount,
                'status'                => Invoice::STATUS_UNPAID,
                'issue_date'            => $today->toDateString(),
                'due_date'              => $dueDate->toDateString(),
                'issued_by'             => $actorId,
                // The first bill of the schedule carries the plan summary + the
                // scholarship note so the finance manager sees the full picture.
                'notes'                 => $isFirstBill
                    ? trim($plan->name.' — '.$comp['descriptor'].($scholarship > 0 && $enrollment->scholarship_label ? ' · net of '.$enrollment->scholarship_label : ''))
                    : $plan->name,
            ]);

            InvoiceItem::create([
                'invoice_id'      => $invoice->id,
                'school_id'       => $enrollment->school_id,
                'fee_type'        => $isDownpayment ? 'downpayment' : 'installment',
                'description'     => $row['description'],
                'billing_basis'   => 'fixed',
                'quantity'        => 1,
                'unit_amount'     => $amount,
                'amount'          => $amount,
                'discount_amount' => 0,
                'net_amount'      => $amount,
            ]);

            $this->ledger->postInvoiceCharge($invoice->fresh(), $actorId);

            $first ??= $invoice;
        }

        return ($first ?? Invoice::query()
            ->where('student_enrollment_id', $enrollment->id)
            ->orderBy('id')->first())->fresh(['items']);
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
