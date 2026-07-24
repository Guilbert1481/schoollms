<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestSetting;
use App\Models\User;
use App\Services\Games\SnakeLadderAttemptService;
use App\Services\Tests\OnlineTestAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Quiz Snakes & Ladders — the graded board-game delivery of an online test.
 *
 * Pins the security spine: the game rides the SAME attempt + grading pipeline
 * as a standard quiz, the answer key never reaches the browser before an
 * answer is locked, each answer locks exactly once, every dice roll is
 * generated server-side and consumed exactly once (client dice/positions are
 * ignored), snakes/ladders/shields resolve on the server against the frozen
 * board, and the board state lives only in meta['game'] — it can never move
 * the academic percentage. Reaching tile 100 never ends a graded sitting.
 */
class SnakeLadderGradedModeTest extends TestCase
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

    /** @var (SnakeLadderAttemptService&object{diceQueue: array<int, int>})|null */
    private $fixedDice = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);

        $termId = DB::table('terms')->insertGetId(['school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x', 'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31']);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $this->school->id, 'name' => '2026-2027']);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $termId, 'name' => 'G6-A', 'year_level' => 6]);
        $this->subjectId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'SCI', 'name' => 'Science']);
        $this->topicId = DB::table('topics')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'name' => 'Plants', 'created_at' => now(), 'updated_at' => now()]);
        $this->levelId = DB::table('academic_levels')->insertGetId(['school_id' => $this->school->id, 'name' => 'Grade 6', 'sequence_order' => 6, 'type' => 'basic']);

        $this->classId = DB::table('classes')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'term_id' => $termId, 'teacher_id' => $this->teacher->id, 'section_id' => $sectionId, 'code' => 'SCI-A']);

        $this->student = Student::create(['school_id' => $this->school->id, 'user_id' => $this->studentUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Alex', 'last_name' => 'Reyes']);
        $enrollmentId = DB::table('student_enrollments')->insertGetId(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $sectionId, 'status' => 'enrolled']);
        DB::table('student_enrollment_subjects')->insert(['student_enrollment_id' => $enrollmentId, 'class_id' => $this->classId, 'subject_id' => $this->subjectId, 'status' => 'enrolled']);
    }

    /** @return array{0:int,1:array<string,int>} [question_id, choice text => id] */
    private function question(int $testId, int $order, string $type, array $choices, string $difficulty = 'average'): array
    {
        $qid = DB::table('questions')->insertGetId([
            'school_id' => $this->school->id, 'teacher_id' => $this->teacher->id,
            'topic_id' => $this->topicId, 'academic_level_id' => $this->levelId,
            'question_type' => $type, 'question_text' => 'Q'.$order, 'difficulty' => $difficulty,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $ids = [];
        foreach ($choices as $text => $correct) {
            $ids[$text] = DB::table('choices')->insertGetId(['question_id' => $qid, 'choice_text' => $text, 'is_correct' => $correct ? 1 : 0, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('test_questions')->insert(['test_id' => $testId, 'question_id' => $qid, 'order' => $order, 'points' => 1, 'created_at' => now(), 'updated_at' => now()]);

        return [$qid, $ids];
    }

    private function makeGameTest(array $settings = [], array $gameSettings = []): Test
    {
        $test = Test::create(['school_id' => $this->school->id, 'class_id' => $this->classId, 'subject_id' => $this->subjectId, 'teacher_id' => $this->teacher->id, 'title' => 'Plant Race', 'status' => 'published']);
        TestSetting::create(array_merge([
            'test_id' => $test->id, 'mode' => 'online', 'play_mode' => 'snake_and_ladder',
            'availability_mode' => 'duration', 'duration_minutes' => 30,
            'attempts_allowed' => 1, 'show_results' => 'immediate',
            'game_settings' => $gameSettings,
        ], $settings));

        return $test;
    }

    private function beginAttempt(Test $test): TestAttempt
    {
        $this->actingAs($this->studentUser)->get(route('student.assessments.board', $test))->assertOk();

        return TestAttempt::where('test_id', $test->id)->where('student_id', $this->student->id)->firstOrFail();
    }

    /** Swap in a dice queue so movement becomes deterministic. */
    private function useFixedDice(): void
    {
        $this->fixedDice = new class(app(OnlineTestAttemptService::class)) extends SnakeLadderAttemptService
        {
            /** @var array<int, int> */
            public array $diceQueue = [];

            protected function rollDice(int $min, int $max): int
            {
                return $this->diceQueue !== [] ? array_shift($this->diceQueue) : $min;
            }
        };
        $this->app->instance(SnakeLadderAttemptService::class, $this->fixedDice);
    }

    /** Overwrite server-side game state (test-only shortcut to a board spot). */
    private function forceGame(TestAttempt $attempt, array $patch): void
    {
        $attempt->refresh();
        $meta = $attempt->meta;
        $meta['game'] = array_merge($meta['game'], $patch);
        $attempt->update(['meta' => $meta]);
    }

    // ------------------------------------------------------------ teacher

    public function test_teacher_can_enable_snakes_and_ladders_with_board_settings(): void
    {
        $test = $this->makeGameTest(['play_mode' => 'standard']);
        $this->question($test->id, 1, 'multiple_choice', ['Carbon Dioxide' => true, 'Oxygen' => false, 'Helium' => false]);
        $this->question($test->id, 2, 'true_false', ['True' => true, 'False' => false]);

        $this->actingAs($this->teacher)->get(route('teacher.tests.game', $test))
            ->assertOk()->assertSee('Quiz Snakes')->assertSee('Ready to play');

        $this->actingAs($this->teacher)->post(route('teacher.tests.game.save', $test), [
            'play_mode' => 'snake_and_ladder',
            'movement_policy' => 'knowledge', 'finish_rule' => 'bounce', 'board_layout' => 'random', 'shield_enabled' => 0,
            'starting_lives' => 3, 'instant_submit' => 1, 'powerups_enabled' => 1, 'speed_bonus_max' => 20,
        ])->assertRedirect(route('teacher.tests.game', $test));

        $settings = $test->fresh('settings')->settings;
        $this->assertSame('snake_and_ladder', $settings->play_mode);
        $this->assertSame('knowledge', $settings->game_settings['movement_policy']);
        $this->assertSame('bounce', $settings->game_settings['finish_rule']);
        $this->assertSame('random', $settings->game_settings['board_layout']);
        $this->assertFalse($settings->game_settings['shield_enabled']);
    }

    // ------------------------------------------------------------ student

    public function test_take_screen_redirects_to_the_board_game(): void
    {
        $test = $this->makeGameTest();
        $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);

        $this->actingAs($this->studentUser)->get(route('student.assessments.take', $test))
            ->assertRedirect(route('student.assessments.board', $test));
    }

    public function test_board_screen_freezes_the_attempt_board_and_never_leaks_the_key(): void
    {
        $test = $this->makeGameTest();
        [$qid, $choices] = $this->question($test->id, 1, 'multiple_choice', ['Carbon Dioxide' => true, 'Oxygen' => false, 'Nitrogen' => false]);

        $response = $this->actingAs($this->studentUser)->get(route('student.assessments.board', $test))->assertOk();

        // The key exists in the DB but never in the page payload.
        $this->assertStringNotContainsString('is_correct', $response->getContent());
        $this->assertStringNotContainsString('correct_choice', $response->getContent());

        $attempt = TestAttempt::where('test_id', $test->id)->where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame(1, (int) $attempt->meta['game']['position']);
        $this->assertNotEmpty($attempt->meta['game']['board']['snakes']);
        $this->assertNotEmpty($attempt->meta['game']['board']['ladders']);
        $this->assertSame(1, $attempt->answers()->count()); // frozen question set
    }

    public function test_correct_answer_arms_the_dice_and_wrong_answer_does_not(): void
    {
        $test = $this->makeGameTest();
        [$q1, $c1] = $this->question($test->id, 1, 'multiple_choice', ['Right' => true, 'Wrong' => false]);
        [$q2, $c2] = $this->question($test->id, 2, 'multiple_choice', ['Yes' => true, 'No' => false]);
        $attempt = $this->beginAttempt($test);

        $wrong = $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $q1, 'choice_id' => $c1['Wrong']])
            ->assertOk()->json();
        $this->assertFalse($wrong['correct']);
        $this->assertNull($wrong['game']['pending_roll']);
        $this->assertSame(1, $wrong['game']['position']); // no movement on wrong

        $right = $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $q2, 'choice_id' => $c2['Yes']])
            ->assertOk()->json();
        $this->assertTrue($right['correct']);
        $this->assertSame($q2, $right['game']['pending_roll']);
    }

    public function test_rolling_without_a_correct_answer_is_rejected(): void
    {
        $test = $this->makeGameTest();
        [$qid] = $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $attempt = $this->beginAttempt($test);

        $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-roll', $attempt), ['question_id' => $qid])
            ->assertStatus(422);
    }

    public function test_a_roll_is_server_generated_persisted_and_consumed_exactly_once(): void
    {
        $test = $this->makeGameTest();
        [$qid, $choices] = $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $attempt = $this->beginAttempt($test);

        $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $qid, 'choice_id' => $choices['A']])
            ->assertOk();

        // Client-supplied dice/positions must be ignored.
        $roll = $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-roll', $attempt), ['question_id' => $qid, 'dice' => 6, 'position' => 99, 'to' => 100])
            ->assertOk()->json();

        $this->assertGreaterThanOrEqual(1, $roll['dice']);
        $this->assertLessThanOrEqual(6, $roll['dice']);
        $this->assertSame(1, $roll['from']);
        $this->assertNotSame(99, $roll['game']['position']);
        $this->assertNull($roll['game']['pending_roll']);
        $this->assertCount(1, $roll['game']['rolls']);

        // Replay returns the SAME stored roll — the token does not move twice.
        $replay = $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-roll', $attempt), ['question_id' => $qid])
            ->assertOk()->json();
        $this->assertTrue($replay['duplicate']);
        $this->assertSame($roll['dice'], $replay['dice']);
        $this->assertSame($roll['to'], $replay['to']);
        $this->assertCount(1, $replay['game']['rolls']);
    }

    public function test_snake_is_resolved_server_side_and_a_shield_blocks_it(): void
    {
        $this->useFixedDice();
        $test = $this->makeGameTest();
        [$q1, $c1] = $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        [$q2, $c2] = $this->question($test->id, 2, 'multiple_choice', ['C' => true, 'D' => false]);
        $attempt = $this->beginAttempt($test);

        // Default board has a snake 36 → 15. Sit on 35, no shield, roll 1.
        $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $q1, 'choice_id' => $c1['A']])->assertOk();
        $this->forceGame($attempt, ['position' => 35, 'shields' => 0]);
        $this->fixedDice->diceQueue = [1];

        $bitten = $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-roll', $attempt), ['question_id' => $q1])
            ->assertOk()->json();
        $this->assertSame('snake', $bitten['event']['type']);
        $this->assertSame(36, $bitten['landed']);
        $this->assertSame(15, $bitten['to']);
        $this->assertSame(1, $bitten['game']['snakes_hit']);

        // Same spot WITH a shield: the slide is absorbed.
        $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $q2, 'choice_id' => $c2['C']])->assertOk();
        $this->forceGame($attempt, ['position' => 35, 'shields' => 1]);
        $this->fixedDice->diceQueue = [1];

        $shielded = $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-roll', $attempt), ['question_id' => $q2])
            ->assertOk()->json();
        $this->assertTrue($shielded['shielded']);
        $this->assertSame(36, $shielded['to']); // stayed on the snake's tile
        $this->assertSame(0, $shielded['game']['shields']);
        $this->assertSame(1, $shielded['game']['shields_used']);
    }

    public function test_exact_finish_blocks_overshoot_and_reaching_100_never_ends_the_attempt(): void
    {
        $this->useFixedDice();
        $test = $this->makeGameTest(); // default finish_rule = exact
        [$q1, $c1] = $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        [$q2, $c2] = $this->question($test->id, 2, 'multiple_choice', ['C' => true, 'D' => false]);
        [$q3, $c3] = $this->question($test->id, 3, 'multiple_choice', ['E' => true, 'F' => false]);
        $attempt = $this->beginAttempt($test);

        // Overshoot: on 97, roll 5 → stays put.
        $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $q1, 'choice_id' => $c1['A']])->assertOk();
        $this->forceGame($attempt, ['position' => 97]);
        $this->fixedDice->diceQueue = [5];
        $stay = $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-roll', $attempt), ['question_id' => $q1])->assertOk()->json();
        $this->assertFalse($stay['moved']);
        $this->assertSame(97, $stay['to']);

        // Exact: roll 3 → tile 100, board completed — but the sitting continues.
        $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $q2, 'choice_id' => $c2['C']])->assertOk();
        $this->fixedDice->diceQueue = [3];
        $win = $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-roll', $attempt), ['question_id' => $q2])->assertOk()->json();
        $this->assertTrue($win['board_completed']);
        $this->assertSame(100, $win['to']);

        $this->assertSame('in_progress', $attempt->fresh()->status);

        // The remaining required question is still answerable after tile 100.
        $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $q3, 'choice_id' => $c3['E']])
            ->assertOk();
    }

    public function test_cap_finish_rule_caps_overshoot_at_100(): void
    {
        $this->useFixedDice();
        $test = $this->makeGameTest([], ['finish_rule' => 'cap']);
        [$q1, $c1] = $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $attempt = $this->beginAttempt($test);

        $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $q1, 'choice_id' => $c1['A']])->assertOk();
        $this->forceGame($attempt, ['position' => 97]);
        $this->fixedDice->diceQueue = [5];

        $capped = $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-roll', $attempt), ['question_id' => $q1])->assertOk()->json();
        $this->assertSame(100, $capped['to']);
        $this->assertTrue($capped['board_completed']);
    }

    public function test_answers_lock_exactly_once(): void
    {
        $test = $this->makeGameTest();
        [$qid, $choices] = $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $attempt = $this->beginAttempt($test);

        $first = $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $qid, 'choice_id' => $choices['A']])
            ->assertOk()->json();
        $this->assertFalse($first['duplicate']);

        // A retried submit — even with the OTHER choice — replays the lock.
        $second = $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $qid, 'choice_id' => $choices['B']])
            ->assertOk()->json();
        $this->assertTrue($second['duplicate']);
        $this->assertTrue($second['correct']);
        $this->assertSame(1, $second['game']['correct']);
    }

    public function test_academic_score_counts_answers_only_never_the_board(): void
    {
        $test = $this->makeGameTest();
        [$q1, $c1] = $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        [$q2, $c2] = $this->question($test->id, 2, 'multiple_choice', ['C' => true, 'D' => false]);
        $attempt = $this->beginAttempt($test);

        // One right (never rolled — dice left on the table), one wrong.
        $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $q1, 'choice_id' => $c1['A']])->assertOk();
        $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $q2, 'choice_id' => $c2['D']])->assertOk();

        // Inflate the GAME state to absurdity — the grade must not care.
        $this->forceGame($attempt, ['position' => 100, 'score' => 999999, 'board_completed' => true]);

        $this->actingAs($this->studentUser)
            ->post(route('student.assessments.board-finish', $attempt))
            ->assertRedirect(route('student.assessments.result', $test));

        $attempt->refresh();
        $this->assertSame('graded', $attempt->status);
        $this->assertSame(1.0, (float) $attempt->raw_score);
        $this->assertSame(2.0, (float) $attempt->max_score);
        $this->assertSame(50.0, (float) $attempt->percentage);
    }

    public function test_refresh_resumes_the_same_board_position_and_pending_roll(): void
    {
        $test = $this->makeGameTest();
        [$qid, $choices] = $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $this->question($test->id, 2, 'multiple_choice', ['C' => true, 'D' => false]);
        $attempt = $this->beginAttempt($test);

        $this->actingAs($this->studentUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $qid, 'choice_id' => $choices['A']])->assertOk();

        // Refresh: same attempt, armed roll and position survive in the payload.
        $page = $this->actingAs($this->studentUser)->get(route('student.assessments.board', $test))->assertOk();
        $this->assertSame(1, TestAttempt::where('test_id', $test->id)->where('student_id', $this->student->id)->count());
        $this->assertStringContainsString('"pending_roll":'.$qid, $page->getContent());
    }

    public function test_other_students_cannot_touch_the_attempt(): void
    {
        $test = $this->makeGameTest();
        [$qid, $choices] = $this->question($test->id, 1, 'multiple_choice', ['A' => true, 'B' => false]);
        $attempt = $this->beginAttempt($test);

        $otherUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        Student::create(['school_id' => $this->school->id, 'user_id' => $otherUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Bea', 'last_name' => 'Cruz']);

        $this->actingAs($otherUser)
            ->postJson(route('student.assessments.board-answer', $attempt), ['question_id' => $qid, 'choice_id' => $choices['A']])
            ->assertForbidden();
        $this->actingAs($otherUser)
            ->postJson(route('student.assessments.board-roll', $attempt), ['question_id' => $qid])
            ->assertForbidden();
    }
}
