<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Dashboard\StudentDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Active-enrollment resolution across per-level academic years. Basic ed and
 * higher ed each manage their own academic_years rows, so a school running
 * both levels holds one active year PER level — the resolver must consider
 * every active year (and fall back to the date-current year), not whichever
 * single row ->first() grabs. That bug made Clearance/Modality report
 * "no active enrollment" for enrolled basic-ed students.
 */
class StudentEnrollmentResolutionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $studentUser;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        $this->student = Student::create([
            'school_id' => $this->school->id, 'user_id' => $this->studentUser->id,
            'student_number' => 'S-'.uniqid(), 'first_name' => 'Psalms', 'last_name' => 'Jabinar',
        ]);
    }

    /** A basic-ed AY + term + section + enrolled enrollment. Returns the enrollment id. */
    private function basicEdEnrollment(bool $ayActive = true, ?string $ayStart = null, ?string $ayEnd = null): int
    {
        $ayId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->school->id, 'name' => '2026-2027', 'is_active' => $ayActive,
            'education_level' => 'basic_ed', 'start_date' => $ayStart, 'end_date' => $ayEnd,
        ]);
        $termId = DB::table('terms')->insertGetId([
            'school_id' => $this->school->id, 'academic_year_id' => $ayId, 'academic_year' => '2026-2027',
            'enrollment_type' => 'x', 'term' => 'first', 'name' => 'Basic Ed (AY 2026 - 2027)',
            'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
            'education_level' => 'basic_ed', 'status' => 'active',
        ]);
        $sectionId = DB::table('sections')->insertGetId([
            'school_id' => $this->school->id, 'term_id' => $termId, 'name' => 'G6-A', 'year_level' => 6,
        ]);

        return DB::table('student_enrollments')->insertGetId([
            'school_id' => $this->school->id, 'student_id' => $this->student->id,
            'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $sectionId,
            'status' => 'enrolled', 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function resolved(): ?int
    {
        return app(StudentDashboardService::class)
            ->activeEnrollment($this->studentUser->fresh())?->id;
    }

    public function test_basic_ed_enrollment_resolves_when_a_higher_ed_year_is_also_active(): void
    {
        // The higher-ed AY is created first (lower id) — the old ->first()
        // grabbed it and the basic-ed enrollment could never match.
        DB::table('academic_years')->insert([
            'school_id' => $this->school->id, 'name' => '2026-2027 (College)',
            'is_active' => true, 'education_level' => 'higher_ed',
        ]);
        $enrollmentId = $this->basicEdEnrollment();

        $this->assertSame($enrollmentId, $this->resolved());
    }

    public function test_date_current_year_resolves_when_no_year_is_flagged_active(): void
    {
        $enrollmentId = $this->basicEdEnrollment(
            ayActive: false,
            ayStart: now()->subMonth()->toDateString(),
            ayEnd: now()->addMonths(8)->toDateString(),
        );

        $this->assertSame($enrollmentId, $this->resolved());
    }

    public function test_clearance_page_offers_the_request_form_instead_of_the_empty_state(): void
    {
        DB::table('academic_years')->insert([
            'school_id' => $this->school->id, 'name' => '2026-2027 (College)',
            'is_active' => true, 'education_level' => 'higher_ed',
        ]);
        $this->basicEdEnrollment();

        $this->actingAs($this->studentUser)
            ->get(route('student.services.clearance.index'))
            ->assertOk()
            ->assertDontSee('You have no active enrollment this term.')
            ->assertSee('Start a clearance');
    }

    public function test_a_student_with_no_enrollment_still_gets_the_empty_state(): void
    {
        DB::table('academic_years')->insert([
            'school_id' => $this->school->id, 'name' => '2026-2027',
            'is_active' => true, 'education_level' => 'basic_ed',
        ]);

        $this->assertNull($this->resolved());

        $this->actingAs($this->studentUser)
            ->get(route('student.services.clearance.index'))
            ->assertOk()
            ->assertSee('You have no active enrollment this term.');
    }
}
