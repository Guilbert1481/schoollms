<?php

namespace Tests\Feature;

use App\Models\EnrollmentDocument;
use App\Models\EnrollmentDraft;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentAcademicBackground;
use App\Models\StudentEnrollment;
use App\Models\StudentHealthRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Roadmap D3 — never-enrolled applicants' PII is erased past the retention
 * window, while anyone who ever enrolled is left untouched. Covers the window
 * boundaries, the shorter abandoned-draft clock, complete child-row + file
 * erasure, the off-DB audit record, and the dry-run gate.
 */
class PiiRetentionPurgeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private int $academicYearId;

    private int $termId;

    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->school = School::factory()->create();

        $this->academicYearId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->school->id, 'name' => 'AY 2025-2026',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->termId = DB::table('terms')->insertGetId([
            'school_id' => $this->school->id, 'academic_year' => '2025-2026',
            'enrollment_type' => 'regular', 'term' => '1st',
            'start_date' => '2025-06-01', 'end_date' => '2025-10-31',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Route the off-DB audit channel to a readable temp file (Roadmap S4).
        $this->logFile = storage_path('logs/'.uniqid('test-pii-', true).'.log');
        config(['logging.audit_channel' => 'audit']);
        config(['logging.channels.audit' => [
            'driver' => 'single', 'path' => $this->logFile, 'level' => 'info',
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
        ]]);
        app('log')->forgetChannel('audit');
    }

    protected function tearDown(): void
    {
        if (isset($this->logFile) && is_file($this->logFile)) {
            @unlink($this->logFile);
        }

        parent::tearDown();
    }

    /**
     * Build a full applicant footprint. $status drives the enrollment; pass
     * $status = null for an abandoned draft (no enrollment at all). $activity
     * back-dates every timestamp so the record sits at a chosen age.
     */
    private function makeApplicant(?string $status, Carbon $activity, bool $withUser = true): Student
    {
        $user = $withUser
            ? User::factory()->create(['school_id' => $this->school->id, 'role' => 'student'])
            : null;

        $suffix = uniqid();

        Storage::disk('local')->put("id_documents/gov-{$suffix}.jpg", 'ID');
        Storage::disk('public')->put("avatars/pic-{$suffix}.jpg", 'AV');

        $student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $user?->id,
            'student_number' => 'S-'.$suffix,
            'first_name' => 'Applic', 'last_name' => 'Ant',
            'government_id_number' => 'GOV-'.$suffix,
            'photo_id' => "id_documents/gov-{$suffix}.jpg",
            'photo_path' => "avatars/pic-{$suffix}.jpg",
            'email' => "a{$suffix}@example.test",
        ]);

        Guardian::create(['student_id' => $student->id, 'first_name' => 'Par', 'last_name' => 'Ent']);
        StudentHealthRecord::create(['student_id' => $student->id]);
        StudentAcademicBackground::create([
            'student_id' => $student->id, 'education_level' => 'junior_high', 'school_name' => 'Prev School',
        ]);
        EnrollmentDraft::create(['student_id' => $student->id, 'term_id' => $this->termId, 'data' => ['step' => 1]]);

        if ($status !== null) {
            $enrollment = StudentEnrollment::create([
                'school_id' => $this->school->id, 'student_id' => $student->id,
                'academic_year_id' => $this->academicYearId, 'term_id' => $this->termId,
                'status' => $status,
            ]);
            EnrollmentDocument::create([
                'school_id' => $this->school->id, 'enrollment_id' => $enrollment->id,
                'document_type' => 'birth_certificate', 'file_path' => "id_documents/doc-{$suffix}.pdf",
            ]);
            Storage::disk('local')->put("id_documents/doc-{$suffix}.pdf", 'DOC');
        }

        // Back-date all activity timestamps so age is deterministic.
        $ts = $activity->toDateTimeString();
        DB::table('students')->where('id', $student->id)->update(['updated_at' => $ts]);
        DB::table('enrollment_drafts')->where('student_id', $student->id)->update(['updated_at' => $ts]);
        DB::table('student_enrollments')->where('student_id', $student->id)->update(['updated_at' => $ts]);

        return $student->fresh();
    }

    private function trail(): string
    {
        return is_file($this->logFile) ? (string) file_get_contents($this->logFile) : '';
    }

    public function test_over_window_rejected_applicant_is_fully_purged(): void
    {
        $student = $this->makeApplicant('rejected', now()->subMonths(18));
        $userId = $student->user_id;

        $this->artisan('pii:purge-applicants', ['--purge' => true])->assertSuccessful();

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertSame(0, StudentEnrollment::where('student_id', $student->id)->count());
        $this->assertSame(0, EnrollmentDraft::where('student_id', $student->id)->count());
        $this->assertSame(0, Guardian::where('student_id', $student->id)->count());
        $this->assertSame(0, StudentHealthRecord::where('student_id', $student->id)->count());
        $this->assertSame(0, StudentAcademicBackground::where('student_id', $student->id)->count());
        $this->assertSame(0, EnrollmentDocument::where('school_id', $this->school->id)->count());
        $this->assertDatabaseMissing('users', ['id' => $userId]);

        // Files gone from both disks.
        $this->assertEmpty(Storage::disk('local')->allFiles());
        $this->assertEmpty(Storage::disk('public')->allFiles());

        // The erasure is recorded off-database (S4 accountability).
        $this->assertStringContainsString('audit.pii_purge', $this->trail());
        $this->assertStringContainsString('decided_application', $this->trail());
    }

    public function test_under_window_applicant_is_kept(): void
    {
        $student = $this->makeApplicant('rejected', now()->subMonths(3)); // < 12 months

        $this->artisan('pii:purge-applicants', ['--purge' => true])->assertSuccessful();

        $this->assertDatabaseHas('students', ['id' => $student->id]);
        $this->assertSame(1, EnrollmentDraft::where('student_id', $student->id)->count());
    }

    public function test_enrolled_student_is_never_touched(): void
    {
        // Old and dormant, but they reached 'enrolled' — protected forever.
        $student = $this->makeApplicant('enrolled', now()->subMonths(36));

        $this->artisan('pii:purge-applicants', ['--purge' => true])->assertSuccessful();

        $this->assertDatabaseHas('students', ['id' => $student->id]);
        $this->assertDatabaseHas('users', ['id' => $student->user_id]);
        $this->assertSame(1, Guardian::where('student_id', $student->id)->count());
    }

    public function test_abandoned_draft_uses_the_shorter_clock(): void
    {
        $old = $this->makeApplicant(null, now()->subDays(100)); // > 90 days, no enrollment
        $fresh = $this->makeApplicant(null, now()->subDays(30)); // < 90 days

        $this->artisan('pii:purge-applicants', ['--purge' => true])->assertSuccessful();

        $this->assertDatabaseMissing('students', ['id' => $old->id]);
        $this->assertDatabaseHas('students', ['id' => $fresh->id]);
    }

    public function test_scrub_mode_keeps_a_skeleton_but_erases_pii(): void
    {
        config(['privacy.purge_depth' => 'scrub']);

        $student = $this->makeApplicant('rejected', now()->subMonths(18));

        $this->artisan('pii:purge-applicants', ['--purge' => true])->assertSuccessful();

        // Row survives, but every identifying field is gone.
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => 'archived']);
        $scrubbed = Student::find($student->id);
        $this->assertNull($scrubbed->government_id_number);
        $this->assertNull($scrubbed->email);
        $this->assertSame('Purged', $scrubbed->first_name);

        // Child PII and files are still fully erased; the login is retained.
        $this->assertSame(0, Guardian::where('student_id', $student->id)->count());
        $this->assertSame(0, EnrollmentDraft::where('student_id', $student->id)->count());
        $this->assertEmpty(Storage::disk('local')->allFiles());
        $this->assertDatabaseHas('users', ['id' => $student->user_id]);
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $student = $this->makeApplicant('rejected', now()->subMonths(18));

        $this->artisan('pii:purge-applicants')->assertSuccessful(); // no --purge

        $this->assertDatabaseHas('students', ['id' => $student->id]);
        $this->assertSame(1, StudentEnrollment::where('student_id', $student->id)->count());
        $this->assertNotEmpty(Storage::disk('local')->allFiles());
        $this->assertStringNotContainsString('audit.pii_purge', $this->trail());
    }
}
