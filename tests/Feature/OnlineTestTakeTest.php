<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 3 — the student take flow end to end: start → begin (freeze) → autosave →
 * submit → result, plus the guards (enrolment, online-only) and the
 * server-authoritative deadline that auto-submits a sitting that runs out of time.
 */
class OnlineTestTakeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $studentUser;

    private Student $student;

    private int $classId;

    private int $subjectId;

    private int $topicId;

    private int $levelId;

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
        $this->topicId = DB::table('topics')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'name' => 'Matter', 'created_at' => now(), 'updated_at' => now()]);
        $this->levelId = DB::table('academic_levels')->insertGetId(['school_id' => $this->school->id, 'name' => 'Year 1', 'sequence_order' => 1, 'type' => 'higher']);

        $this->classId = DB::table('classes')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'term_id' => $termId, 'teacher_id' => $this->teacher->id, 'section_id' => $sectionId, 'code' => 'SCI-A']);

        $this->student = Student::create(['school_id' => $this->school->id, 'user_id' => $this->studentUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz']);
        $enrollmentId = DB::table('student_enrollments')->insertGetId(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $sectionId, 'status' => 'enrolled']);
        DB::table('student_enrollment_subjects')->insert(['student_enrollment_id' => $enrollmentId, 'class_id' => $this->classId, 'subject_id' => $this->subjectId, 'status' => 'enrolled']);
    }

    /** @return array{0:int,1:array<string,int>} [question_id, choice text => id] */
    private function question(int $testId, int $order, string $type, array $choices): array
    {
        $qid = DB::table('questions')->insertGetId([
            'school_id' => $this->school->id, 'teacher_id' => $this->teacher->id,
            'topic_id' => $this->topicId, 'academic_level_id' => $this->levelId,
            'question_type' => $type, 'question_text' => 'Q'.$order, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $ids = [];
        foreach ($choices as $text => $correct) {
            $ids[$text] = DB::table('choices')->insertGetId(['question_id' => $qid, 'choice_text' => $text, 'is_correct' => $correct ? 1 : 0, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('test_questions')->insert(['test_id' => $testId, 'question_id' => $qid, 'order' => $order, 'points' => 1, 'created_at' => now(), 'updated_at' => now()]);

        return [$qid, $ids];
    }

    private function makeTest(string $mode = 'online', bool $published = true, array $settings = []): Test
    {
        $test = Test::create(['school_id' => $this->school->id, 'class_id' => $this->classId, 'subject_id' => $this->subjectId, 'teacher_id' => $this->teacher->id, 'title' => 'Quiz', 'status' => $published ? 'published' : 'draft']);
        TestSetting::create(array_merge(['test_id' => $test->id, 'mode' => $mode, 'availability_mode' => 'duration', 'duration_minutes' => 30, 'attempts_allowed' => 1, 'show_results' => 'immediate'], $settings));

        return $test;
    }

    public function test_full_take_flow_start_to_result(): void
    {
        $test = $this->makeTest();
        [$mcq, $mcqC] = $this->question($test->id, 1, 'multiple_choice', ['Volume' => true, 'Mass' => false]);
        [$idq] = $this->question($test->id, 2, 'identification', ['Balance' => true]);

        $this->actingAs($this->studentUser);

        $this->get(route('student.assessments.start', $test))->assertOk();

        // Begin freezes an attempt with one answer row per question.
        $this->post(route('student.assessments.begin', $test))->assertRedirect(route('student.assessments.take', $test));
        $attempt = TestAttempt::where('test_id', $test->id)->where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame('in_progress', $attempt->status);
        $this->assertSame(2, $attempt->answers()->count());

        $this->get(route('student.assessments.take', $test))->assertOk()->assertSee('Q1');

        // Autosave the MCQ (correct) and the identification (correct).
        $this->postJson(route('student.assessments.answer', $attempt), ['question_id' => $mcq, 'response' => ['choice_id' => $mcqC['Volume']]])->assertOk();
        $this->postJson(route('student.assessments.answer', $attempt), ['question_id' => $idq, 'response' => ['text' => 'balance']])->assertOk();

        // Submit → graded, 100%, no manual items.
        $this->post(route('student.assessments.submit', $attempt))->assertRedirect(route('student.assessments.result', $test));
        $attempt->refresh();
        $this->assertSame('graded', $attempt->status);
        $this->assertSame('100.00', (string) $attempt->percentage);

        $this->get(route('student.assessments.result', $test))->assertOk()->assertSee('100%');
    }

    public function test_beginning_twice_resumes_the_same_attempt(): void
    {
        $test = $this->makeTest();
        $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $this->actingAs($this->studentUser);

        $this->post(route('student.assessments.begin', $test));
        $this->post(route('student.assessments.begin', $test));

        $this->assertSame(1, TestAttempt::where('test_id', $test->id)->where('student_id', $this->student->id)->count());
    }

    public function test_f2f_test_is_not_takeable_online(): void
    {
        $test = $this->makeTest(mode: 'f2f');
        $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);

        $this->actingAs($this->studentUser)->get(route('student.assessments.start', $test))->assertNotFound();
    }

    public function test_a_non_enrolled_student_is_blocked(): void
    {
        $test = $this->makeTest();
        $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);

        $otherUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        Student::create(['school_id' => $this->school->id, 'user_id' => $otherUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Bob', 'last_name' => 'X']);

        $this->actingAs($otherUser)->get(route('student.assessments.start', $test))->assertForbidden();
    }

    public function test_answering_past_the_deadline_auto_submits(): void
    {
        $test = $this->makeTest();
        [$mcq, $mcqC] = $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $this->actingAs($this->studentUser);

        $this->post(route('student.assessments.begin', $test));
        $attempt = TestAttempt::where('test_id', $test->id)->firstOrFail();
        // Force the server deadline into the past.
        $attempt->update(['deadline_at' => now()->subMinute()]);

        $this->postJson(route('student.assessments.answer', $attempt), ['question_id' => $mcq, 'response' => ['choice_id' => $mcqC['A']]])
            ->assertStatus(409)
            ->assertJson(['expired' => true]);

        $attempt->refresh();
        $this->assertTrue($attempt->isSubmitted());
        $this->assertTrue($attempt->auto_submitted);
    }
}
