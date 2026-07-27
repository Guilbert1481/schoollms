<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hangman renders a drawn gallows + figure (SVG). This guards that the play
 * page compiles and renders through the real HTTP + view stack — the partial
 * mixes an @verbatim <style> block with an @json() route call in its script,
 * an easy combination to break, which would 500 the page.
 */
class HangmanGameTest extends TestCase
{
    use RefreshDatabase;

    private function authUser(): User
    {
        $school = School::factory()->create();

        return User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
    }

    public function test_hangman_play_page_renders_the_gallows(): void
    {
        $this->actingAs($this->authUser())
            ->get(route('tools.games.play', ['slug' => 'hangman']))
            ->assertOk()
            ->assertSee('Hangman Challenge')
            ->assertSee('Mistake Meter')  // the key-art mistake meter panel
            ->assertSee('hmP5', false);   // the sixth drawn body part exists in the SVG
    }
}
