<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The Millionaire game's difficulty selector (average | advanced | mixed) must
 * connect to the real question bank: 'average'/'advanced' return only that tier,
 * and 'mixed' climbs average-first then advanced (~50/50), backfilling from
 * whichever tier has supply so a thin tier never short-changes the run.
 *
 * The API payload does not echo the difficulty column, so each question is seeded
 * with a tier-tagged stem ("AVG …" / "ADV …") and tier membership is asserted from
 * the returned stems.
 */
class MillionaireGameDifficultyTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $player;

    /** @var array{level:int,subject:int,topic:int} */
    private array $curriculum;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->player = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->curriculum = $this->curriculumFor($this->school->id);
    }

    public function test_average_difficulty_returns_only_average_questions(): void
    {
        $this->seedTier('AVG', 'average', 6);
        $this->seedTier('ADV', 'advanced', 6);

        $stems = $this->play(['difficulty' => 'average', 'limit' => 6]);

        $this->assertCount(6, $stems);
        foreach ($stems as $stem) {
            $this->assertStringStartsWith('AVG', $stem, 'average selection must not leak an advanced item');
        }
    }

    public function test_advanced_difficulty_returns_only_advanced_questions(): void
    {
        $this->seedTier('AVG', 'average', 6);
        $this->seedTier('ADV', 'advanced', 6);

        $stems = $this->play(['difficulty' => 'advanced', 'limit' => 6]);

        $this->assertCount(6, $stems);
        foreach ($stems as $stem) {
            $this->assertStringStartsWith('ADV', $stem, 'advanced selection must not leak an average item');
        }
    }

    public function test_mixed_puts_the_average_block_first_then_advanced(): void
    {
        $this->seedTier('AVG', 'average', 6);
        $this->seedTier('ADV', 'advanced', 6);

        $stems = $this->play(['difficulty' => 'mixed', 'limit' => 8]);

        // ceil(8/2) = 4 average lead, the remaining 4 are advanced.
        $this->assertCount(8, $stems);
        foreach (array_slice($stems, 0, 4) as $stem) {
            $this->assertStringStartsWith('AVG', $stem, 'mixed must start with the average block');
        }
        foreach (array_slice($stems, 4, 4) as $stem) {
            $this->assertStringStartsWith('ADV', $stem, 'mixed must follow with the advanced block');
        }
    }

    public function test_mixed_backfills_from_average_when_no_advanced_exist(): void
    {
        // The live bank today holds only 'average' rows — mixed must still fill.
        $this->seedTier('AVG', 'average', 10);

        $stems = $this->play(['difficulty' => 'mixed', 'limit' => 8]);

        $this->assertCount(8, $stems);
        foreach ($stems as $stem) {
            $this->assertStringStartsWith('AVG', $stem, 'with no advanced authored, mixed falls back to average');
        }
    }

    public function test_a_strict_tier_with_no_questions_returns_empty_not_a_fallback(): void
    {
        $this->seedTier('AVG', 'average', 6);

        $stems = $this->play(['difficulty' => 'advanced', 'limit' => 6]);

        $this->assertSame([], $stems, 'a strict advanced request must not silently borrow average items');
    }

    public function test_play_page_renders_the_scoped_style_theme_and_difficulty_selector(): void
    {
        // Guards two things through the real HTTP + view stack:
        // 1) the scoped @verbatim <style> theme must survive Blade compilation —
        //    a top-of-file Blade comment that literally contained the @verbatim
        //    directive name once swallowed the entire CSS block, shipping an
        //    unstyled game with every screen stacked on top of each other;
        // 2) the new Difficulty selector and its tiers are present.
        $res = $this->actingAs($this->player)
            ->get(route('tools.games.play', ['slug' => 'millionaire']))
            ->assertOk();

        $res->assertSee('radial-gradient', false)   // a property that only lives inside the <style>
            ->assertSee('.ml-stage', false);        // a scoped rule from the theme

        $res->assertSee('id="mlDiff"', false)
            ->assertSee('Advanced')
            ->assertSee('Mixed (average', false);
    }

    // --- helpers ------------------------------------------------------------

    /**
     * Hit the game question endpoint and return the returned question stems.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, string>
     */
    private function play(array $query): array
    {
        $response = $this->actingAs($this->player)
            ->getJson(route('tools.games.questions', array_merge(['type' => 'mcq'], $query)));

        $response->assertOk();

        return array_map(fn ($q) => (string) $q['question'], $response->json('questions'));
    }

    /** Seed $n valid MCQ items in one tier, each stem prefixed for identification. */
    private function seedTier(string $prefix, string $difficulty, int $n): void
    {
        for ($i = 1; $i <= $n; $i++) {
            $questionId = DB::table('questions')->insertGetId([
                'school_id' => $this->school->id,
                'teacher_id' => $this->player->id,
                'subject_id' => $this->curriculum['subject'],
                'topic_id' => $this->curriculum['topic'],
                'academic_level_id' => $this->curriculum['level'],
                'question_type' => 'multiple_choice',
                'question_text' => $prefix.' item '.$i,
                'difficulty' => $difficulty,
                'explanation' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('choices')->insert([
                ['question_id' => $questionId, 'choice_text' => 'Right', 'is_correct' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['question_id' => $questionId, 'choice_text' => 'Wrong', 'is_correct' => 0, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    /** One Grade 7 / Science / Living Things chain for the school. */
    private function curriculumFor(int $schoolId): array
    {
        $levelId = DB::table('academic_levels')->insertGetId([
            'school_id' => $schoolId,
            'name' => 'Grade 7',
            'sequence_order' => 7,
            'type' => 'basic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subjectId = DB::table('subjects')->insertGetId([
            'school_id' => $schoolId,
            'code' => 'SCI-'.$schoolId,
            'name' => 'Science',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $topicId = DB::table('topics')->insertGetId([
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'name' => 'Living Things',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['level' => $levelId, 'subject' => $subjectId, 'topic' => $topicId];
    }
}
