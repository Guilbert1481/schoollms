<?php

namespace App\Services\Finance;

use App\Models\FinanceFeeSetup;
use App\Models\PaymentPlan;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Support\EducationLevels;
use Illuminate\Support\Carbon;

/**
 * Computes the read-only fee breakdown and the payment schedule shown on the
 * enrolment Financial Assessment step. No persistence — it never touches the
 * ledger or invoices; it only previews what a chosen payment plan would cost.
 */
class PaymentScheduleService
{
    /** Months advanced per installment for each billing frequency. */
    public const PERIOD_MONTHS = ['monthly' => 1, 'quarterly' => 3, 'semi_annual' => 6];

    public const FREQ_LABELS = ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'semi_annual' => 'Semi-Annual'];

    public const OPTION_TITLES = [
        'cash'        => 'Cash (Full Payment)',
        'downpayment' => 'Down Payment + Installment',
        'installment' => 'Installment + Interest',
    ];

    /**
     * Fee line items for an (possibly unsaved) enrolment, using the same
     * matching rules as InvoiceService::applicableFees but without persisting.
     *
     * @return array{items: \Illuminate\Support\Collection, total: float}
     */
    public function feePreview(StudentEnrollment $enrollment): array
    {
        $units = (float) ($enrollment->total_units ?? 0);

        $rows = FinanceFeeSetup::query()
            ->where('school_id', $enrollment->school_id)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('academic_year_id')->orWhere('academic_year_id', $enrollment->academic_year_id))
            ->where(fn ($q) => $q->whereNull('term_id')->orWhere('term_id', $enrollment->term_id))
            ->where(fn ($q) => $q->whereNull('education_node_id')->orWhereIn('education_node_id', EducationLevels::ancestorIds((int) $enrollment->education_node_id) ?: [-1]))
            ->where(fn ($q) => $q->whereNull('program_id')->orWhere('program_id', $enrollment->program_id))
            ->where(fn ($q) => $q->whereNull('year_level')->orWhere('year_level', $enrollment->year_level))
            ->orderByRaw("CASE WHEN fee_type = 'tuition' THEN 0 ELSE 1 END") // tuition first
            ->orderBy('fee_type')->orderBy('code')
            ->get();

        // Default + strand/grade overrides: only the most-specific tuition applies.
        $rows = FinanceFeeSetup::keepMostSpecificTuition($rows, (int) $enrollment->education_node_id);

        $items = $rows->map(function (FinanceFeeSetup $fee) use ($units) {
            $qty = $fee->billing_basis === 'per_unit' ? max($units, 0) : 1.0;

            return (object) [
                'label'    => $fee->name,
                'fee_type' => FinanceFeeSetup::FEE_TYPES[$fee->fee_type] ?? ucfirst((string) $fee->fee_type),
                'amount'   => round($qty * (float) $fee->amount, 2),
            ];
        })->values();

        return ['items' => $items, 'total' => round((float) $items->sum('amount'), 2)];
    }

    /**
     * The options a plan offers, in display order. Each enabled option becomes
     * its own selectable card — a plan with all three enabled shows three cards.
     *
     * @return string[]
     */
    public function enabledOptions(PaymentPlan $plan): array
    {
        $options = [];
        if ($plan->cash_enabled) {
            $options[] = 'cash';
        }
        if ($plan->dp_enabled) {
            $options[] = 'downpayment';
        }
        if ($plan->interest_enabled) {
            $options[] = 'installment';
        }

        return $options;
    }

    /** Preferred billing frequency for a plan (monthly first, else the first set). */
    public function planFrequency(PaymentPlan $plan): string
    {
        $freqs = is_array($plan->frequencies) ? $plan->frequencies : [];
        if (in_array('monthly', $freqs, true)) {
            return 'monthly';
        }

        return $freqs[0] ?? 'monthly';
    }

    /** Number of installments across the term at the given frequency. */
    public function installmentCount(?Term $term, string $frequency): int
    {
        $months = self::PERIOD_MONTHS[$frequency] ?? 1;

        if ($term && $term->start_date && $term->end_date) {
            $span = (int) Carbon::parse($term->start_date)->diffInMonths(Carbon::parse($term->end_date));

            return max(1, (int) ceil(max(1, $span) / $months));
        }

        // Fallback: spread over a ~10-month school year.
        return max(1, (int) ceil(10 / $months));
    }

    /**
     * Live figures + dated schedule for ONE option of a plan against a fee total.
     * Each enabled option is computed independently — a guardian picks exactly one.
     *
     * @param  string  $option  cash | downpayment | installment
     * @return array<string, mixed>
     */
    public function computeForOption(PaymentPlan $plan, string $option, float $totalFees, ?Term $term): array
    {
        $mode = $option;
        $freq = $this->planFrequency($plan);

        // Installment dates come from the plan's class/billing schedule (or the
        // term, as a fallback). The number of installments is whatever fits.
        [$classStart, $dueDates] = $this->scheduleDates($plan, $term, $freq);
        $count = max(1, count($dueDates));

        $discount = 0.0;
        $downpayment = 0.0;
        $interest = 0.0;
        $remaining = 0.0;
        $schedule = [];

        if ($mode === 'cash') {
            $discount = min($this->resolve($plan->cash_discount_type, (float) $plan->cash_discount_value, $totalFees), $totalFees);
            $net      = round($totalFees - $discount, 2);
            $count    = 1;
            $perInstallment = $net;
            $totalDue = $net;
            $schedule[] = ['due' => $classStart->copy(), 'description' => 'Full Payment', 'amount' => $net];
        } elseif ($mode === 'downpayment') {
            // Down Payment + Installment is its own option: down payment, then the
            // remaining balance split evenly. Interest belongs to the separate
            // "Installment + Interest" option only.
            $downpayment = min($this->resolve($plan->down_payment_type, (float) $plan->down_payment_value, $totalFees), $totalFees);
            $remaining   = round($totalFees - $downpayment, 2);
            $base           = $remaining;
            $perInstallment = round($base / $count, 2);
            $totalDue       = round($downpayment + $base, 2);

            $schedule[] = ['due' => $classStart->copy(), 'description' => 'Downpayment', 'amount' => $downpayment];
            $this->appendDatedInstallments($schedule, $dueDates, $base, $perInstallment);
        } else { // installment (+ optional interest)
            if ($plan->interest_enabled) {
                $interest = round($totalFees * (float) $plan->interest_rate / 100, 2);
            }
            $base           = round($totalFees + $interest, 2);
            $remaining      = $base;
            $perInstallment = round($base / $count, 2);
            $totalDue       = $base;

            $this->appendDatedInstallments($schedule, $dueDates, $base, $perInstallment);
        }

        $freqLabel  = self::FREQ_LABELS[$freq] ?? ucfirst($freq);
        $descriptor = $this->optionDescriptor($mode, $count, $freqLabel, round($discount, 2), round($downpayment, 2), round($interest, 2), (float) $plan->interest_rate);

        return [
            'plan_id'          => $plan->id,
            'plan_name'        => $plan->name,
            'option'           => $mode,
            'mode'             => $mode,
            'title'            => self::OPTION_TITLES[$mode] ?? ucfirst($mode),
            'descriptor'       => $descriptor,
            'frequency'        => $freq,
            'frequency_label'  => $freqLabel,
            'total_fees'       => round($totalFees, 2),
            'discount'         => round($discount, 2),
            'interest'         => round($interest, 2),
            'downpayment'      => round($downpayment, 2),
            'remaining'        => round($remaining, 2),
            'num_installments' => $count,
            'per_installment'  => round($perInstallment, 2),
            'total_due'        => round($totalDue, 2),
            'schedule'         => array_map(fn ($r) => [
                'due_date'    => $r['due']->format('M d, Y'),
                'description' => $r['description'],
                'amount'      => round($r['amount'], 2),
            ], $schedule),
            // Raw Carbon rows — the billing source of truth so InvoiceService can
            // issue one dated invoice per scheduled payment (identical figures to
            // the formatted 'schedule' the Financial Assessment renders).
            'schedule_raw'     => array_map(fn ($r) => [
                'due'         => $r['due']->copy(),
                'description' => $r['description'],
                'amount'      => round($r['amount'], 2),
            ], $schedule),
        ];
    }

    /** Short line shown under each option card. */
    protected function optionDescriptor(string $mode, int $count, string $freqLabel, float $discount, float $downpayment, float $interest, float $interestRate): string
    {
        if ($mode === 'cash') {
            return $discount > 0 ? '✓ ₱'.number_format($discount, 2).' discount' : 'One-time full payment';
        }
        if ($mode === 'downpayment') {
            return '₱'.number_format($downpayment, 2).' down · '.$count.' '.$freqLabel.' installments';
        }
        $rate = rtrim(rtrim(number_format($interestRate, 2), '0'), '.');

        return $count.' '.$freqLabel.' installments'.($interest > 0 ? ' · +'.$rate.'% interest' : '');
    }

    /** Append the dated installments, the last absorbing any rounding remainder. */
    protected function appendDatedInstallments(array &$schedule, array $dueDates, float $base, float $per): void
    {
        $count   = count($dueDates);
        $running = 0.0;
        foreach ($dueDates as $i => $due) {
            $n      = $i + 1;
            $amount = ($n === $count) ? round($base - $running, 2) : $per;
            $running = round($running + $amount, 2);
            $schedule[] = [
                'due'         => $due->copy(),
                'description' => "Installment {$n} of {$count}",
                'amount'      => $amount,
            ];
        }
    }

    /**
     * Class-start anchor + the dated installment due dates for a plan. Each
     * installment falls on the plan's billing day, starting one period after
     * class start, through the billing/SOA end date (else class end / term end).
     *
     * @return array{0: Carbon, 1: Carbon[]}
     */
    protected function scheduleDates(PaymentPlan $plan, ?Term $term, string $freq): array
    {
        $pm = self::PERIOD_MONTHS[$freq] ?? 1;

        $cs = $plan->class_start_date
            ? Carbon::parse($plan->class_start_date)->startOfDay()
            : (($term && $term->start_date) ? Carbon::parse($term->start_date)->startOfDay() : Carbon::now()->startOfDay());

        $end = $plan->billing_end_date ?: ($plan->class_end_date ?: (($term && $term->end_date) ? $term->end_date : null));
        $be  = $end ? Carbon::parse($end)->endOfDay() : $cs->copy()->addMonthsNoOverflow(10 * $pm)->endOfDay();

        $day = ($plan->billing_day >= 1 && $plan->billing_day <= 31) ? (int) $plan->billing_day : (int) $cs->day;

        // First installment month = one period after class start.
        $cursorMonth = $cs->copy()->startOfMonth()->addMonthsNoOverflow($pm);

        $dates = [];
        for ($i = 0; $i < 120; $i++) {
            $due = $this->onDay($cursorMonth, $day);
            if ($due->gt($be)) {
                break;
            }
            $dates[] = $due;
            $cursorMonth = $cursorMonth->copy()->addMonthsNoOverflow($pm);
        }

        if (empty($dates)) {
            // Window too short for a full period — at least one installment.
            $dates[] = $this->onDay($cs->copy()->startOfMonth()->addMonthsNoOverflow($pm), $day);
        }

        return [$cs, $dates];
    }

    /** A Carbon on the given day-of-month, clamped to that month's length. */
    protected function onDay(Carbon $monthAnchor, int $day): Carbon
    {
        $d = min(max(1, $day), $monthAnchor->daysInMonth);

        return $monthAnchor->copy()->day($d)->startOfDay();
    }

    /** A percentage-or-fixed value against a base. */
    protected function resolve(?string $type, float $value, float $base): float
    {
        return $type === 'fixed' ? round($value, 2) : round($base * $value / 100, 2);
    }
}
