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
 * Quiz Speed Dash — the graded game delivery of an online test.
 *
 * Pins the security spine: the game rides the SAME attempt + grading pipeline
 * as a standard quiz, the answer key never reaches the browser before an
 * answer is locked, each answer locks exactly once (idempotent), losing every
 * heart never ends a graded attempt, and the game score lives only in
 * meta['game'] — it can never move the academic percentage.
 */
class SpeedDashGradedModeTest extends TestCase
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
        $this->topicId = DB::table('topics')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $this->subjectId, 'name' => 'Planets', 'created_at' => now(), 'updated_at' => now()]);
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

    private function makeGameTest(array $settings = [], bool $published = true): Test
    {
        $test = Test::create(['school_id' => $this->school->id, 'class_id' => $this->classId, 'subject_id' => $this->subjectId, 'teacher_id' => $this->teacher->id, 'title' => 'Planet Dash', 'status' => $published ? 'published' : 'draft']);
        TestSetting::create(array_merge([
            'test_id' => $test->id, 'mode' => 'online', 'play_mode' => 'speed_dash',
            'availability_mode' => 'duration', 'duration_minutes' => 30,
            'attempts_allowed' => 1, 'show_results' => 'immediate',
        ], $settings));

        return $test;
    }

    private function beginAttempt(Test $test): TestAttempt
    {
        $this->actingAs($this->studentUser)->get(route('student.assessments.play', $test))->assertOk();

        return TestAttempt::where('test_id', $test->id)->where('student_id', $this->student->id)->firstOrFail();
    }

    // ------------------------------------------------------------ teacher

    public function test_teacher_can_enable_speed_dash_for_an_eligible_test(): void
    {
        $test = $this->makeGameTest(['play_mode' => 'standard']);
        $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false, 'Jupiter' => false]);
        $this->question($test->id, 2, 'true_false', ['True' => true, 'False' => false]);

        $this->actingAs($this->teacher)->get(route('teacher.tests.game', $test))->assertOk()->assertSee('Ready to play');

        $this->actingAs($this->teacher)->post(route('teacher.tests.game.save', $test), [
            'enabled' => 1, 'starting_lives' => 4, 'instant_submit' => 0,
            'powerups_enabled' => 1, 'speed_bonus_max' => 10,
        ])->assertRedirect(route('teacher.tests.game', $test));

        $settings = $test->fresh('settings')->settings;
        $this->assertSame('speed_dash', $settings->play_mode);
        $this->assertSame(4, $settings->game_settings['starting_lives']);
        $this->assertFalse($settings->game_settings['instant_submit']);
    }

    public function test_enabling_is_refused_when_a_question_type_does_not_fit(): void
    {
        $test = $this->makeGameTest(['play_mode' => 'standard']);
        $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false]);
        $this->question($test->id, 2, 'essay', []);

        $this->actingAs($this->teacher)->post(route('teacher.tests.game.save', $test), [
            'enabled' => 1, 'starting_lives' => 3, 'instant_submit' => 1,
            'powerups_enabled' => 1, 'speed_bonus_max' => 20,
        ])->assertSessionHas('error');

        $this->assertSame('standard', $test->fresh('settings')->settings->play_mode);
    }

    public function test_only_the_author_may_change_game_settings(): void
    {
        $test = $this->makeGameTest();
        $other = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);

        $this->actingAs($other)->get(route('teacher.tests.game', $test))->assertForbidden();
    }

    // ------------------------------------------------------------ student

    public function test_play_screen_freezes_an_attempt_and_never_leaks_the_answer_key(): void
    {
        $test = $this->makeGameTest();
        $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false, 'Jupiter' => false]);
        $this->question($test->id, 2, 'true_false', ['True' => true, 'False' => false]);

        $response = $this->actingAs($this->studentUser)->get(route('student.assessments.play', $test));
        $response->assertOk()->assertSee('Quiz Speed Dash');

        // The frozen attempt exists with one answer row per question.
        $attempt = TestAttempt::where('test_id', $test->id)->where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame(2, $attempt->answers()->count());

        // No correctness marker anywhere in the page payload before answering.
        $response->assertDontSee('is_correct');
        $response->assertDontSee('was_correct":true', false);
        $response->assertDontSee('correct_choice');
    }

    public function test_five_choice_questions_carry_all_five_gates(): void
    {
        $test = $this->makeGameTest();
        $this->question($test->id, 1, 'multiple_choice', ['A1' => false, 'B2' => false, 'C3' => true, 'D4' => false, 'E5' => false]);

        $this->beginAttempt($test);
        $payload = app(\App\Services\Games\SpeedDashAttemptService::class)
            ->payload(TestAttempt::where('test_id', $test->id)->firstOrFail());

        $this->assertCount(5, $payload['questions'][0]['choices']);
        $this->assertArrayNotHasKey('is_correct', $payload['questions'][0]['choices'][0]);
    }

    public function test_a_test_with_an_incompatible_question_is_not_playable(): void
    {
        $test = $this->makeGameTest();
        $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false]);
        $this->question($test->id, 2, 'essay', []);

        $this->actingAs($this->studentUser)->get(route('student.assessments.play', $test))->assertStatus(409);
    }

    public function test_correct_answer_locks_and_moves_only_game_meters(): void
    {
        $test = $this->makeGameTest();
        [$qid, $c] = $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false]);
        $attempt = $this->beginAttempt($test);

        $res = $this->postJson(route('student.assessments.game-answer', $attempt), [
            'question_id' => $qid, 'choice_id' => $c['Mars'], 'response_ms' => 2500,
        ]);

        $res->assertOk()->assertJson(['ok' => true, 'correct' => true, 'duplicate' => false]);
        $this->assertGreaterThanOrEqual(100, $res->json('game.score'));
        $this->assertSame(1, $res->json('game.streak'));
        $this->assertSame(3, $res->json('game.hearts'));

        $answer = $attempt->answers()->where('question_id', $qid)->first();
        $this->assertTrue((bool) $answer->is_correct);
        $this->assertSame('1.00', (string) $answer->points_earned);

        // Academic totals are untouched until final submit.
        $this->assertNull($attempt->fresh()->percentage);
    }

    public function test_wrong_answer_costs_a_heart_and_hides_the_key_when_not_allowed(): void
    {
        $test = $this->makeGameTest(['show_correct_answers' => 0]);
        [$qid, $c] = $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false]);
        $attempt = $this->beginAttempt($test);

        $res = $this->postJson(route('student.assessments.game-answer', $attempt), [
            'question_id' => $qid, 'choice_id' => $c['Venus'],
        ]);

        $res->assertOk()->assertJson(['correct' => false]);
        $this->assertNull($res->json('correct_choice_id'));
        $this->assertSame(2, $res->json('game.hearts'));
        $this->assertSame(0, $res->json('game.streak'));
    }

    public function test_wrong_answer_reveals_the_key_when_settings_allow(): void
    {
        $test = $this->makeGameTest(['show_correct_answers' => 1]);
        [$qid, $c] = $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false]);
        $attempt = $this->beginAttempt($test);

        $res = $this->postJson(route('student.assessments.game-answer', $attempt), [
            'question_id' => $qid, 'choice_id' => $c['Venus'],
        ]);

        $this->assertSame($c['Mars'], $res->json('correct_choice_id'));
    }

    public function test_answers_lock_exactly_once_and_replay_idempotently(): void
    {
        $test = $this->makeGameTest();
        [$qid, $c] = $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false]);
        $attempt = $this->beginAttempt($test);

        $this->postJson(route('student.assessments.game-answer', $attempt), ['question_id' => $qid, 'choice_id' => $c['Mars']])->assertOk();
        $scoreAfterFirst = $attempt->fresh()->meta['game']['score'];

        // A replayed request — even flipping to the WRONG choice — changes nothing.
        $dup = $this->postJson(route('student.assessments.game-answer', $attempt), ['question_id' => $qid, 'choice_id' => $c['Venus']]);
        $dup->assertOk()->assertJson(['duplicate' => true, 'correct' => true]);
        $this->assertSame($scoreAfterFirst, $attempt->fresh()->meta['game']['score']);
        $this->assertTrue((bool) $attempt->answers()->where('question_id', $qid)->first()->is_correct);
    }

    public function test_a_choice_from_another_question_is_rejected(): void
    {
        $test = $this->makeGameTest();
        [$q1] = $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false]);
        [, $c2] = $this->question($test->id, 2, 'true_false', ['True' => true, 'False' => false]);
        $attempt = $this->beginAttempt($test);

        $this->postJson(route('student.assessments.game-answer', $attempt), [
            'question_id' => $q1, 'choice_id' => $c2['True'],
        ])->assertStatus(422);
    }

    public function test_losing_every_heart_enters_recovery_but_the_run_finishes_and_grades(): void
    {
        $test = $this->makeGameTest(['game_settings' => ['starting_lives' => 2, 'instant_submit' => true, 'powerups_enabled' => true, 'speed_bonus_max' => 20]]);
        [$q1, $c1] = $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false]);
        [$q2, $c2] = $this->question($test->id, 2, 'multiple_choice', ['Blue' => true, 'Red' => false]);
        [$q3, $c3] = $this->question($test->id, 3, 'true_false', ['True' => true, 'False' => false]);
        $attempt = $this->beginAttempt($test);

        $this->postJson(route('student.assessments.game-answer', $attempt), ['question_id' => $q1, 'choice_id' => $c1['Venus']]);
        $res = $this->postJson(route('student.assessments.game-answer', $attempt), ['question_id' => $q2, 'choice_id' => $c2['Red']]);
        $this->assertSame(0, $res->json('game.hearts'));
        $this->assertTrue($res->json('game.recovery'));

        // Recovery: still answering, correct earns base points only.
        $res3 = $this->postJson(route('student.assessments.game-answer', $attempt), ['question_id' => $q3, 'choice_id' => $c3['True'], 'response_ms' => 800]);
        $res3->assertOk()->assertJson(['correct' => true]);
        $this->assertSame(100, $res3->json('game.score'));

        // Finish → graded 1/3 by the shared grader.
        $this->post(route('student.assessments.game-finish', $attempt))
            ->assertRedirect(route('student.assessments.result', $test));
        $attempt->refresh();
        $this->assertSame('graded', $attempt->status);
        $this->assertSame('33.33', (string) $attempt->percentage);
        $this->assertSame(100, $attempt->meta['game']['score']);
    }

    public function test_streaks_earn_boost_then_shield_and_shield_absorbs_a_wrong_answer(): void
    {
        $test = $this->makeGameTest();
        $qs = [];
        foreach (range(1, 6) as $i) {
            $qs[] = $this->question($test->id, $i, 'multiple_choice', ['Yes'.$i => true, 'No'.$i => false]);
        }
        $attempt = $this->beginAttempt($test);

        foreach (range(0, 4) as $i) {
            [$qid, $c] = $qs[$i];
            $res = $this->postJson(route('student.assessments.game-answer', $attempt), ['question_id' => $qid, 'choice_id' => $c['Yes'.($i + 1)]]);
        }
        $this->assertSame(5, $res->json('game.streak'));
        $this->assertSame(1, $res->json('game.boosts'));
        $this->assertTrue($res->json('game.shield'));

        // Wrong answer: the shield is consumed, hearts stay full.
        [$q6, $c6] = $qs[5];
        $res = $this->postJson(route('student.assessments.game-answer', $attempt), ['question_id' => $q6, 'choice_id' => $c6['No6']]);
        $this->assertFalse($res->json('game.shield'));
        $this->assertSame(3, $res->json('game.hearts'));
    }

    public function test_game_score_never_moves_the_academic_percentage(): void
    {
        $test = $this->makeGameTest();
        [$q1, $c1] = $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false], 'advanced');
        [$q2, $c2] = $this->question($test->id, 2, 'true_false', ['True' => true, 'False' => false]);
        $attempt = $this->beginAttempt($test);

        // Fast + advanced + streak → big game score; academic stays 1 point per hit.
        $this->postJson(route('student.assessments.game-answer', $attempt), ['question_id' => $q1, 'choice_id' => $c1['Mars'], 'response_ms' => 900]);
        $this->postJson(route('student.assessments.game-answer', $attempt), ['question_id' => $q2, 'choice_id' => $c2['True'], 'response_ms' => 1000]);

        $this->post(route('student.assessments.game-finish', $attempt));
        $attempt->refresh();

        $this->assertSame('100.00', (string) $attempt->percentage);
        $this->assertSame('2.00', (string) $attempt->raw_score);
        $this->assertGreaterThan(200, $attempt->meta['game']['score']); // bonuses live in the game world only
    }

    public function test_resume_replays_locked_answers_without_regrading(): void
    {
        $test = $this->makeGameTest();
        [$q1, $c1] = $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false]);
        $this->question($test->id, 2, 'true_false', ['True' => true, 'False' => false]);
        $attempt = $this->beginAttempt($test);

        $this->postJson(route('student.assessments.game-answer', $attempt), ['question_id' => $q1, 'choice_id' => $c1['Mars']]);

        // "Refresh": the play screen resumes the same attempt, marking Q1 done.
        $this->actingAs($this->studentUser)->get(route('student.assessments.play', $test))->assertOk();
        $payload = app(\App\Services\Games\SpeedDashAttemptService::class)->payload($attempt->fresh());

        $q1Row = collect($payload['questions'])->firstWhere('question_id', $q1);
        $this->assertTrue($q1Row['answered']);
        $this->assertTrue($q1Row['was_correct']);
        $this->assertSame(1, TestAttempt::where('test_id', $test->id)->count());
    }

    public function test_standard_take_screen_redirects_to_the_game(): void
    {
        $test = $this->makeGameTest();
        $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false]);

        $this->actingAs($this->studentUser)->post(route('student.assessments.begin', $test));
        $this->actingAs($this->studentUser)->get(route('student.assessments.take', $test))
            ->assertRedirect(route('student.assessments.play', $test));
    }

    public function test_another_student_cannot_touch_the_attempt(): void
    {
        $test = $this->makeGameTest();
        [$qid, $c] = $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false]);
        $attempt = $this->beginAttempt($test);

        $otherUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        Student::create(['school_id' => $this->school->id, 'user_id' => $otherUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ben', 'last_name' => 'Cruz']);

        $this->actingAs($otherUser)->postJson(route('student.assessments.game-answer', $attempt), [
            'question_id' => $qid, 'choice_id' => $c['Mars'],
        ])->assertForbidden();
    }

    public function test_a_standard_test_is_not_playable_as_a_game(): void
    {
        $test = $this->makeGameTest(['play_mode' => 'standard']);
        $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false]);

        $this->actingAs($this->studentUser)->get(route('student.assessments.play', $test))->assertNotFound();
    }

    public function test_result_page_shows_both_scores_after_a_run(): void
    {
        $test = $this->makeGameTest();
        [$qid, $c] = $this->question($test->id, 1, 'multiple_choice', ['Mars' => true, 'Venus' => false]);
        $attempt = $this->beginAttempt($test);

        $this->postJson(route('student.assessments.game-answer', $attempt), ['question_id' => $qid, 'choice_id' => $c['Mars'], 'response_ms' => 1200]);
        $this->post(route('student.assessments.game-finish', $attempt));

        $this->actingAs($this->studentUser)->get(route('student.assessments.result', $test))
            ->assertOk()
            ->assertSee('100%')
            ->assertSee('Quiz Speed Dash run')
            ->assertSee('Best streak');
    }
}
