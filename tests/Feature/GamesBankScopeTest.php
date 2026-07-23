<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Games catalog — the practice content bank is scoped to the user's OWN
 * subjects: a student sees only the subjects of classes they're enrolled in,
 * a teacher only the subjects they teach. Users with no classes fall back to
 * the whole school catalogue so practice never bricks.
 */
class GamesBankScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private int $termId;

    private int $sectionId;

    private int $scienceId;

    private int $mathId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);

        $this->termId = DB::table('terms')->insertGetId(['school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x', 'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31']);
        $this->sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $this->termId, 'name' => 'G5-A', 'year_level' => 5]);

        $this->scienceId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'SCI5', 'name' => 'Science Five', 'is_active' => 1]);
        $this->mathId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'MTH5', 'name' => 'Mathematics Five', 'is_active' => 1]);
    }

    private function classFor(int $subjectId, ?int $teacherId = null, ?int $sectionId = null): int
    {
        return DB::table('classes')->insertGetId([
            'school_id' => $this->school->id, 'subject_id' => $subjectId, 'term_id' => $this->termId,
            'teacher_id' => $teacherId ?? $this->teacher->id, 'section_id' => $sectionId ?? $this->sectionId,
            'code' => 'C-'.$subjectId.'-'.uniqid(), 'is_active' => 1,
        ]);
    }

    private function enrolledStudent(array $classIds): User
    {
        $userStudent = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        $student = Student::create(['school_id' => $this->school->id, 'user_id' => $userStudent->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Psalms', 'last_name' => 'Jabinar']);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $this->school->id, 'name' => '2026-2027-'.uniqid()]);
        $enrollmentId = DB::table('student_enrollments')->insertGetId(['school_id' => $this->school->id, 'student_id' => $student->id, 'academic_year_id' => $ayId, 'term_id' => $this->termId, 'section_id' => $this->sectionId, 'status' => 'enrolled']);
        foreach ($classIds as $classId) {
            DB::table('student_enrollment_subjects')->insert(['student_enrollment_id' => $enrollmentId, 'class_id' => $classId, 'subject_id' => DB::table('classes')->where('id', $classId)->value('subject_id'), 'status' => 'enrolled']);
        }

        return $userStudent;
    }

    public function test_student_bank_lists_only_their_enrolled_subjects(): void
    {
        $science = $this->classFor($this->scienceId);
        // The math class lives in ANOTHER section — this student never takes it.
        $otherSection = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $this->termId, 'name' => 'G6-B', 'year_level' => 6]);
        $this->classFor($this->mathId, null, (int) $otherSection);
        $studentUser = $this->enrolledStudent([$science]);

        $this->actingAs($studentUser)->get(route('tools.games.play', ['slug' => 'hangman', 'embed' => 1]))
            ->assertOk()
            ->assertSee('Science Five')
            ->assertDontSee('Mathematics Five');
    }

    public function test_teacher_bank_lists_only_subjects_they_teach(): void
    {
        $this->classFor($this->scienceId);
        $other = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->classFor($this->mathId, (int) $other->id);

        $this->actingAs($this->teacher)->get(route('tools.games.play', ['slug' => 'hangman', 'embed' => 1]))
            ->assertOk()
            ->assertSee('Science Five')
            ->assertDontSee('Mathematics Five');
    }

    public function test_user_with_no_classes_falls_back_to_the_full_catalogue(): void
    {
        $studentUser = $this->enrolledStudent([]); // enrolled, but no subject classes

        $this->actingAs($studentUser)->get(route('tools.games.play', ['slug' => 'hangman', 'embed' => 1]))
            ->assertOk()
            ->assertSee('Science Five')
            ->assertSee('Mathematics Five');
    }
}
