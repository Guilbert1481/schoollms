<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\AuditLog;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\ReportCardGrade;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roadmap Phase 3 (H2 + idempotency + AI2) — every money/grade/role mutation
 * leaves an append-only audit row, and exact duplicate payment submissions
 * are blocked before any money posts.
 */
class FinanceAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $finance;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->finance = User::factory()->create(['school_id' => $this->school->id, 'role' => 'finance_manager']);
        $this->student = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
    }

    private function paymentPayload(array $overrides = []): array
    {
        return array_merge([
            'student_id' => $this->student->id,
            'amount' => 1500.00,
            'payment_method' => 'cash',
            'reference_number' => 'OR-2026-0001',
        ], $overrides);
    }

    public function test_recording_a_payment_writes_audit_rows_for_payment_and_ledger(): void
    {
        $this->actingAs($this->finance)
            ->post(route('finance.ledger.record-payment'), $this->paymentPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $payment = Payment::firstOrFail();

        $paymentAudit = AuditLog::where('auditable_type', Payment::class)
            ->where('auditable_id', $payment->id)
            ->where('event', 'created')
            ->firstOrFail();

        $this->assertSame((int) $this->finance->id, (int) $paymentAudit->actor_id);
        $this->assertSame((int) $this->school->id, (int) $paymentAudit->school_id);
        $this->assertNull($paymentAudit->before);
        $this->assertEquals(1500.00, (float) $paymentAudit->after['amount']);

        $this->assertSame(1, AuditLog::where('auditable_type', LedgerEntry::class)->count(), 'The ledger credit must be audited too.');
    }

    public function test_exact_resubmit_with_same_reference_is_blocked(): void
    {
        $this->actingAs($this->finance)
            ->post(route('finance.ledger.record-payment'), $this->paymentPayload())
            ->assertSessionHasNoErrors();

        $this->actingAs($this->finance)
            ->post(route('finance.ledger.record-payment'), $this->paymentPayload())
            ->assertSessionHasErrors('amount');

        $this->assertSame(1, Payment::count(), 'The duplicate must not create a second payment.');
        $this->assertSame(1, LedgerEntry::count(), 'The duplicate must not post a second credit.');
    }

    public function test_quick_identical_resubmit_without_reference_is_blocked(): void
    {
        $payload = $this->paymentPayload(['reference_number' => null]);

        $this->actingAs($this->finance)
            ->post(route('finance.ledger.record-payment'), $payload)
            ->assertSessionHasNoErrors();

        $this->actingAs($this->finance)
            ->post(route('finance.ledger.record-payment'), $payload)
            ->assertSessionHasErrors('amount');

        $this->assertSame(1, Payment::count());
    }

    public function test_distinct_payments_are_unaffected(): void
    {
        $this->actingAs($this->finance)
            ->post(route('finance.ledger.record-payment'), $this->paymentPayload())
            ->assertSessionHasNoErrors();

        // Different amount, and a different reference for the same amount —
        // both are legitimate follow-up payments.
        $this->actingAs($this->finance)
            ->post(route('finance.ledger.record-payment'), $this->paymentPayload([
                'amount' => 999.50, 'reference_number' => 'OR-2026-0002',
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->finance)
            ->post(route('finance.ledger.record-payment'), $this->paymentPayload([
                'reference_number' => 'OR-2026-0003',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(3, Payment::count());
    }

    public function test_grade_change_is_audited_with_before_and_after(): void
    {
        $this->actingAs($this->finance); // any authenticated actor

        $studentRow = \App\Models\Student::create([
            'school_id' => $this->school->id,
            'user_id' => $this->student->id,
            'student_number' => 'S-'.uniqid(),
            'first_name' => 'Gra',
            'last_name' => 'Dee',
        ]);
        $nodeId = \Illuminate\Support\Facades\DB::table('education_nodes')->insertGetId([
            'name' => 'Grade 5', 'parent_id' => null, 'node_type' => 'stage',
            'is_offered' => 1, 'is_active' => 1, 'order_index' => 1,
        ]);
        $subjectId = \Illuminate\Support\Facades\DB::table('subjects')->insertGetId([
            'school_id' => $this->school->id, 'name' => 'Mathematics 5', 'code' => 'MATH5',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $grade = ReportCardGrade::create([
            'school_id' => $this->school->id,
            'student_id' => $studentRow->id,
            'education_node_id' => $nodeId,
            'subject_id' => $subjectId,
            'grading_period' => 1,
            'final_grade' => 80,
            'recorded_by' => $this->finance->id,
        ]);

        $grade->update(['final_grade' => 92]);

        $audit = AuditLog::where('auditable_type', ReportCardGrade::class)
            ->where('auditable_id', $grade->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->assertEquals(80, (float) $audit->before['final_grade']);
        $this->assertEquals(92, (float) $audit->after['final_grade']);
        $this->assertSame((int) $this->finance->id, (int) $audit->actor_id);
    }

    public function test_role_change_is_audited_but_profile_edits_are_not(): void
    {
        $this->actingAs($this->finance);

        AuditLog::query()->delete(); // ignore setUp() creation noise

        $this->student->update(['first_name' => 'Renamed']);
        $this->assertSame(0, AuditLog::where('auditable_type', User::class)->count(), 'Profile edits must not hit the audit log.');

        $this->student->update(['role' => 'teacher']);

        $audit = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $this->student->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->assertSame('student', $audit->before['role']);
        $this->assertSame('teacher', $audit->after['role']);
    }

    public function test_ai_provider_changes_are_audited_without_the_api_key(): void
    {
        $this->actingAs($this->finance);

        $provider = AiProvider::create([
            'provider' => 'claude',
            'label' => 'Claude',
            'api_key' => 'sk-super-secret',
            'model' => 'claude-opus-4-8',
            'is_enabled' => true,
        ]);

        $provider->update(['model' => 'claude-fable-5', 'api_key' => 'sk-rotated']);

        $logs = AuditLog::where('auditable_type', AiProvider::class)->get();
        $this->assertCount(2, $logs); // created + updated

        foreach ($logs as $log) {
            $payload = json_encode([$log->before, $log->after]);
            $this->assertStringNotContainsString('sk-super-secret', $payload);
            $this->assertStringNotContainsString('sk-rotated', $payload);
            $this->assertArrayNotHasKey('api_key', (array) $log->after);
        }

        $updated = $logs->firstWhere('event', 'updated');
        $this->assertSame('claude-opus-4-8', $updated->before['model']);
        $this->assertSame('claude-fable-5', $updated->after['model']);
    }
}
