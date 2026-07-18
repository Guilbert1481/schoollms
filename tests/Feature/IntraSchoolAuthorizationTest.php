<?php

namespace Tests\Feature;

use App\Models\ChatThread;
use App\Models\Invoice;
use App\Models\School;
use App\Models\StatementOfAccount;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Roadmap Phase 2.5 (A1/A2/A3) — user-to-user isolation inside one school.
 * BelongsToSchool stops School A reading School B; these tests prove Student A
 * cannot read/write Student B's records within the SAME school, and that
 * legitimate owner/staff access keeps working.
 */
class IntraSchoolAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $ownerUser;

    private Student $ownerStudent;

    private User $peerUser;

    private Term $term;

    private StudentEnrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();

        $this->ownerUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        $this->ownerStudent = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $this->ownerUser->id,
            'student_number' => 'S-'.uniqid(),
            'first_name' => 'Own',
            'last_name' => 'Er',
        ]);

        // Same school, different account — the attacker in every scenario.
        $this->peerUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        Student::create([
            'school_id' => $this->school->id,
            'user_id' => $this->peerUser->id,
            'student_number' => 'S-'.uniqid(),
            'first_name' => 'Pe',
            'last_name' => 'Er',
        ]);

        $this->term = Term::create([
            'school_id' => $this->school->id,
            'title' => 'SY 2026 T1',
            'academic_year' => '2026-2027',
            'enrollment_type' => 'regular',
            'term' => '1st',
            'start_date' => '2026-06-01',
            'end_date' => '2026-10-31',
            'status' => 'active',
        ]);

        $yearId = \Illuminate\Support\Facades\DB::table('academic_years')->insertGetId([
            'school_id' => $this->school->id,
            'name' => '2026-2027',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->enrollment = StudentEnrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $this->ownerStudent->id,
            'academic_year_id' => $yearId,
            'term_id' => $this->term->id,
            'status' => StudentEnrollment::STATUS_SUBMITTED,
            'year_level' => 11,
        ]);
    }

    public function test_peer_cannot_view_anothers_enrollment_confirmation(): void
    {
        $this->actingAs($this->peerUser)
            ->get(route('public.apply.confirmation', [$this->term->id, $this->enrollment->id]))
            ->assertNotFound();
    }

    public function test_peer_cannot_submit_exam_outcome_for_anothers_enrollment(): void
    {
        $this->actingAs($this->peerUser)
            ->post(route('public.apply.exam.submit', [$this->term->id, $this->enrollment->id]), [
                'outcome' => 'passed',
            ])
            ->assertNotFound();

        $this->assertSame(
            StudentEnrollment::STATUS_SUBMITTED,
            $this->enrollment->fresh()->status,
            'A foreign exam submission must not move the enrolment.'
        );
    }

    public function test_owner_still_reaches_own_enrollment_confirmation(): void
    {
        $this->actingAs($this->ownerUser)
            ->get(route('public.apply.confirmation', [$this->term->id, $this->enrollment->id]))
            ->assertOk();
    }

    public function test_peer_cannot_download_anothers_invoice_or_statement(): void
    {
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->ownerUser->id,
            'invoice_number' => 'INV-0001',
            'subtotal_amount' => 100,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance' => 100,
            'status' => Invoice::STATUS_UNPAID,
        ]);
        $statement = StatementOfAccount::create([
            'school_id' => $this->school->id,
            'student_id' => $this->ownerUser->id,
            'soa_number' => 'SOA-0001',
            'opening_balance' => 0,
            'total_charges' => 100,
            'total_credits' => 0,
            'closing_balance' => 100,
            'status' => StatementOfAccount::STATUS_ISSUED,
        ]);

        $this->actingAs($this->peerUser)
            ->get(route('student.finance.invoice.pdf', $invoice))
            ->assertNotFound();
        $this->actingAs($this->peerUser)
            ->get(route('student.finance.soa.pdf', $statement))
            ->assertNotFound();

        // Checkout is the mutating surface — a peer must be blocked there too.
        $this->actingAs($this->peerUser)
            ->get(route('checkout.invoice.show', $invoice))
            ->assertForbidden();

        // Policy answers, not just HTTP: owner and same-school finance staff
        // stay allowed, a cross-school finance manager is not.
        $this->assertTrue(Gate::forUser($this->ownerUser)->allows('view', $invoice));
        $this->assertTrue(Gate::forUser($this->ownerUser)->allows('view', $statement));

        $finance = User::factory()->create(['school_id' => $this->school->id, 'role' => 'finance_manager']);
        $this->assertTrue(Gate::forUser($finance)->allows('view', $invoice));

        $foreignFinance = User::factory()->create([
            'school_id' => School::factory()->create()->id,
            'role' => 'finance_manager',
        ]);
        $this->assertFalse(Gate::forUser($foreignFinance)->allows('view', $invoice));
        $this->assertFalse(Gate::forUser($this->peerUser)->allows('view', $statement));
    }

    public function test_non_participant_cannot_read_a_chat_thread(): void
    {
        $thread = ChatThread::create([
            'school_id' => $this->school->id,
            'type' => 'private',
            'created_by' => $this->ownerUser->id,
            'status' => 'active',
        ]);
        $other = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $thread->participants()->attach([$this->ownerUser->id, $other->id]);

        $this->actingAs($this->peerUser)
            ->getJson(route('communication.chat.messages', $thread))
            ->assertForbidden();

        $this->actingAs($this->ownerUser)
            ->getJson(route('communication.chat.messages', $thread))
            ->assertOk();
    }

    public function test_program_head_cannot_spoof_school_or_creator_on_subject_create(): void
    {
        $otherSchool = School::factory()->create();
        $head = User::factory()->create(['school_id' => $this->school->id, 'role' => 'program_head']);

        $this->actingAs($head)
            ->post(route('program_head.subjects.store'), [
                'name' => 'Tenant Escape 101',
                'code' => 'TE101',
                'active' => 1,
                'category' => 'major',
                // A3 — these two must be ignored by the server.
                'school_id' => $otherSchool->id,
                'created_by' => $this->ownerUser->id,
            ])
            ->assertRedirect(route('program_head.subjects.index'));

        $subject = Subject::withoutGlobalScopes()->where('code', 'TE101')->firstOrFail();
        $this->assertSame((int) $this->school->id, (int) $subject->school_id);
        $this->assertSame((int) $head->id, (int) $subject->created_by);
    }
}
