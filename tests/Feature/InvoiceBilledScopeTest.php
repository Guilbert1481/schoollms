<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Billing page shows a bill once its billing date has arrived — current +
 * past (including everything still unpaid) — while installment invoices dated to
 * a future month stay hidden until then. Legacy rows without a billing_date are
 * treated as already billed so nothing pre-existing disappears. Money path —
 * see the MODERNIZATION governance docs.
 */
class InvoiceBilledScopeTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;
    private int $studentId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the parent rows the invoice FKs require. (MySQL — the test DB —
        // enforces these foreign keys; sqlite historically did not.)
        $school = School::factory()->create();
        $student = User::factory()->create([
            'school_id' => $school->id,
            'role'      => 'student',
        ]);

        $this->schoolId  = (int) $school->id;
        $this->studentId = (int) $student->id;
    }

    private function invoice(string $number, ?string $billingDate): Invoice
    {
        return Invoice::create([
            'invoice_number' => $number,
            'school_id'      => $this->schoolId,
            'student_id'     => $this->studentId,
            'total_amount'   => 1000,
            'paid_amount'    => 0,
            'balance'        => 1000,
            'status'         => Invoice::STATUS_UNPAID,
            'billing_date'   => $billingDate,
        ]);
    }

    public function test_billed_scope_shows_current_and_past_hides_future(): void
    {
        $past   = $this->invoice('INV-PAST',   now()->subDay()->toDateString());
        $today  = $this->invoice('INV-TODAY',  now()->toDateString());
        $future = $this->invoice('INV-FUTURE', now()->addMonth()->toDateString());
        $legacy = $this->invoice('INV-LEGACY', null);

        $this->assertSame(4, Invoice::count(), 'all four exist unfiltered');

        $billed = Invoice::billed()->pluck('id');

        $this->assertCount(3, $billed);
        $this->assertContains($past->id,   $billed->all(), 'past bill shows');
        $this->assertContains($today->id,  $billed->all(), "today's bill shows");
        $this->assertContains($legacy->id, $billed->all(), 'legacy (null billing_date) shows');
        $this->assertNotContains($future->id, $billed->all(), 'future-dated bill is hidden until its billing date');
    }
}
