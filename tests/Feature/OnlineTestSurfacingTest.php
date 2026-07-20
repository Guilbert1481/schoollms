<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestSetting;
use App\Models\User;
use App\Support\AssessmentNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 4 — surfacing. The student Assessments list buckets published online tests by
 * state, and the bell shows a sticky red row per open, unsubmitted test (clearing only
 * on submit). Drafts, F2F tests, and other students' classes never appear.
 */
class OnlineTestSurfacingTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $studentUser;

    private Student $student;

    private int $classId;

    private int $subjectId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);

        $termId = DB::table('terms')->insertGetId(['school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x', 'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31']);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $this->school->id, 'name' => '2026-2027']);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $termId, 'name' => 'BSIT', 'year_level' => 1]);
        $this->subjectId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'SCI', 'name' => 'Science']);
        $this->classId = DB::table('classes')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'term_id' => $termId, 'teacher_id' => $this->teacher->id, 'section_id' => $sectionId, 'code' => 'SCI-A']);

        $this->student = Student::create(['school_id' => $this->school->id, 'user_id' => $this->studentUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz']);
        $enrollmentId = DB::table('student_enrollments')->insertGetId(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $sectionId, 'status' => 'enrolled']);
        DB::table('student_enrollment_subjects')->insert(['student_enrollment_id' => $enrollmentId, 'class_id' => $this->classId, 'subject_id' => $this->subjectId, 'status' => 'enrolled']);
    }

    private function test(string $title, string $status, string $mode, array $settings = []): Test
    {
        $test = Test::create(['school_id' => $this->school->id, 'class_id' => $this->classId, 'subject_id' => $this->subjectId, 'teacher_id' => $this->teacher->id, 'title' => $title, 'status' => $status]);
        TestSetting::create(array_merge(['test_id' => $test->id, 'mode' => $mode, 'availability_mode' => 'duration', 'duration_minutes' => 30, 'attempts_allowed' => 1], $settings));

        return $test;
    }

    public function test_list_buckets_by_state_and_hides_draft_and_f2f(): void
    {
        $open = $this->test('OPEN QUIZ', 'published', 'online');
        $upcoming = $this->test('UPCOMING QUIZ', 'published', 'online', ['availability_mode' => 'schedule', 'duration_minutes' => null, 'start_at' => now()->addDay(), 'end_at' => now()->addDays(2)]);
        $missed = $this->test('MISSED QUIZ', 'published', 'online', ['availability_mode' => 'schedule', 'duration_minutes' => null, 'start_at' => now()->subDays(2), 'end_at' => now()->subDay()]);
        $submitted = $this->test('DONE QUIZ', 'published', 'online');
        TestAttempt::create(['school_id' => $this->school->id, 'test_id' => $submitted->id, 'student_id' => $this->student->id, 'status' => 'graded', 'submitted_at' => now()]);
        $this->test('DRAFT QUIZ', 'draft', 'online');
        $this->test('PAPER QUIZ', 'published', 'f2f');

        $res = $this->actingAs($this->studentUser)->get(route('student.assessments.index'))->assertOk();
        $res->assertSee('OPEN QUIZ')->assertSee('UPCOMING QUIZ')->assertSee('MISSED QUIZ')->assertSee('DONE QUIZ');
        $res->assertDontSee('DRAFT QUIZ')->assertDontSee('PAPER QUIZ');
    }

    public function test_bell_shows_open_unsubmitted_only(): void
    {
        $open = $this->test('OPEN QUIZ', 'published', 'online');
        $this->test('UPCOMING QUIZ', 'published', 'online', ['availability_mode' => 'schedule', 'duration_minutes' => null, 'start_at' => now()->addDay(), 'end_at' => now()->addDays(2)]);
        $this->test('DRAFT QUIZ', 'draft', 'online');

        $rows = AssessmentNotifications::forUser($this->studentUser->fresh());
        $this->assertCount(1, $rows, 'only the open, published, online, unsubmitted test rings the bell');
        $this->assertSame('assessment_open', $rows[0]['type']);
        $this->assertSame($open->id, $rows[0]['reference_id']);
        $this->assertFalse($rows[0]['read']);
    }

    public function test_bell_clears_when_the_student_submits(): void
    {
        $open = $this->test('OPEN QUIZ', 'published', 'online');
        $this->assertCount(1, AssessmentNotifications::forUser($this->studentUser->fresh()));

        TestAttempt::create(['school_id' => $this->school->id, 'test_id' => $open->id, 'student_id' => $this->student->id, 'status' => 'submitted', 'submitted_at' => now()]);

        $this->assertCount(0, AssessmentNotifications::forUser($this->studentUser->fresh()), 'submitting clears the bell');
    }

    public function test_bell_is_empty_for_a_non_enrolled_student(): void
    {
        $this->test('OPEN QUIZ', 'published', 'online');

        $otherUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        Student::create(['school_id' => $this->school->id, 'user_id' => $otherUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Bob', 'last_name' => 'X']);

        $this->assertCount(0, AssessmentNotifications::forUser($otherUser->fresh()));
    }
}
