<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * My Subjects page for both enrollment models: higher ed lists per-subject
 * enrolment rows (student_enrollment_subjects); basic ed has none of those —
 * its subjects hang off the advisory section's classes.
 */
class StudentSubjectsPageTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $studentUser;

    private Student $student;

    private int $ayId;

    private int $termId;

    private int $sectionId;

    private int $subjectId;

    private int $classId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        $this->student = Student::create([
            'school_id' => $this->school->id, 'user_id' => $this->studentUser->id,
            'student_number' => 'S-'.uniqid(), 'first_name' => 'Psalms', 'last_name' => 'Jabinar',
        ]);

        $this->ayId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->school->id, 'name' => '2026-2027', 'is_active' => 1,
            'education_level' => 'basic_ed',
        ]);
        $this->termId = DB::table('terms')->insertGetId([
            'school_id' => $this->school->id, 'academic_year_id' => $this->ayId, 'academic_year' => '2026-2027',
            'enrollment_type' => 'x', 'term' => 'first', 'name' => 'Basic Ed (AY 2026 - 2027)',
            'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
            'education_level' => 'basic_ed', 'status' => 'active',
        ]);
        $this->sectionId = DB::table('sections')->insertGetId([
            'school_id' => $this->school->id, 'term_id' => $this->termId, 'name' => 'G6-A', 'year_level' => 6,
        ]);
        $this->subjectId = DB::table('subjects')->insertGetId([
            'school_id' => $this->school->id, 'code' => 'SCI', 'name' => 'Science',
        ]);
        $this->classId = DB::table('classes')->insertGetId([
            'school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'term_id' => $this->termId,
            'teacher_id' => $this->teacher->id, 'section_id' => $this->sectionId, 'code' => 'SCI-6A',
        ]);
    }

    private function enroll(?int $sectionId): int
    {
        return DB::table('student_enrollments')->insertGetId([
            'school_id' => $this->school->id, 'student_id' => $this->student->id,
            'academic_year_id' => $this->ayId, 'term_id' => $this->termId, 'section_id' => $sectionId,
            'status' => 'enrolled', 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_basic_ed_student_sees_the_sections_subjects(): void
    {
        $this->enroll($this->sectionId);

        $this->actingAs($this->studentUser)
            ->get(route('student.subjects.index'))
            ->assertOk()
            ->assertSee('Science')
            ->assertSee('SCI-6A')
            ->assertSee('G6-A')
            ->assertDontSee('No enrolled subjects');
    }

    public function test_per_subject_enrolment_rows_take_precedence_and_are_not_duplicated(): void
    {
        $enrollmentId = $this->enroll($this->sectionId);
        DB::table('student_enrollment_subjects')->insert([
            'student_enrollment_id' => $enrollmentId, 'class_id' => $this->classId,
            'subject_id' => $this->subjectId, 'status' => 'enrolled',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->get(route('student.subjects.index'))
            ->assertOk()
            ->assertSee('Science');

        // One card, not one from ses plus one from the section fallback.
        $this->assertSame(1, substr_count($response->getContent(), 'data-subject-card'));
    }

    public function test_unenrolled_student_sees_the_empty_state(): void
    {
        $this->actingAs($this->studentUser)
            ->get(route('student.subjects.index'))
            ->assertOk()
            ->assertSee('No enrolled subjects');
    }
}
