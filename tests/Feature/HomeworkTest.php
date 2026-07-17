<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Homework tied to a class (subject × section × teacher). Teacher posts per
 * class; students in that class submit text + a file; the teacher grades. Guards
 * ownership, class membership, draft visibility, and the private file gate.
 */
class HomeworkTest extends TestCase
{
    use RefreshDatabase;

    /** A higher-ed class + teacher + one enrolled student-account. */
    private function scenario(School $school): array
    {
        $termId = DB::table('terms')->insertGetId([
            'school_id' => $school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $school->id, 'name' => '2026-2027']);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $school->id, 'term_id' => $termId, 'name' => 'BSIT', 'year_level' => 1]);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'code' => 'IT101', 'name' => 'IT']);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $classId = DB::table('classes')->insertGetId([
            'school_id' => $school->id, 'subject_id' => $subjectId, 'term_id' => $termId,
            'teacher_id' => $teacher->id, 'section_id' => $sectionId, 'code' => 'IT101-A',
        ]);

        $studentUser = User::factory()->create(['school_id' => $school->id, 'role' => 'student']);
        $student = Student::create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz']);
        $enrollmentId = DB::table('student_enrollments')->insertGetId([
            'school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $ayId,
            'term_id' => $termId, 'section_id' => $sectionId, 'status' => 'enrolled',
        ]);
        DB::table('student_enrollment_subjects')->insert([
            'student_enrollment_id' => $enrollmentId, 'class_id' => $classId, 'subject_id' => $subjectId, 'status' => 'enrolled',
        ]);

        return [$classId, $teacher, $studentUser, $student];
    }

    private function homework(int $classId, int $schoolId, bool $published = true): Homework
    {
        return Homework::withoutGlobalScopes()->create([
            'school_id' => $schoolId, 'class_id' => $classId, 'title' => 'Essay 1',
            'points' => 100, 'is_published' => $published,
        ]);
    }

    public function test_teacher_posts_homework_for_their_class(): void
    {
        $school = School::factory()->create();
        [$classId, $teacher] = $this->scenario($school);

        $this->actingAs($teacher)->post(route('teacher.homework.store'), [
            'class_id' => $classId, 'title' => 'Essay 1', 'points' => 100, 'is_published' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('homework', ['class_id' => $classId, 'title' => 'Essay 1', 'is_published' => 1, 'school_id' => $school->id]);
    }

    public function test_teacher_cannot_post_to_a_class_they_do_not_teach(): void
    {
        $school = School::factory()->create();
        [$classId] = $this->scenario($school);
        $intruder = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);

        $this->actingAs($intruder)->post(route('teacher.homework.store'), ['class_id' => $classId, 'title' => 'X'])
            ->assertNotFound();
        $this->assertDatabaseCount('homework', 0);
    }

    public function test_student_sees_and_submits_published_homework_with_a_file(): void
    {
        Storage::fake('local');
        $school = School::factory()->create();
        [$classId, , $studentUser, $student] = $this->scenario($school);
        $hw = $this->homework($classId, $school->id);

        $this->actingAs($studentUser)->get(route('student.homework.index'))->assertOk()->assertSee('Essay 1');

        $this->actingAs($studentUser)->post(route('student.homework.submit', $hw->id), [
            'body' => 'my answer', 'file' => UploadedFile::fake()->create('work.pdf', 40, 'application/pdf'),
        ])->assertRedirect()->assertSessionHas('success');

        $sub = HomeworkSubmission::where('homework_id', $hw->id)->where('student_id', $student->id)->first();
        $this->assertNotNull($sub);
        $this->assertSame('my answer', $sub->body);
        Storage::disk('local')->assertExists($sub->file_path);
    }

    public function test_a_student_not_in_the_class_cannot_submit(): void
    {
        $school = School::factory()->create();
        [$classId] = $this->scenario($school);
        $hw = $this->homework($classId, $school->id);

        $outsiderUser = User::factory()->create(['school_id' => $school->id, 'role' => 'student']);
        Student::create(['school_id' => $school->id, 'user_id' => $outsiderUser->id, 'student_number' => 'S-out', 'first_name' => 'O', 'last_name' => 'X']);

        $this->actingAs($outsiderUser)->post(route('student.homework.submit', $hw->id), ['body' => 'x'])->assertForbidden();
        $this->assertDatabaseCount('homework_submissions', 0);
    }

    public function test_draft_homework_is_hidden_from_students(): void
    {
        $school = School::factory()->create();
        [$classId, , $studentUser] = $this->scenario($school);
        $hw = $this->homework($classId, $school->id, published: false);

        $this->actingAs($studentUser)->get(route('student.homework.index'))->assertOk()->assertDontSee('Essay 1');
        $this->actingAs($studentUser)->post(route('student.homework.submit', $hw->id), ['body' => 'x'])->assertForbidden();
    }

    public function test_teacher_grades_a_submission(): void
    {
        $school = School::factory()->create();
        [$classId, $teacher, , $student] = $this->scenario($school);
        $hw = $this->homework($classId, $school->id);
        $sub = HomeworkSubmission::withoutGlobalScopes()->create([
            'school_id' => $school->id, 'homework_id' => $hw->id, 'student_id' => $student->id, 'body' => 'a', 'submitted_at' => now(),
        ]);

        $this->actingAs($teacher)->post(route('teacher.homework.grade', $hw->id), [
            'grades' => [$sub->id => ['score' => 92, 'feedback' => 'Good work']],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('homework_submissions', ['id' => $sub->id, 'score' => 92, 'feedback' => 'Good work']);
    }
}
