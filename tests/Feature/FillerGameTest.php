<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filler (verb-forms trainer) is a client-side game: no controller logic or
 * DB beyond the shared games catalog. These guard that the catalog entry and
 * the Blade partial exist and render through the real HTTP + view stack — a
 * missing partial or a Blade-compile error in the @verbatim block would 500.
 */
class FillerGameTest extends TestCase
{
    use RefreshDatabase;

    private function authUser(): User
    {
        $school = School::factory()->create();

        return User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
    }

    public function test_filler_appears_in_the_games_catalog(): void
    {
        $this->actingAs($this->authUser())
            ->get(route('tools.games.index'))
            ->assertOk()
            ->assertSee('Filler');
    }

    public function test_filler_play_page_renders_the_verb_table(): void
    {
        $this->actingAs($this->authUser())
            ->get(route('tools.games.play', ['slug' => 'filler']))
            ->assertOk()
            ->assertSee('Your verb list')
            ->assertSee('Base form');
    }

    public function test_filler_play_page_renders_the_table_pagination_controls(): void
    {
        $this->actingAs($this->authUser())
            ->get(route('tools.games.play', ['slug' => 'filler']))
            ->assertOk()
            ->assertSee('Rows per page')
            ->assertSee('id="flRpp"', false)
            ->assertSee('id="flPrev"', false)
            ->assertSee('id="flNextPg"', false)
            ->assertSee('id="flRange"', false);
    }
}
