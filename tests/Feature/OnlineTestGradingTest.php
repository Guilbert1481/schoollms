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
 * Phase 2 — the online grading engine. Auto types grade the same way the F2F/OMR
 * side does (AnswerMatch for text, choice-id for bubbles); essays park for manual
 * grading; the score is frozen at submit and rolls into the gradebook, provisionally
 * at first and finalised once the essay is scored.
 */
class OnlineTestGradingTest extends TestCase
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

    /** @param array<int,array{0:string,1:bool}> $choices [text, isCorrect] */
    private function question(string $type, array $choices = []): array
    {
        $qid = DB::table('questions')->insertGetId([
            'school_id' => $this->school->id, 'teacher_id' => $this->teacher->id,
            'topic_id' => $this->topicId, 'academic_level_id' => $this->levelId,
            'question_type' => $type, 'question_text' => 'Q', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $ids = [];
        foreach ($choices as $c) {
            $ids[$c[0]] = DB::table('choices')->insertGetId([
                'question_id' => $qid, 'choice_text' => $c[0], 'is_correct' => $c[1] ? 1 : 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return [$qid, $ids];
    }

    private function makeOnlineTest(): Test
    {
        return Test::create([
            'school_id' => $this->school->id, 'class_id' => $this->classId, 'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher->id, 'title' => 'Exam', 'status' => 'draft',
            'grade_component_id' => $this->componentId,
        ]);
    }

    private function grader(): OnlineTestGradingService
    {
        return app(OnlineTestGradingService::class);
    }

    private function studentScore(): ?ComponentScore
    {
        return ComponentScore::where('student_id', $this->student->id)
            ->where('grade_component_id', $this->componentId)->first();
    }

    public function test_grades_mixed_types_posts_provisional_then_finalises(): void
    {
        $test = $this->makeOnlineTest();
        [$mcq, $mcqC] = $this->question('multiple_choice', [['Volume', true], ['Mass', false]]);
        [$tf, $tfC] = $this->question('true_false', [['True', true], ['False', false]]);
        [$idq] = $this->question('identification', [['Balance', true]]);
        [$enum] = $this->question('enumeration', [['Mass', true], ['Volume', true], ['Density', true]]);
        [$essay] = $this->question('essay');

        $attempt = TestAttempt::create([
            'school_id' => $this->school->id, 'test_id' => $test->id, 'student_id' => $this->student->id,
            'status' => 'in_progress', 'started_at' => now(),
        ]);
        $attempt->answers()->createMany([
            ['question_id' => $mcq, 'question_type' => 'multiple_choice', 'response' => ['choice_id' => $mcqC['Volume']], 'points_possible' => 1], // correct
            ['question_id' => $tf, 'question_type' => 'true_false', 'response' => ['choice_id' => $tfC['False']], 'points_possible' => 1],       // wrong
            ['question_id' => $idq, 'question_type' => 'identification', 'response' => ['text' => 'balance'], 'points_possible' => 1],           // fuzzy → correct
            ['question_id' => $enum, 'question_type' => 'enumeration', 'response' => ['items' => ['Mass', 'Volume', 'Weight']], 'points_possible' => 1], // 2 of 3
            ['question_id' => $essay, 'question_type' => 'essay', 'response' => ['text' => 'My answer.'], 'points_possible' => 1],               // manual
        ]);

        $this->grader()->submit($attempt);
        $attempt->refresh();

        // Auto raw = 1 + 0 + 1 + 0.67 + 0(essay) = 2.67 over max 5.
        $this->assertSame('2.67', (string) $attempt->raw_score);
        $this->assertSame('5.00', (string) $attempt->max_score);
        $this->assertSame('53.40', (string) $attempt->percentage);
        $this->assertTrue($attempt->needs_manual);
        $this->assertSame('submitted', $attempt->status);

        $answers = $attempt->answers()->get()->keyBy('question_id');
        $this->assertTrue($answers[$mcq]->is_correct);
        $this->assertFalse($answers[$tf]->is_correct);
        $this->assertTrue($answers[$idq]->is_correct);
        $this->assertSame('0.67', (string) $answers[$enum]->points_earned);
        $this->assertTrue($answers[$essay]->needs_manual);
        $this->assertNull($answers[$essay]->is_correct);

        // Provisional score is already in the gradebook.
        $this->assertNotNull($this->studentScore());
        $this->assertSame('53.40', (string) $this->studentScore()->score);

        // Teacher scores the essay full marks → finalises + re-feeds.
        $this->grader()->gradeManual($answers[$essay], 1.0, $this->teacher->id);
        $attempt->refresh();
        $this->assertFalse($attempt->needs_manual);
        $this->assertSame('graded', $attempt->status);
        $this->assertSame('3.67', (string) $attempt->raw_score);
        $this->assertSame('73.40', (string) $this->studentScore()->score);
    }

    public function test_score_is_locked_at_submit(): void
    {
        $test = $this->makeOnlineTest();
        [$mcq, $mcqC] = $this->question('multiple_choice', [['A', true], ['B', false]]);

        $attempt = TestAttempt::create([
            'school_id' => $this->school->id, 'test_id' => $test->id, 'student_id' => $this->student->id,
            'status' => 'in_progress', 'started_at' => now(),
        ]);
        $attempt->answers()->create(['question_id' => $mcq, 'question_type' => 'multiple_choice', 'response' => ['choice_id' => $mcqC['A']], 'points_possible' => 1]);

        $this->grader()->submit($attempt);
        $this->assertTrue($attempt->answers()->first()->is_correct);

        // Teacher moves the correct answer to B afterwards. The frozen grade must not shift.
        DB::table('choices')->where('question_id', $mcq)->update(['is_correct' => DB::raw('CASE WHEN choice_text = "B" THEN 1 ELSE 0 END')]);
        $this->assertTrue($attempt->answers()->first()->fresh()->is_correct, 'a submitted grade is frozen');
    }

    public function test_submit_is_idempotent(): void
    {
        $test = $this->makeOnlineTest();
        [$mcq, $mcqC] = $this->question('multiple_choice', [['A', true], ['B', false]]);

        $attempt = TestAttempt::create([
            'school_id' => $this->school->id, 'test_id' => $test->id, 'student_id' => $this->student->id,
            'status' => 'in_progress', 'started_at' => now(),
        ]);
        $attempt->answers()->create(['question_id' => $mcq, 'question_type' => 'multiple_choice', 'response' => ['choice_id' => $mcqC['A']], 'points_possible' => 1]);

        $this->grader()->submit($attempt);
        $first = $attempt->fresh()->submitted_at;

        $this->grader()->submit($attempt->fresh()); // second call is a no-op
        $this->assertEquals($first, $attempt->fresh()->submitted_at);
        $this->assertSame(1, TestAttempt::where('test_id', $test->id)->count());
    }
}
