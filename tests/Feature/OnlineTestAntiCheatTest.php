<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestSetting;
use App\Models\User;
use App\Services\Tests\OnlineTestAttemptService;
use App\Support\TestArrangement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 5 — anti-cheat. Part A: the two shuffle settings (questions, MCQ choices)
 * are frozen onto the attempt at start and applied INDEPENDENTLY per axis, so a
 * teacher can shuffle one without the other. Part B: the take screen's integrity
 * signals (tab-blur, hidden, fullscreen exit) are logged to the attempt's meta,
 * server-stamped and type-whitelisted, and never touch a submitted attempt.
 */
class OnlineTestAntiCheatTest extends TestCase
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

    /** @return array{0:int,1:int[]} [question_id, ordered choice ids] */
    private function question(int $testId, int $order, string $type, array $choices): array
    {
        $qid = DB::table('questions')->insertGetId([
            'school_id' => $this->school->id, 'teacher_id' => $this->teacher->id,
            'topic_id' => $this->topicId, 'academic_level_id' => $this->levelId,
            'question_type' => $type, 'question_text' => 'Q'.$order, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $ids = [];
        foreach ($choices as $text => $correct) {
            $ids[] = DB::table('choices')->insertGetId(['question_id' => $qid, 'choice_text' => $text, 'is_correct' => $correct ? 1 : 0, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('test_questions')->insert(['test_id' => $testId, 'question_id' => $qid, 'order' => $order, 'points' => 1, 'created_at' => now(), 'updated_at' => now()]);

        return [$qid, $ids];
    }

    private function makeTest(array $settings = []): Test
    {
        $test = Test::create(['school_id' => $this->school->id, 'class_id' => $this->classId, 'subject_id' => $this->subjectId, 'teacher_id' => $this->teacher->id, 'title' => 'Quiz', 'status' => 'published']);
        TestSetting::create(array_merge(['test_id' => $test->id, 'mode' => 'online', 'availability_mode' => 'duration', 'duration_minutes' => 30, 'attempts_allowed' => 1, 'show_results' => 'immediate'], $settings));

        return $test;
    }

    private function attemptWith(Test $test, ?int $seed, array $flags): TestAttempt
    {
        $no = TestAttempt::where('test_id', $test->id)->where('student_id', $this->student->id)->count() + 1;

        return TestAttempt::create([
            'school_id' => $this->school->id, 'test_id' => $test->id, 'student_id' => $this->student->id,
            'attempt_no' => $no, 'status' => 'in_progress', 'print_seed' => $seed, 'started_at' => now(),
            'meta' => ['shuffle' => $flags],
        ]);
    }

    /** @return array<string,mixed> the multiple_choice section from a sections() payload */
    private function mcqSection(array $sections): array
    {
        foreach ($sections as $s) {
            if ($s['type'] === 'multiple_choice') {
                return $s;
            }
        }
        $this->fail('no multiple_choice section in payload');
    }

    // --- Part A: shuffle ---------------------------------------------------

    public function test_start_or_resume_freezes_seed_and_flags_per_settings(): void
    {
        $cases = [
            [['shuffle_questions' => false, 'shuffle_mcq_choices' => false], false, ['questions' => false, 'choices' => false]],
            [['shuffle_questions' => true,  'shuffle_mcq_choices' => false], true,  ['questions' => true,  'choices' => false]],
            [['shuffle_questions' => false, 'shuffle_mcq_choices' => true],  true,  ['questions' => false, 'choices' => true]],
            [['shuffle_questions' => true,  'shuffle_mcq_choices' => true],  true,  ['questions' => true,  'choices' => true]],
        ];

        $svc = app(OnlineTestAttemptService::class);
        foreach ($cases as [$settings, $seedSet, $flags]) {
            $test = $this->makeTest($settings);
            $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);

            $attempt = $svc->startOrResume($test, $this->student->id, (int) $this->student->school_id);

            $seedSet ? $this->assertNotNull($attempt->print_seed) : $this->assertNull($attempt->print_seed);
            $this->assertSame($flags, $attempt->meta['shuffle']);
        }
    }

    public function test_each_shuffle_axis_is_applied_independently(): void
    {
        $test = $this->makeTest(['shuffle_questions' => true, 'shuffle_mcq_choices' => true]);
        $qids = [];
        $choiceIds = [];
        for ($o = 1; $o <= 4; $o++) {
            [$qid, $cids] = $this->question($test->id, $o, 'multiple_choice', ['w' => true, 'x' => false, 'y' => false, 'z' => false]);
            $qids[$o] = $qid;
            $choiceIds[$qid] = $cids;
        }
        $naturalQ = array_values($qids);                 // by test_questions.order
        $q1 = $naturalQ[0];
        $idOrderQ1 = $choiceIds[$q1];                     // choices by id (insertion order)

        // A seed that visibly reorders BOTH the questions and q1's choices, so the
        // "unshuffled axis stays natural" assertions below are not vacuous.
        $seed = $this->reorderingSeed(array_keys($qids), $q1, $idOrderQ1);

        $expectedShuffledQ = collect(array_keys($qids))
            ->sortBy(fn ($o) => TestArrangement::orderKey($seed, $o))->map(fn ($o) => $qids[$o])->values()->all();
        $expectedShuffledC = TestArrangement::choiceOrder($seed, $q1, collect($idOrderQ1)->map(fn ($id) => (object) ['id' => $id]))
            ->pluck('id')->all();

        $svc = app(OnlineTestAttemptService::class);

        // Choices-only: questions stay in natural order, choices are shuffled.
        $secC = $this->mcqSection($svc->sections($this->attemptWith($test, $seed, ['questions' => false, 'choices' => true])));
        $this->assertSame($naturalQ, array_map(fn ($q) => $q['question_id'], $secC['questions']), 'choices-only must NOT reorder questions');
        $this->assertSame($expectedShuffledC, array_map(fn ($c) => $c['id'], $secC['questions'][0]['choices']), 'choices-only must shuffle choices');

        // Questions-only: questions shuffled, choices stay in id order.
        $secQ = $this->mcqSection($svc->sections($this->attemptWith($test, $seed, ['questions' => true, 'choices' => false])));
        $this->assertSame($expectedShuffledQ, array_map(fn ($q) => $q['question_id'], $secQ['questions']), 'questions-only must shuffle questions');
        $shuffledFirst = collect($secQ['questions'])->firstWhere('question_id', $q1);
        $this->assertSame($idOrderQ1, array_map(fn ($c) => $c['id'], $shuffledFirst['choices']), 'questions-only must leave choices in id order');
    }

    public function test_legacy_attempt_without_frozen_flags_keeps_old_single_seed_behaviour(): void
    {
        $test = $this->makeTest();
        [$q1] = $this->question($test->id, 1, 'multiple_choice', ['w' => true, 'x' => false, 'y' => false, 'z' => false]);
        [$q2] = $this->question($test->id, 2, 'multiple_choice', ['w' => true, 'x' => false, 'y' => false, 'z' => false]);

        // An attempt frozen before Part A: meta has no 'shuffle' key. A non-null seed
        // must still shuffle (old rule: one seed drove both axes).
        $attempt = TestAttempt::create([
            'school_id' => $this->school->id, 'test_id' => $test->id, 'student_id' => $this->student->id,
            'attempt_no' => 1, 'status' => 'in_progress', 'print_seed' => 12345, 'started_at' => now(), 'meta' => null,
        ]);

        $svc = app(OnlineTestAttemptService::class);
        $sec = $this->mcqSection($svc->sections($attempt));
        $expected = collect([1 => $q1, 2 => $q2])
            ->sortBy(fn ($qid, $order) => TestArrangement::orderKey(12345, $order))->values()->all();

        $this->assertSame($expected, array_map(fn ($q) => $q['question_id'], $sec['questions']));
    }

    /** Smallest seed that reorders the question orders AND $qid's choice ids. */
    private function reorderingSeed(array $orders, int $qid, array $choiceIds): int
    {
        for ($seed = 1; $seed < 50_000; $seed++) {
            $q = collect($orders)->sortBy(fn ($o) => TestArrangement::orderKey($seed, $o))->values()->all();
            $c = TestArrangement::choiceOrder($seed, $qid, collect($choiceIds)->map(fn ($id) => (object) ['id' => $id]))->pluck('id')->all();
            if ($q !== $orders && $c !== $choiceIds) {
                return $seed;
            }
        }
        $this->fail('no reordering seed found');
    }

    // --- Part B: integrity log --------------------------------------------

    public function test_event_endpoint_logs_a_whitelisted_signal(): void
    {
        $test = $this->makeTest();
        $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $this->actingAs($this->studentUser);
        $this->post(route('student.assessments.begin', $test));
        $attempt = TestAttempt::where('test_id', $test->id)->firstOrFail();

        $this->postJson(route('student.assessments.event', $attempt), ['type' => 'blur'])->assertOk();
        $this->postJson(route('student.assessments.event', $attempt), ['type' => 'hidden'])->assertOk();
        $this->postJson(route('student.assessments.event', $attempt), ['type' => 'blur'])->assertOk();

        $proctor = $attempt->fresh()->meta['proctor'];
        $this->assertSame(3, $proctor['total']);
        $this->assertSame(2, $proctor['counts']['blur']);
        $this->assertSame(1, $proctor['counts']['hidden']);
        $this->assertCount(3, $proctor['events']);
        $this->assertSame('blur', $proctor['events'][0]['type']);
    }

    public function test_unknown_event_type_is_bucketed_as_other(): void
    {
        $test = $this->makeTest();
        $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $this->actingAs($this->studentUser);
        $this->post(route('student.assessments.begin', $test));
        $attempt = TestAttempt::where('test_id', $test->id)->firstOrFail();

        $this->postJson(route('student.assessments.event', $attempt), ['type' => 'evil-payload'])->assertOk();

        $this->assertSame(1, $attempt->fresh()->meta['proctor']['counts']['other']);
    }

    public function test_events_are_ignored_once_the_attempt_is_submitted(): void
    {
        $test = $this->makeTest();
        $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $this->actingAs($this->studentUser);
        $this->post(route('student.assessments.begin', $test));
        $attempt = TestAttempt::where('test_id', $test->id)->firstOrFail();
        $this->post(route('student.assessments.submit', $attempt));

        $this->postJson(route('student.assessments.event', $attempt), ['type' => 'blur'])->assertOk();

        $this->assertNull(Arr::get($attempt->fresh()->meta ?? [], 'proctor'), 'a submitted attempt must not accept new signals');
    }

    public function test_take_screen_gates_on_fullscreen_only_when_the_test_requires_it(): void
    {
        $on = $this->makeTest(['require_fullscreen' => true]);
        $this->question($on->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $this->actingAs($this->studentUser)->post(route('student.assessments.begin', $on));
        $this->get(route('student.assessments.take', $on))
            ->assertOk()
            ->assertSee('Fullscreen required')
            ->assertSee('requireFullscreen: true', false);

        $off = $this->makeTest(['require_fullscreen' => false]);
        $this->question($off->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $this->post(route('student.assessments.begin', $off));
        $this->get(route('student.assessments.take', $off))
            ->assertOk()
            ->assertDontSee('Fullscreen required')
            ->assertSee('requireFullscreen: false', false);
    }

    public function test_a_student_cannot_log_events_on_another_students_attempt(): void
    {
        $test = $this->makeTest();
        $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $this->actingAs($this->studentUser);
        $this->post(route('student.assessments.begin', $test));
        $attempt = TestAttempt::where('test_id', $test->id)->firstOrFail();

        $otherUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        Student::create(['school_id' => $this->school->id, 'user_id' => $otherUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Bob', 'last_name' => 'X']);

        $this->actingAs($otherUser)
            ->postJson(route('student.assessments.event', $attempt), ['type' => 'blur'])
            ->assertForbidden();
    }
}
