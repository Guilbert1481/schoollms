<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Live Tug-of-War sessions (GameSessionController / GameSessionService).
 *
 * The guarantees under test are the security-critical ones: the question set is
 * drawn + snapshotted SERVER-SIDE (the correct answer never rides down in the
 * play payload), grading is authoritative, a player gets one buzz per question,
 * the fastest correct answer pulls the rope, and a session from another school
 * is invisible (tenant isolation via the school-scoped GameSession binding).
 */
class TugOfWarSessionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $host;

    /** @var array{level:int,subject:int,topic:int} */
    private array $curriculum;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->host = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->curriculum = $this->curriculumFor($this->school->id);
        $this->seedMcq('AVG', 'average', 8);
    }

    // ---- lobby / roster ----------------------------------------------------

    public function test_host_creates_a_team_session_with_join_code_and_seated_players(): void
    {
        $alice = $this->makeStudent('Alice', 'Reyes');
        $bob = $this->makeStudent('Bob', 'Santos');

        $res = $this->actingAs($this->host)->postJson(route('tools.games.sessions.store'), [
            'mode' => 'team',
            'question_type' => 'mcq',
            'difficulty' => 'average',
            'question_count' => 6,
            'players' => [
                ['student_id' => $alice, 'team' => 'A'],
                ['student_id' => $bob, 'team' => 'B'],
            ],
        ])->assertCreated();

        $this->assertNotEmpty($res->json('join_code'));
        $this->assertSame('lobby', $res->json('status'));

        $players = collect($res->json('players'));
        $this->assertTrue($players->contains(fn ($p) => $p['role'] === 'host'));
        $this->assertSame('A', $players->firstWhere('display_name', 'Alice Reyes')['team']);
        $this->assertSame('B', $players->firstWhere('display_name', 'Bob Santos')['team']);
    }

    public function test_roster_seeding_drops_a_student_from_another_school(): void
    {
        $otherSchool = School::factory()->create();
        $otherStudent = DB::table('students')->insertGetId($this->studentRow($otherSchool->id, 'Mallory', 'Cross'));
        $mine = $this->makeStudent('Grace', 'Lim');

        $res = $this->actingAs($this->host)->postJson(route('tools.games.sessions.store'), [
            'mode' => 'team',
            'question_type' => 'mcq',
            'difficulty' => 'average',
            'players' => [
                ['student_id' => $mine, 'team' => 'A'],
                ['student_id' => $otherStudent, 'team' => 'B'],
            ],
        ])->assertCreated();

        $names = collect($res->json('players'))->pluck('display_name');
        $this->assertTrue($names->contains('Grace Lim'));
        $this->assertFalse($names->contains('Mallory Cross'), 'a student from another school must not be seated');
    }

    public function test_classmates_endpoint_is_reachable_and_shaped(): void
    {
        $this->actingAs($this->host)
            ->getJson(route('tools.games.sessions.classmates'))
            ->assertOk()
            ->assertJsonStructure(['classes']);
    }

    // ---- start / server-authoritative draw --------------------------------

    public function test_start_snapshots_questions_server_side_and_hides_the_answer(): void
    {
        $session = $this->newSoloSession(6);
        $state = $this->actingAs($this->host)
            ->postJson(route('tools.games.sessions.start', $session))
            ->assertOk()->json();

        $this->assertSame('active', $state['status']);
        $this->assertSame(1, $state['current_sequence']);

        // The play payload must NOT reveal the correct answer of an open question.
        $this->assertNull($state['question']['correct_index'], 'the correct index must stay server-side');
        $this->assertNull($state['question']['answer_text']);

        // …but it IS persisted server-side for grading.
        $stored = DB::table('game_session_questions')->where('game_session_id', $session)->where('sequence', 1)->first();
        $this->assertNotNull($stored->correct_index);
        $this->assertNotEmpty($stored->choices);
    }

    public function test_not_enough_questions_is_a_422(): void
    {
        // Only 'average' items exist — a strict 'advanced' draw finds none.
        $res = $this->actingAs($this->host)->postJson(route('tools.games.sessions.store'), [
            'mode' => 'solo', 'question_type' => 'mcq', 'difficulty' => 'advanced', 'question_count' => 6,
        ])->assertCreated();

        $this->actingAs($this->host)
            ->postJson(route('tools.games.sessions.start', $res->json('id')))
            ->assertStatus(422);
    }

    // ---- grading / rope ----------------------------------------------------

    public function test_correct_answer_pulls_the_rope_and_advances(): void
    {
        $session = $this->startedSolo(6);

        $correct = $this->correctIndexFor($session, 1);
        $res = $this->actingAs($this->host)->postJson(route('tools.games.sessions.answer', $session), [
            'team' => 'A', 'choice_index' => $correct,
        ])->assertOk()->json();

        $this->assertTrue($res['ok']);
        $this->assertTrue($res['correct']);
        $this->assertGreaterThan(0, $res['state']['rope_position'], 'a correct Team A answer pulls the rope toward A');
        $this->assertSame(1, $res['state']['teams']['A']['correct']);
        $this->assertSame(2, $res['state']['current_sequence'], 'a closed question advances the round');
    }

    public function test_grading_is_authoritative_a_wrong_index_never_scores(): void
    {
        $session = $this->startedSolo(6);

        $correct = $this->correctIndexFor($session, 1);
        $wrong = $correct === 0 ? 1 : 0;

        $res = $this->actingAs($this->host)->postJson(route('tools.games.sessions.answer', $session), [
            'team' => 'A', 'choice_index' => $wrong,
        ])->assertOk()->json();

        $this->assertTrue($res['ok']);
        $this->assertFalse($res['correct']);
        $this->assertSame(0, $res['state']['rope_position']);
        $this->assertSame(1, $res['state']['current_sequence'], 'a wrong answer does not advance the question');
    }

    public function test_a_player_gets_only_one_buzz_per_question(): void
    {
        $session = $this->startedSolo(6);
        $wrong = $this->correctIndexFor($session, 1) === 0 ? 1 : 0;

        // Team A's representative buzzes wrong…
        $this->actingAs($this->host)->postJson(route('tools.games.sessions.answer', $session), [
            'team' => 'A', 'choice_index' => $wrong,
        ])->assertOk()->assertJson(['correct' => false]);

        // …and cannot buzz the same question again.
        $again = $this->actingAs($this->host)->postJson(route('tools.games.sessions.answer', $session), [
            'team' => 'A', 'choice_index' => $this->correctIndexFor($session, 1),
        ])->assertOk()->json();

        $this->assertFalse($again['ok'], 'the same player may not re-answer a question');
    }

    public function test_wrong_answer_leaves_the_question_open_for_the_other_side(): void
    {
        $session = $this->startedSolo(6);
        $correct = $this->correctIndexFor($session, 1);
        $wrong = $correct === 0 ? 1 : 0;

        // Team A misses — question stays open, rope unmoved.
        $this->actingAs($this->host)->postJson(route('tools.games.sessions.answer', $session), [
            'team' => 'A', 'choice_index' => $wrong,
        ])->assertOk();

        // Team B (the CPU seat) steals it and pulls the rope its way.
        $res = $this->actingAs($this->host)->postJson(route('tools.games.sessions.answer', $session), [
            'team' => 'B', 'choice_index' => $correct,
        ])->assertOk()->json();

        $this->assertTrue($res['correct']);
        $this->assertLessThan(0, $res['state']['rope_position'], 'a Team B answer pulls the rope toward B');
        $this->assertSame(2, $res['state']['current_sequence']);
    }

    // ---- authorization / tenancy ------------------------------------------

    public function test_a_session_from_another_school_is_not_found(): void
    {
        $session = $this->newSoloSession(6);

        $intruder = User::factory()->create(['school_id' => School::factory()->create()->id, 'role' => 'teacher']);

        $this->actingAs($intruder)->getJson(route('tools.games.sessions.show', $session))->assertNotFound();
        $this->actingAs($intruder)->postJson(route('tools.games.sessions.answer', $session), ['team' => 'A', 'choice_index' => 0])->assertNotFound();
    }

    public function test_only_the_host_may_start_the_match(): void
    {
        $session = $this->newSoloSession(6);
        $other = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);

        $this->actingAs($other)->postJson(route('tools.games.sessions.start', $session))->assertForbidden();
    }

    // ---- view render guard -------------------------------------------------

    public function test_play_page_renders_config_and_game_screens(): void
    {
        $res = $this->actingAs($this->host)
            ->get(route('tools.games.play', ['slug' => 'tug-of-war']))
            ->assertOk();

        // Scoped theme survived Blade compilation (the @verbatim-in-comment footgun).
        $res->assertSee('.tug-stage', false)
            ->assertSee('[data-game="tug-of-war"]', false);

        // Config screen: the new dropdowns.
        $res->assertSee('id="tugType"', false)
            ->assertSee('id="tugMode"', false)
            ->assertSee('Identification');

        // Game screen: the mockup's rope + live badge.
        $res->assertSee('id="tugKnot"', false)
            ->assertSee('Live Classroom Game', false);
    }

    // ---- helpers -----------------------------------------------------------

    /** Create a solo lobby, return its id. */
    private function newSoloSession(int $count): int
    {
        return (int) $this->actingAs($this->host)->postJson(route('tools.games.sessions.store'), [
            'mode' => 'solo', 'question_type' => 'mcq', 'difficulty' => 'average', 'question_count' => $count,
        ])->assertCreated()->json('id');
    }

    /** Create + start a solo session, return its id. */
    private function startedSolo(int $count): int
    {
        $id = $this->newSoloSession($count);
        $this->actingAs($this->host)->postJson(route('tools.games.sessions.start', $id))->assertOk();

        return $id;
    }

    private function correctIndexFor(int $sessionId, int $sequence): int
    {
        return (int) DB::table('game_session_questions')
            ->where('game_session_id', $sessionId)->where('sequence', $sequence)->value('correct_index');
    }

    private function makeStudent(string $first, string $last): int
    {
        return DB::table('students')->insertGetId($this->studentRow($this->school->id, $first, $last));
    }

    /** @return array<string, mixed> */
    private function studentRow(int $schoolId, string $first, string $last): array
    {
        return [
            'school_id' => $schoolId,
            'student_number' => 'S'.$schoolId.'-'.substr(md5($first.$last.$schoolId), 0, 8),
            'first_name' => $first,
            'last_name' => $last,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function seedMcq(string $prefix, string $difficulty, int $n): void
    {
        for ($i = 1; $i <= $n; $i++) {
            $qid = DB::table('questions')->insertGetId([
                'school_id' => $this->school->id,
                'teacher_id' => $this->host->id,
                'subject_id' => $this->curriculum['subject'],
                'topic_id' => $this->curriculum['topic'],
                'academic_level_id' => $this->curriculum['level'],
                'question_type' => 'multiple_choice',
                'question_text' => $prefix.' Tug question '.$i,
                'difficulty' => $difficulty,
                'explanation' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('choices')->insert([
                ['question_id' => $qid, 'choice_text' => 'Right '.$i, 'is_correct' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['question_id' => $qid, 'choice_text' => 'Wrong '.$i, 'is_correct' => 0, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    /** One Grade 7 / Science / Living Things chain for the school. */
    private function curriculumFor(int $schoolId): array
    {
        $levelId = DB::table('academic_levels')->insertGetId([
            'school_id' => $schoolId, 'name' => 'Grade 7', 'sequence_order' => 7, 'type' => 'basic',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $subjectId = DB::table('subjects')->insertGetId([
            'school_id' => $schoolId, 'code' => 'SCI-'.$schoolId, 'name' => 'Science',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $topicId = DB::table('topics')->insertGetId([
            'school_id' => $schoolId, 'subject_id' => $subjectId, 'name' => 'Living Things',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['level' => $levelId, 'subject' => $subjectId, 'topic' => $topicId];
    }
}
