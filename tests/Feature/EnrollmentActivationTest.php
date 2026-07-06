<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The gate → finance activation flow: settling the first-due invoice
 * officially enrolls (documents complied) or provisionally enrolls
 * (documents pending); partial payments park at partially_paid; finance
 * can decide manually without payment; and the registrar marking the
 * documents complied promotes a paid provisional student to enrolled.
 */
class EnrollmentActivationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $finance;
    private User $registrar;
    private User $studentUser;
    private int $studentId;
    private int $ayId;
    private int $termId;
    private int $nodeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school    = School::factory()->create();
        $this->finance   = User::factory()->create(['school_id' => $this->school->id, 'role' => 'finance_manager']);
        $this->registrar = User::factory()->create(['school_id' => $this->school->id, 'role' => 'registrar']);
        $this->studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);

        $this->studentId = $this->insertWithDefaults('students', [
            'school_id' => $this->school->id, 'user_id' => $this->studentUser->id,
            'first_name' => 'Gate', 'last_name' => 'Keeper',
            'student_number' => 'GATE-001', 'status' => 'active',
        ]);
        $this->nodeId = $this->insertWithDefaults('education_nodes', [
            'name' => 'Basic Education', 'node_type' => 'level',
            'is_offered' => 1, 'is_active' => 1, 'order_index' => 0,
        ]);
        $this->ayId = $this->insertWithDefaults('academic_years', [
            'school_id' => $this->school->id, 'name' => '2026-2027', 'is_active' => 1,
        ]);
        $this->termId = $this->insertWithDefaults('terms', [
            'school_id' => $this->school->id, 'academic_year_id' => $this->ayId,
            'name' => '1st Sem', 'status' => 'active',
        ]);
    }

    /** Insert a row, auto-filling NOT NULL columns that have no default. */
    private function insertWithDefaults(string $table, array $values): int
    {
        $cols = DB::select(
            'select column_name, data_type, is_nullable, column_default, extra
             from information_schema.columns
             where table_schema = database() and table_name = ?',
            [$table]
        );
        foreach ($cols as $c) {
            $c = array_change_key_case((array) $c, CASE_LOWER);
            if (array_key_exists($c['column_name'], $values)
                || strtoupper((string) $c['is_nullable']) === 'YES'
                || $c['column_default'] !== null
                || str_contains(strtolower((string) $c['extra']), 'auto_increment')) {
                continue;
            }
            $values[$c['column_name']] = match (true) {
                in_array(strtolower((string) $c['data_type']), ['int', 'bigint', 'smallint', 'tinyint', 'decimal', 'double', 'float']) => 0,
                strtolower((string) $c['data_type']) === 'date' => date('Y-m-d'),
                in_array(strtolower((string) $c['data_type']), ['datetime', 'timestamp']) => date('Y-m-d H:i:s'),
                strtolower((string) $c['data_type']) === 'json' => '[]',
                default => '',
            };
        }

        return (int) DB::table($table)->insertGetId($values);
    }

    /** A gate-approved enrollment sitting in finance's queue. */
    private function enrollment(string $clearedAs): StudentEnrollment
    {
        $id = $this->insertWithDefaults('student_enrollments', [
            'school_id' => $this->school->id, 'student_id' => $this->studentId,
            'academic_year_id' => $this->ayId, 'term_id' => $this->termId,
            'education_node_id' => $this->nodeId, 'year_level' => 7,
            'status' => StudentEnrollment::STATUS_SENT_BILLING,
            'billing_cleared_as' => $clearedAs,
        ]);

        return StudentEnrollment::findOrFail($id);
    }

    private function invoice(StudentEnrollment $enrollment, string $number, string $billingDate, float $amount = 1000): Invoice
    {
        return Invoice::create([
            'invoice_number'        => $number,
            'school_id'             => $this->school->id,
            'student_id'            => $this->studentUser->id,
            'student_enrollment_id' => $enrollment->id,
            'total_amount'          => $amount,
            'paid_amount'           => 0,
            'balance'               => $amount,
            'status'                => Invoice::STATUS_UNPAID,
            'billing_date'          => $billingDate,
            'due_date'              => now()->addDays(7)->toDateString(),
        ]);
    }

    public function test_settling_downpayment_officially_enrolls_when_docs_complied(): void
    {
        $enrollment = $this->enrollment('assessed');
        $down       = $this->invoice($enrollment, 'INV-DP', now()->toDateString());
        $this->invoice($enrollment, 'INV-2', now()->addMonth()->toDateString());

        app(PaymentService::class)->recordInvoicePayment($this->finance, $down, 1000, 'cash');

        $fresh = $enrollment->fresh();
        $this->assertSame(StudentEnrollment::STATUS_ENROLLED, $fresh->status);
        $this->assertSame('finance_payment', $fresh->approval_level);
        $this->assertSame($this->finance->id, (int) $fresh->approved_by);
        $this->assertStringContainsString('Officially enrolled', (string) $fresh->remarks);
    }

    public function test_settling_downpayment_provisionally_enrolls_when_docs_pending(): void
    {
        $enrollment = $this->enrollment('provisional');
        $down       = $this->invoice($enrollment, 'INV-DP', now()->toDateString());

        app(PaymentService::class)->recordInvoicePayment($this->finance, $down, 1000, 'cash');

        $fresh = $enrollment->fresh();
        $this->assertSame(StudentEnrollment::STATUS_PROVISIONALLY_ENROLLED, $fresh->status);
        $this->assertStringContainsString('documents pending', (string) $fresh->remarks);
    }

    public function test_partial_downpayment_parks_at_partially_paid_until_settled(): void
    {
        $enrollment = $this->enrollment('assessed');
        $down       = $this->invoice($enrollment, 'INV-DP', now()->toDateString());

        $service = app(PaymentService::class);

        $service->recordInvoicePayment($this->finance, $down, 400, 'cash');
        $this->assertSame(StudentEnrollment::STATUS_PARTIALLY_PAID, $enrollment->fresh()->status);

        $service->recordInvoicePayment($this->finance, $down->fresh(), 600, 'cash');
        $this->assertSame(StudentEnrollment::STATUS_ENROLLED, $enrollment->fresh()->status);
    }

    public function test_finance_can_decide_manually_without_payment(): void
    {
        $enrollment = $this->enrollment('provisional');

        $this->actingAs($this->finance)
            ->put(route('finance.enrollment-queue.decide', $enrollment), [
                'decision' => 'officially',
                'note'     => 'Scholarship grantee — fees waived.',
            ])
            ->assertSessionHas('success');

        $fresh = $enrollment->fresh();
        $this->assertSame(StudentEnrollment::STATUS_ENROLLED, $fresh->status);
        $this->assertSame('finance_manual', $fresh->approval_level);
        $this->assertStringContainsString('Scholarship grantee', (string) $fresh->remarks);
    }

    public function test_rejecting_manually_requires_a_note(): void
    {
        $enrollment = $this->enrollment('assessed');

        $this->actingAs($this->finance)
            ->from(route('finance.enrollment-queue.index'))
            ->put(route('finance.enrollment-queue.decide', $enrollment), ['decision' => 'rejected'])
            ->assertSessionHasErrors('note');

        $this->assertSame(StudentEnrollment::STATUS_SENT_BILLING, $enrollment->fresh()->status);
    }

    public function test_documents_complied_promotes_paid_provisional_student(): void
    {
        $enrollment = $this->enrollment('provisional');
        $down       = $this->invoice($enrollment, 'INV-DP', now()->toDateString());
        app(PaymentService::class)->recordInvoicePayment($this->finance, $down, 1000, 'cash');
        $this->assertSame(StudentEnrollment::STATUS_PROVISIONALLY_ENROLLED, $enrollment->fresh()->status);

        $this->actingAs($this->registrar)
            ->post(route('registrar.enrollments.documents-complied', $enrollment), [
                'remarks' => 'Form 137 submitted.',
            ])
            ->assertSessionHas('success');

        $fresh = $enrollment->fresh();
        $this->assertSame(StudentEnrollment::STATUS_ENROLLED, $fresh->status);
        $this->assertSame('assessed', $fresh->billing_cleared_as);
        $this->assertStringContainsString('promoted to officially enrolled', (string) $fresh->remarks);
    }

    public function test_queue_page_lists_waiting_enrollments(): void
    {
        $this->enrollment('provisional');

        $this->actingAs($this->finance)
            ->get(route('finance.enrollment-queue.index'))
            ->assertOk()
            ->assertSee('Enrollment Queue')
            ->assertSee('Keeper, Gate')
            ->assertSee('Incomplete');
    }
}
