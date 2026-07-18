<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Registrar Teaching Assignments — the source of truth (a class row) for a
 * teacher's gradebook and attendance. Assignments upsert on (subject, term,
 * section) so re-assigning never duplicates, capability is enforced, and an
 * assigned class actually surfaces in the teacher's gradebook.
 */
class TeachingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $registrar;

    private int $subjectId;

    private int $sectionId;

    private int $termId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
        $this->registrar = User::factory()->create(['school_id' => $this->school->id, 'role' => 'registrar']);

        $this->termId = DB::table('terms')->insertGetId([
            'school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
        $this->subjectId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'MATH', 'name' => 'Math']);
        $this->sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $this->termId, 'name' => 'Rizal']);
    }

    private function teacher(bool $qualified = false, ?int $subjectId = null): User
    {
        $teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $profileId = DB::table('teacher_profiles')->insertGetId(['user_id' => $teacher->id]);
        if ($qualified) {
            DB::table('teacher_subjects')->insert(['teacher_id' => $profileId, 'subject_id' => $subjectId ?? $this->subjectId]);
        }

        return $teacher;
    }

    private function assign(User $registrar, int $teacherId, int $subjectId, int $sectionId)
    {
        return $this->actingAs($registrar)->post(route('registrar.teaching-assignments.store'), [
            'teacher_id' => $teacherId, 'subject_id' => $subjectId, 'section_id' => $sectionId,
        ]);
    }

    public function test_registrar_assigns_a_teacher_and_a_class_row_is_created(): void
    {
        $teacher = $this->teacher();

        $this->assign($this->registrar, $teacher->id, $this->subjectId, $this->sectionId)
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('classes', [
            'school_id' => $this->school->id, 'subject_id' => $this->subjectId,
            'section_id' => $this->sectionId, 'term_id' => $this->termId, 'teacher_id' => $teacher->id,
        ]);
    }

    public function test_reassigning_updates_the_same_class_without_duplicating(): void
    {
        $teacherA = $this->teacher();
        $teacherB = $this->teacher();

        $this->assign($this->registrar, $teacherA->id, $this->subjectId, $this->sectionId)->assertRedirect();
        $this->assign($this->registrar, $teacherB->id, $this->subjectId, $this->sectionId)->assertRedirect();

        // One class for (subject, term, section) — teacher replaced, not duplicated.
        $this->assertSame(1, DB::table('classes')->where('subject_id', $this->subjectId)->where('section_id', $this->sectionId)->count());
        $this->assertDatabaseHas('classes', ['subject_id' => $this->subjectId, 'section_id' => $this->sectionId, 'teacher_id' => $teacherB->id]);
    }

    public function test_an_assigned_class_appears_in_the_teachers_gradebook(): void
    {
        $teacher = $this->teacher();
        $this->assign($this->registrar, $teacher->id, $this->subjectId, $this->sectionId)->assertRedirect();

        // End-to-end: the class the registrar created is one the teacher teaches.
        Auth::login($teacher);
        $this->assertTrue(ClassModel::where('teacher_id', $teacher->id)->where('subject_id', $this->subjectId)->exists());
    }

    public function test_a_teacher_cannot_be_assigned_an_unqualified_subject(): void
    {
        $otherSubject = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'SCI', 'name' => 'Science']);
        // Qualified for Math only.
        $teacher = $this->teacher(qualified: true, subjectId: $this->subjectId);

        $this->assign($this->registrar, $teacher->id, $otherSubject, $this->sectionId)
            ->assertSessionHasErrors('subject_id');

        $this->assertDatabaseMissing('classes', ['subject_id' => $otherSubject]);
    }

    public function test_a_non_registrar_is_forbidden(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)
            ->get(route('registrar.teaching-assignments.index'))
            ->assertForbidden();
    }

    public function test_a_class_with_recorded_attendance_cannot_be_removed(): void
    {
        $teacher = $this->teacher();
        $this->assign($this->registrar, $teacher->id, $this->subjectId, $this->sectionId)->assertRedirect();
        $classId = DB::table('classes')->where('subject_id', $this->subjectId)->value('id');

        $student = DB::table('students')->insertGetId(['school_id' => $this->school->id, 'student_number' => 'S-1', 'first_name' => 'A', 'last_name' => 'B']);
        DB::table('attendance_records')->insert([
            'school_id' => $this->school->id, 'student_id' => $student, 'scope' => 'session',
            'class_id' => $classId, 'attendance_date' => '2026-06-01', 'status' => 'present', 'method' => 'manual',
        ]);

        $this->actingAs($this->registrar)
            ->delete(route('registrar.teaching-assignments.destroy', $classId))
            ->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseHas('classes', ['id' => $classId]); // not deleted
    }
}
