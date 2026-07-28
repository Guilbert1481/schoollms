<?php

namespace Tests\Feature;

use App\Models\GradeSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The current-school-year Report Card block lives on Academics → Grades and on
 * the registrar's editable transcript — the student transcript pages no
 * longer duplicate it.
 */
class StudentTranscriptReportCardTest extends TestCase
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

        // Grant every grade-view gate this schema knows: the school-level
        // Principal toggles, and — once the per-student visibility migration
        // has run — the registrar's per-student flags that supersede them.
        GradeSetting::create([
            'school_id' => $this->school->id,
            'show_student_grades' => true,
            'show_student_form137' => true,
        ]);
        if (Schema::hasColumn('students', 'show_grades')) {
            DB::table('students')->where('id', $this->student->id)
                ->update(['show_grades' => true, 'show_form137' => true]);
        }

        $ayId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->school->id, 'name' => '2026-2027', 'is_active' => 1,
            'education_level' => 'basic_ed',
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
        DB::table('student_enrollments')->insert([
            'school_id' => $this->school->id, 'student_id' => $this->student->id,
            'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $sectionId,
            'status' => 'enrolled', 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_student_transcript_no_longer_repeats_the_report_card(): void
    {
        $this->actingAs($this->studentUser)
            ->get(route('student.transcript.index'))
            ->assertOk()
            ->assertDontSee('Report Card');
    }

    public function test_grades_page_still_shows_the_report_card(): void
    {
        $this->actingAs($this->studentUser)
            ->get(route('student.report-card'))
            ->assertOk()
            ->assertSee('Report Card');
    }
}
