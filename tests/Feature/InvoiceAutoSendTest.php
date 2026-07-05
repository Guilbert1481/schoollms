<?php

namespace Tests\Feature;

use App\Mail\InvoiceIssuedMail;
use App\Mail\StatementIssuedMail;
use App\Models\FinanceSetting;
use App\Models\Invoice;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Money/mail path: invoices are auto-emailed once (and only once) to the
 * student + guardians when their billing date arrives, only for schools that
 * opted in, and every 3rd emailed invoice per enrollment also carries the SOA.
 */
class InvoiceAutoSendTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;
    private int $userId;
    private int $enrollmentId;
    private string $studentEmail;
    private string $guardianEmail = 'guardian@example.test';

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::factory()->create();
        $user   = User::factory()->create(['school_id' => $school->id, 'role' => 'student']);

        $this->schoolId     = (int) $school->id;
        $this->userId       = (int) $user->id;
        $this->studentEmail = (string) $user->email;

        // Parent rows the enrollment FKs require.
        $studentId = $this->insertWithDefaults('students', [
            'school_id' => $this->schoolId, 'user_id' => $this->userId,
            'first_name' => 'Auto', 'last_name' => 'Send',
            'student_number' => 'AUTO-001', 'status' => 'active',
        ]);
        $this->insertWithDefaults('guardians', [
            'student_id' => $studentId, 'type' => 'parent',
            'first_name' => 'Guardian', 'last_name' => 'Send',
            'email' => $this->guardianEmail, 'is_primary' => 1,
        ]);

        $nodeId = $this->insertWithDefaults('education_nodes', [
            'name' => 'Basic Education', 'node_type' => 'level',
            'is_offered' => 1, 'is_active' => 1, 'order_index' => 0,
        ]);
        $ayId = $this->insertWithDefaults('academic_years', [
            'school_id' => $this->schoolId, 'name' => '2026-2027',
        ]);
        $termId = $this->insertWithDefaults('terms', [
            'school_id' => $this->schoolId, 'academic_year_id' => $ayId, 'name' => '1st Sem',
        ]);
        $this->enrollmentId = $this->insertWithDefaults('student_enrollments', [
            'school_id' => $this->schoolId, 'student_id' => $studentId,
            'academic_year_id' => $ayId, 'term_id' => $termId,
            'education_node_id' => $nodeId, 'year_level' => 7,
            'status' => 'enrolled',
        ]);

        FinanceSetting::forSchool($this->schoolId)->update(['auto_send_invoices' => true]);
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

    private function invoice(string $number, string $billingDate): Invoice
    {
        return Invoice::create([
            'invoice_number'        => $number,
            'school_id'             => $this->schoolId,
            'student_id'            => $this->userId,
            'student_enrollment_id' => $this->enrollmentId,
            'total_amount'          => 1000,
            'paid_amount'           => 0,
            'balance'               => 1000,
            'status'                => Invoice::STATUS_UNPAID,
            'billing_date'          => $billingDate,
            'due_date'              => now()->addDays(7)->toDateString(),
        ]);
    }

    public function test_sends_due_invoices_once_to_student_and_guardian(): void
    {
        Mail::fake();

        $past   = $this->invoice('INV-1', now()->subDay()->toDateString());
        $today  = $this->invoice('INV-2', now()->toDateString());
        $future = $this->invoice('INV-3', now()->addMonth()->toDateString());

        $this->artisan('finance:send-due-invoices')->assertExitCode(0);

        // 2 due invoices × 2 recipients (student + guardian).
        Mail::assertSent(InvoiceIssuedMail::class, 4);
        Mail::assertSent(InvoiceIssuedMail::class, fn ($m) => $m->hasTo($this->studentEmail));
        Mail::assertSent(InvoiceIssuedMail::class, fn ($m) => $m->hasTo($this->guardianEmail));

        $this->assertNotNull($past->fresh()->emailed_at, 'past-due invoice stamped');
        $this->assertNotNull($today->fresh()->emailed_at, "today's invoice stamped");
        $this->assertNull($future->fresh()->emailed_at, 'future invoice untouched');

        // Idempotent: a re-run sends nothing new.
        $this->artisan('finance:send-due-invoices')->assertExitCode(0);
        Mail::assertSent(InvoiceIssuedMail::class, 4);
    }

    public function test_third_sent_invoice_also_emails_the_soa(): void
    {
        Mail::fake();

        $this->invoice('INV-1', now()->subDays(3)->toDateString());
        $this->invoice('INV-2', now()->subDays(2)->toDateString());
        $this->invoice('INV-3', now()->subDay()->toDateString());

        $this->artisan('finance:send-due-invoices')->assertExitCode(0);

        Mail::assertSent(InvoiceIssuedMail::class, 6);
        // SOA exactly once (after the 3rd invoice), to both recipients.
        Mail::assertSent(StatementIssuedMail::class, 2);
        Mail::assertSent(StatementIssuedMail::class, fn ($m) => $m->hasTo($this->studentEmail));
        Mail::assertSent(StatementIssuedMail::class, fn ($m) => $m->hasTo($this->guardianEmail));

        $this->assertSame(1, DB::table('statement_of_accounts')
            ->where('school_id', $this->schoolId)
            ->whereNotNull('emailed_at')
            ->count(), 'one SOA generated and stamped');
    }

    public function test_two_sent_invoices_do_not_email_the_soa(): void
    {
        Mail::fake();

        $this->invoice('INV-1', now()->subDays(2)->toDateString());
        $this->invoice('INV-2', now()->subDay()->toDateString());

        $this->artisan('finance:send-due-invoices')->assertExitCode(0);

        Mail::assertSent(InvoiceIssuedMail::class, 4);
        Mail::assertNotSent(StatementIssuedMail::class);
    }

    public function test_disabled_school_sends_nothing(): void
    {
        Mail::fake();

        FinanceSetting::forSchool($this->schoolId)->update(['auto_send_invoices' => false]);
        $this->invoice('INV-1', now()->toDateString());

        $this->artisan('finance:send-due-invoices')->assertExitCode(0);

        Mail::assertNothingSent();
        $this->assertNull(Invoice::first()->emailed_at);
    }
}
