<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every game converted to the shared <x-gamified-configuration> component
 * (Constitution §11B) must actually render it through the real HTTP + Blade
 * stack. This guards the Blade footgun where a literal <x-...> in a JS comment
 * — or a nested {{-- --}} — breaks compilation with an "unexpected endif",
 * which a JSON/endpoint test would never catch.
 *
 * @dataProvider convertedGames
 */
class GamifiedConfigRenderTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\DataProvider('convertedGames')]
    public function test_converted_game_renders_the_shared_config_component(string $slug, string $gameScreenMarker): void
    {
        $user = User::factory()->create([
            'school_id' => School::factory()->create()->id,
            'role' => 'teacher',
        ]);

        $this->actingAs($user)
            ->get(route('tools.games.play', ['slug' => $slug]))
            ->assertOk()
            ->assertSee('data-gconf', false)        // the shared component rendered
            ->assertSee('id="gconfStart"', false)   // its Start button
            ->assertSee($gameScreenMarker, false);  // the game's own screen is present
    }

    /** @return array<string, array{0:string,1:string}> */
    public static function convertedGames(): array
    {
        return [
            'tug-of-war' => ['tug-of-war', 'id="tugGame"'],
            'hangman' => ['hangman', 'id="hmGame"'],
            'anagrams' => ['anagrams', 'id="agGame"'],
        ];
    }
}
