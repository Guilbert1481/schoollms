<?php

namespace Tests\Feature;

use App\Models\ComponentScore;
use App\Models\GradeComponent;
use App\Models\GradingSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\Tests\OnlineTestGradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Essay-grading UI (teacher side). The screen scores the manual (essay) answers an
 * online test couldn't auto-grade; saving finalizes the attempt (submitted → graded
 * once none remain manual) and re-feeds the gradebook. Auto items stay frozen. The
 * grade path is guarded to the test's author, and a non-essay answer id can't be
 * hijacked through the score endpoint.
 */
class OnlineEssayGradingTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private Student $student;

    private int $classId;

    private int $componentId;

    private int $subjectId;

    private int $topicId;

    private int $levelId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);

        $termId = DB::table('terms')->insertGetId(['school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x', 'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31']);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $this->school->id, 'name' => '2026-2027']);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $termId, 'name' => 'BSIT', 'year_level' => 1]);
        $this->subjectId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'SCI', 'name' => 'Science']);
        $this->topicId = DB::table('topics')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'name' => 'Matter', 'created_at' => now(), 'updated_at' => now()]);
        $this->levelId = DB::table('academic_levels')->insertGetId(['school_id' => $this->school->id, 'name' => 'Year 1', 'sequence_order' => 1, 'type' => 'higher']);

        $setting = GradingSetting::create(['school_id' => $this->school->id, 'academic_level_id' => $this->levelId, 'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => 0]);
        $this->componentId = GradeComponent::create(['school_id' => $this->school->id, 'grading_setting_id' => $setting->id, 'name' => 'Quiz', 'weight' => 100, 'sort_order' => 0])->id;

        $this->classId = DB::table('classes')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'term_id' => $termId, 'teacher_id' => $this->teacher->id, 'section_id' => $sectionId, 'code' => 'SCI-A']);

        $this->student = Student::create(['school_id' => $this->school->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz']);
        $enrollmentId = DB::table('student_enrollments')->insertGetId(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $sectionId, 'status' => 'enrolled']);
        DB::table('student_enrollment_subjects')->insert(['student_enrollment_id' => $enrollmentId, 'class_id' => $this->classId, 'subject_id' => $this->subjectId, 'status' => 'enrolled']);
    }

    /** @param array<int,array{0:string,1:bool}> $choices */
    private function question(string $type, array $choices = []): array
    {
        $qid = DB::table('questions')->insertGetId([
            'school_id' => $this->school->id, 'teacher_id' => $this->teacher->id,
            'topic_id' => $this->topicId, 'academic_level_id' => $this->levelId,
            'question_type' => $type, 'question_text' => ucfirst($type).' question', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $ids = [];
        foreach ($choices as $c) {
            $ids[$c[0]] = DB::table('choices')->insertGetId(['question_id' => $qid, 'choice_text' => $c[0], 'is_correct' => $c[1] ? 1 : 0, 'created_at' => now(), 'updated_at' => now()]);
        }

        return [$qid, $ids];
    }

    private function makeOnlineTest(): Test
    {
        return Test::create([
            'school_id' => $this->school->id, 'class_id' => $this->classId, 'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher->id, 'title' => 'Exam', 'status' => 'published',
            'grade_component_id' => $this->componentId,
        ]);
    }

    private function grader(): OnlineTestGradingService
    {
        return app(OnlineTestGradingService::class);
    }

    /** @param array<int,array<string,mixed>> $answers */
    private function submittedAttempt(Test $test, array $answers): TestAttempt
    {
        $attempt = TestAttempt::create([
            'school_id' => $this->school->id, 'test_id' => $test->id, 'student_id' => $this->student->id,
            'status' => 'in_progress', 'started_at' => now(),
        ]);
        $attempt->answers()->createMany($answers);
        $this->grader()->submit($attempt);

        return $attempt->fresh();
    }

    private function studentScore(): ?ComponentScore
    {
        return ComponentScore::where('student_id', $this->student->id)->where('grade_component_id', $this->componentId)->first();
    }

    private function essayAnswerId(TestAttempt $attempt): int
    {
        return (int) $attempt->answers()->where('question_type', 'essay')->value('id');
    }

    // --- happy path --------------------------------------------------------

    public function test_scoring_the_essay_finalizes_the_attempt_and_refeeds_the_gradebook(): void
    {
        $test = $this->makeOnlineTest();
        [$mcq, $mcqC] = $this->question('multiple_choice', [['Volume', true], ['Mass', false]]);
        [$essay] = $this->question('essay');

        $attempt = $this->submittedAttempt($test, [
            ['question_id' => $mcq, 'question_type' => 'multiple_choice', 'response' => ['choice_id' => $mcqC['Volume']], 'points_possible' => 1],
            ['question_id' => $essay, 'question_type' => 'essay', 'response' => ['text' => 'The volume changes.'], 'points_possible' => 3],
        ]);

        // Provisional: only the MCQ point counts, essay parks manual. 1/4 = 25%.
        $this->assertTrue($attempt->needs_manual);
        $this->assertSame('submitted', $attempt->status);
        $this->assertSame('25.00', (string) $this->studentScore()->score);

        $this->actingAs($this->teacher);
        $this->get(route('teacher.tests.grade', $test))->assertOk()->assertSee('Ana Cruz')->assertSee('Needs grading');
        $this->get(route('teacher.tests.grade.show', [$test, $attempt]))->assertOk()->assertSee('The volume changes.');

        $this->post(route('teacher.tests.grade.store', [$test, $attempt]), [
            'scores' => [$this->essayAnswerId($attempt) => 3],
        ])->assertRedirect(route('teacher.tests.grade.show', [$test, $attempt]));

        $attempt->refresh();
        $this->assertFalse($attempt->needs_manual);
        $this->assertSame('graded', $attempt->status);
        $this->assertSame('4.00', (string) $attempt->raw_score);  // 1 + 3
        $this->assertSame('100.00', (string) $attempt->percentage);
        $this->assertSame('100.00', (string) $this->studentScore()->score, 'the gradebook is re-fed with the final score');
    }

    public function test_a_score_over_the_maximum_is_clamped(): void
    {
        $test = $this->makeOnlineTest();
        [$essay] = $this->question('essay');
        $attempt = $this->submittedAttempt($test, [
            ['question_id' => $essay, 'question_type' => 'essay', 'response' => ['text' => 'x'], 'points_possible' => 2],
        ]);

        $this->actingAs($this->teacher)->post(route('teacher.tests.grade.store', [$test, $attempt]), [
            'scores' => [$this->essayAnswerId($attempt) => 999],
        ])->assertRedirect();

        $this->assertSame('2.00', (string) $attempt->answers()->where('question_type', 'essay')->value('points_earned'));
    }

    public function test_only_essays_named_in_the_post_are_scored_others_stay_provisional(): void
    {
        $test = $this->makeOnlineTest();
        [$e1] = $this->question('essay');
        [$e2] = $this->question('essay');
        $attempt = $this->submittedAttempt($test, [
            ['question_id' => $e1, 'question_type' => 'essay', 'response' => ['text' => 'one'], 'points_possible' => 1],
            ['question_id' => $e2, 'question_type' => 'essay', 'response' => ['text' => 'two'], 'points_possible' => 1],
        ]);
        $firstAnswerId = (int) $attempt->answers()->where('question_id', $e1)->value('id');

        $this->actingAs($this->teacher)->post(route('teacher.tests.grade.store', [$test, $attempt]), [
            'scores' => [$firstAnswerId => 1/* second left blank */],
        ])->assertRedirect();

        $attempt->refresh();
        $this->assertTrue($attempt->needs_manual, 'one essay still ungraded → attempt stays provisional');
        $this->assertSame('submitted', $attempt->status);
        $this->assertFalse((bool) $attempt->answers()->where('question_id', $e1)->value('needs_manual'));
        $this->assertTrue((bool) $attempt->answers()->where('question_id', $e2)->value('needs_manual'));
    }

    public function test_a_non_essay_answer_id_cannot_be_hijacked_through_the_score_endpoint(): void
    {
        $test = $this->makeOnlineTest();
        [$mcq, $mcqC] = $this->question('multiple_choice', [['A', true], ['B', false]]);
        [$essay] = $this->question('essay');
        $attempt = $this->submittedAttempt($test, [
            ['question_id' => $mcq, 'question_type' => 'multiple_choice', 'response' => ['choice_id' => $mcqC['A']], 'points_possible' => 1],
            ['question_id' => $essay, 'question_type' => 'essay', 'response' => ['text' => 'x'], 'points_possible' => 1],
        ]);
        $mcqAnswerId = (int) $attempt->answers()->where('question_id', $mcq)->value('id');

        // The MCQ auto-graded to 1.00 (correct). Trying to overwrite it with 0 via the
        // essay endpoint must be ignored — only manual answers are scorable here.
        $this->actingAs($this->teacher)->post(route('teacher.tests.grade.store', [$test, $attempt]), [
            'scores' => [$mcqAnswerId => 0],
        ])->assertRedirect();

        $this->assertSame('1.00', (string) $attempt->answers()->where('question_id', $mcq)->value('points_earned'), 'the auto grade is untouched');
    }

    // --- guards ------------------------------------------------------------

    public function test_only_the_author_can_open_or_score_an_attempt(): void
    {
        $test = $this->makeOnlineTest();
        [$essay] = $this->question('essay');
        $attempt = $this->submittedAttempt($test, [
            ['question_id' => $essay, 'question_type' => 'essay', 'response' => ['text' => 'x'], 'points_possible' => 1],
        ]);

        $other = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);

        $this->actingAs($other)->get(route('teacher.tests.grade', $test))->assertForbidden();
        $this->actingAs($other)->get(route('teacher.tests.grade.show', [$test, $attempt]))->assertForbidden();
        $this->actingAs($other)->post(route('teacher.tests.grade.store', [$test, $attempt]), ['scores' => [$this->essayAnswerId($attempt) => 1]])->assertForbidden();

        // And the grade did not move.
        $this->assertTrue($attempt->fresh()->needs_manual);
    }

    public function test_an_attempt_from_another_test_is_not_found_under_this_test(): void
    {
        $testA = $this->makeOnlineTest();
        $testB = $this->makeOnlineTest();
        [$essay] = $this->question('essay');
        $attemptB = $this->submittedAttempt($testB, [
            ['question_id' => $essay, 'question_type' => 'essay', 'response' => ['text' => 'x'], 'points_possible' => 1],
        ]);

        $this->actingAs($this->teacher)
            ->get(route('teacher.tests.grade.show', [$testA, $attemptB]))
            ->assertNotFound();
    }
}
