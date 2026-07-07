<?php

namespace Tests\Feature;

use App\Mail\ParentPortalCredentialsMail;
use App\Models\ParentProvisioningIssue;
use App\Models\ParentStudentLink;
use App\Models\RegistrarSetting;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Parent portal auto-provisioning at the registrar gate: clearing an
 * enrollment (assessed / provisional) creates a parent account from the
 * primary guardian email, emails one-time credentials, dedupes onto an
 * existing parent account for further children, never merges into
 * non-parent accounts, and respects the registrar kill-switch. Failures
 * surface as issues instead of blocking the gate.
 */
class ParentPortalProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $registrar;
    private int $ayId;
    private int $termId;
    private int $nodeId;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->school    = School::factory()->create();
        $this->registrar = User::factory()->create(['school_id' => $this->school->id, 'role' => 'registrar']);

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

    /** A student (with login user) + guardian + submitted enrollment, ready for the gate. */
    private function studentWithGuardian(?string $guardianEmail, string $number = 'PP-001', string $type = 'parent'): array
    {
        $studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        $studentId = $this->insertWithDefaults('students', [
            'school_id' => $this->school->id, 'user_id' => $studentUser->id,
            'first_name' => 'Child', 'last_name' => 'Of'.$number,
            'student_number' => $number, 'status' => 'active',
        ]);
        $this->insertWithDefaults('guardians', [
            'student_id' => $studentId, 'type' => $type,
            'relationship' => 'mother', 'is_primary' => 1,
            'first_name' => 'Gina', 'last_name' => 'Guardian',
            'email' => $guardianEmail ?? '',
        ]);
        $enrollmentId = $this->insertWithDefaults('student_enrollments', [
            'school_id' => $this->school->id, 'student_id' => $studentId,
            'academic_year_id' => $this->ayId, 'term_id' => $this->termId,
            'education_node_id' => $this->nodeId, 'year_level' => 3,
            'status' => StudentEnrollment::STATUS_SUBMITTED,
        ]);

        return [$studentId, StudentEnrollment::findOrFail($enrollmentId)];
    }

    private function clearAtGate(StudentEnrollment $enrollment, string $action = 'validate'): void
    {
        $this->actingAs($this->registrar)
            ->post(route('registrar.enrollments.'.$action, $enrollment), ['remarks' => 'ok'])
            ->assertSessionHas('success');
    }

    public function test_gate_clearing_creates_parent_account_link_and_credentials_mail(): void
    {
        [$studentId, $enrollment] = $this->studentWithGuardian('gina@example.test');

        $this->clearAtGate($enrollment);

        $parent = User::where('email', 'gina@example.test')->first();
        $this->assertNotNull($parent);
        $this->assertSame('parent', $parent->role);
        $this->assertNull($parent->school_id);
        $this->assertTrue((bool) $parent->password_change_required);
        $this->assertNotNull($parent->credentials_sent_at);

        $link = ParentStudentLink::where('parent_user_id', $parent->id)->where('student_id', $studentId)->first();
        $this->assertNotNull($link);
        $this->assertSame('active', $link->access_status);
        $this->assertSame('mother', $link->relationship);

        Mail::assertSent(ParentPortalCredentialsMail::class, fn ($mail) => $mail->hasTo('gina@example.test'));
    }

    public function test_provisional_approval_also_provisions(): void
    {
        [, $enrollment] = $this->studentWithGuardian('prov@example.test', 'PP-002');

        $this->clearAtGate($enrollment, 'provisional');

        $this->assertNotNull(User::where('email', 'prov@example.test')->where('role', 'parent')->first());
    }

    public function test_second_child_attaches_to_existing_parent_without_new_credentials(): void
    {
        [, $first]  = $this->studentWithGuardian('shared@example.test', 'PP-003');
        [, $second] = $this->studentWithGuardian('shared@example.test', 'PP-004');

        $this->clearAtGate($first);
        $this->clearAtGate($second);

        $this->assertSame(1, User::where('email', 'shared@example.test')->count());
        $parent = User::where('email', 'shared@example.test')->firstOrFail();
        $this->assertSame(2, ParentStudentLink::where('parent_user_id', $parent->id)->count());

        // Credentials go out once — the second child rides the same account.
        Mail::assertSent(ParentPortalCredentialsMail::class, 1);
    }

    public function test_regate_of_same_student_is_idempotent(): void
    {
        [, $enrollment] = $this->studentWithGuardian('once@example.test', 'PP-005');

        $this->clearAtGate($enrollment);
        // The registrar re-runs the gate action (e.g. after a correction).
        $enrollment->update(['status' => StudentEnrollment::STATUS_SUBMITTED]);
        $this->clearAtGate($enrollment);

        $parent = User::where('email', 'once@example.test')->firstOrFail();
        $this->assertSame(1, ParentStudentLink::where('parent_user_id', $parent->id)->count());
        Mail::assertSent(ParentPortalCredentialsMail::class, 1);
    }

    public function test_email_owned_by_non_parent_account_becomes_collision_issue(): void
    {
        User::factory()->create([
            'school_id' => $this->school->id, 'role' => 'teacher',
            'email' => 'moonlights@example.test',
        ]);
        [$studentId, $enrollment] = $this->studentWithGuardian('moonlights@example.test', 'PP-006');

        $this->clearAtGate($enrollment);

        $this->assertSame(0, User::where('role', 'parent')->count());
        $this->assertSame(0, ParentStudentLink::count());
        $issue = ParentProvisioningIssue::where('student_id', $studentId)
            ->where('issue', ParentProvisioningIssue::ISSUE_EMAIL_COLLISION)
            ->whereNull('resolved_at')->first();
        $this->assertNotNull($issue);
        $this->assertStringContainsString('teacher', (string) $issue->details);
        Mail::assertNothingSent();
    }

    public function test_missing_guardian_email_becomes_no_email_issue(): void
    {
        // No usable email: the parent-type guardian has none, and the
        // emergency contact (which HAS one) must never become the account.
        [$studentId, $enrollment] = $this->studentWithGuardian(null, 'PP-007');
        $this->insertWithDefaults('guardians', [
            'student_id' => $studentId, 'type' => 'emergency',
            'first_name' => 'Ed', 'last_name' => 'Mergency',
            'email' => 'neighbor@example.test', 'is_emergency_contact' => 1,
        ]);

        $this->clearAtGate($enrollment);

        $this->assertSame(0, User::where('role', 'parent')->count());
        $this->assertNotNull(
            ParentProvisioningIssue::where('student_id', $studentId)
                ->where('issue', ParentProvisioningIssue::ISSUE_NO_EMAIL)
                ->whereNull('resolved_at')->first()
        );
        Mail::assertNothingSent();
    }

    public function test_registrar_kill_switch_stops_provisioning(): void
    {
        RegistrarSetting::forSchool((int) $this->school->id)
            ->update(['parent_portal_auto_provision' => false]);
        [, $enrollment] = $this->studentWithGuardian('blocked@example.test', 'PP-008');

        $this->clearAtGate($enrollment);

        // The gate action itself still succeeds — only provisioning is off.
        $this->assertSame(StudentEnrollment::STATUS_SENT_BILLING, $enrollment->fresh()->status);
        $this->assertSame(0, User::where('role', 'parent')->count());
        Mail::assertNothingSent();
    }
}
