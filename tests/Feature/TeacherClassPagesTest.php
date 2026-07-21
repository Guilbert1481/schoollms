<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Teacher "My Classes" hub + Students roster — read-only pages scoped to the
 * signed-in teacher. Guards that a teacher sees only their own classes/rosters
 * and that non-teachers are refused.
 */
class TeacherClassPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A higher-ed class taught by $teacher with one student on the class roster
     * (class_student pivot — the source both pages read).
     *
     * @return array{0: ClassModel, 1: Student}
     */
    private function scenario(School $school, User $teacher): array
    {
        $termId = DB::table('terms')->insertGetId([
            'school_id' => $school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
        $sectionId = DB::table('sections')->insertGetId([
            'school_id' => $school->id, 'term_id' => $termId, 'name' => 'BSIT-1A', 'year_level' => 1,
        ]);
        $subjectId = DB::table('subjects')->insertGetId([
            'school_id' => $school->id, 'code' => 'IT101', 'name' => 'Intro to IT',
        ]);
        // Query-builder insert: ClassModel::$fillable carries the legacy
        // semester_id instead of the real term_id column, so mass assignment
        // would silently drop term_id (mirrors the attendance/homework tests).
        $classId = DB::table('classes')->insertGetId([
            'school_id' => $school->id, 'subject_id' => $subjectId, 'term_id' => $termId,
            'teacher_id' => $teacher->id, 'section_id' => $sectionId, 'code' => 'IT101-A',
        ]);
        $class = ClassModel::findOrFail($classId);

        $studentUser = User::factory()->create(['school_id' => $school->id, 'role' => 'student']);
        $student = Student::create([
            'school_id' => $school->id, 'user_id' => $studentUser->id,
            'student_number' => 'S-1001', 'first_name' => 'Ana', 'last_name' => 'Cruz',
        ]);
        $class->students()->attach($student->id, ['status' => 'enrolled']);

        return [$class, $student];
    }

    public function test_class_list_shows_only_the_signed_in_teachers_classes(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $this->scenario($school, $teacher);

        $this->actingAs($teacher)->get(route('teacher.classes.index'))
            ->assertOk()
            ->assertSee('Intro to IT')
            ->assertSee('IT101-A')
            ->assertSee('1 student');

        // A different teacher in the same school must not see that class.
        $other = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $this->actingAs($other)->get(route('teacher.classes.index'))
            ->assertOk()
            ->assertDontSee('IT101-A');
    }

    public function test_roster_lists_enrolled_students_for_a_class_the_teacher_teaches(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        [$class, $student] = $this->scenario($school, $teacher);

        $this->actingAs($teacher)->get(route('teacher.students.index', ['class_id' => $class->id]))
            ->assertOk()
            ->assertSee('Cruz, Ana')
            ->assertSee($student->student_number);
    }

    public function test_teacher_cannot_view_the_roster_of_a_class_they_do_not_teach(): void
    {
        $school = School::factory()->create();
        $owner = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        [$class] = $this->scenario($school, $owner);

        // The class isn't in the intruder's own list, so the picker resolves no
        // context and the roster (student name) never renders.
        $intruder = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $this->actingAs($intruder)->get(route('teacher.students.index', ['class_id' => $class->id]))
            ->assertOk()
            ->assertDontSee('Cruz, Ana');
    }

    public function test_non_teacher_is_forbidden_from_both_pages(): void
    {
        $school = School::factory()->create();
        $student = User::factory()->create(['school_id' => $school->id, 'role' => 'student']);

        $this->actingAs($student)->get(route('teacher.classes.index'))->assertForbidden();
        $this->actingAs($student)->get(route('teacher.students.index'))->assertForbidden();
    }
}
