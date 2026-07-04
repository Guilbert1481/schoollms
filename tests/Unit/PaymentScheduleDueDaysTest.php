<?php

namespace Tests\Unit;

use App\Models\PaymentPlan;
use App\Services\Finance\PaymentScheduleService;
use Tests\TestCase;

/**
 * Guards the per-plan "Days to Due Date" grace window: a bill is issued on its
 * billing day but falls due `due_days` later — for cash, the down payment and
 * every installment alike. Pure schedule math (no DB). Money path — see the
 * MODERNIZATION governance docs.
 */
class PaymentScheduleDueDaysTest extends TestCase
{
    /** An unsaved plan: monthly, billing day 8, ₱20k fixed down payment. */
    private function plan(int $dueDays): PaymentPlan
    {
        $plan = new PaymentPlan();
        $plan->school_id          = 1;
        $plan->frequencies        = ['monthly'];
        $plan->class_start_date   = '2026-06-27';
        $plan->billing_end_date   = '2027-04-30';
        $plan->billing_day        = 8;
        $plan->due_days           = $dueDays;
        $plan->cash_discount_type = 'fixed';
        $plan->cash_discount_value = 0;
        $plan->down_payment_type  = 'fixed';
        $plan->down_payment_value = 20000;
        $plan->interest_enabled   = false;
        $plan->interest_rate      = 0;

        return $plan;
    }

    private function svc(): PaymentScheduleService
    {
        return new PaymentScheduleService();
    }

    public function test_installment_due_is_billing_day_plus_due_days(): void
    {
        $rows = $this->svc()->computeForOption($this->plan(5), 'installment', 100000, null)['schedule_raw'];

        $this->assertNotEmpty($rows);
        $this->assertSame('2026-07-08', $rows[0]['bill']->toDateString(), 'first installment bills on the 8th');
        $this->assertSame('2026-07-13', $rows[0]['due']->toDateString(), 'and falls due on the 13th (8 + 5)');

        foreach ($rows as $r) {
            $this->assertSame(8, (int) $r['bill']->day, 'every installment bills on the 8th');
            $this->assertSame(13, (int) $r['due']->day, 'every installment falls due on the 13th');
        }
    }

    public function test_zero_due_days_keeps_due_on_billing_day(): void
    {
        $rows = $this->svc()->computeForOption($this->plan(0), 'installment', 100000, null)['schedule_raw'];

        foreach ($rows as $r) {
            $this->assertSame($r['bill']->toDateString(), $r['due']->toDateString(), 'with 0 grace, due == billing date');
            $this->assertSame(8, (int) $r['due']->day);
        }
    }

    public function test_offset_applies_to_downpayment_and_cash_rows(): void
    {
        $dpFirst = $this->svc()->computeForOption($this->plan(5), 'downpayment', 100000, null)['schedule_raw'][0];
        $this->assertStringContainsStringIgnoringCase('down', $dpFirst['description']);
        $this->assertSame('2026-06-27', $dpFirst['bill']->toDateString(), 'down payment bills on the class-start date');
        $this->assertSame('2026-07-02', $dpFirst['due']->toDateString(), 'down payment falls due 5 days later');

        $cashRow = $this->svc()->computeForOption($this->plan(5), 'cash', 100000, null)['schedule_raw'][0];
        $this->assertSame('2026-06-27', $cashRow['bill']->toDateString(), 'cash full payment bills on the class-start date');
        $this->assertSame('2026-07-02', $cashRow['due']->toDateString(), 'cash full payment falls due 5 days later');
    }
}
