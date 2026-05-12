<?php

namespace App\Http\Controllers\Tools\Games;

use App\Http\Controllers\Controller;

/**
 * Gamified Quiz catalog controller.
 *
 * Lists the available game templates and serves an individual game
 * placeholder page. Each game is a separate Blade partial under
 * resources/views/tools/games/games/{slug}.blade.php so they can be
 * fleshed out independently without touching the catalog.
 */
class GamesController extends Controller
{
    /**
     * The catalog of supported game templates.
     * Adding a new game = add an entry here and a matching partial.
     */
    public const GAMES = [
        ['slug' => 'millionaire',          'title' => 'Who Wants to Be a Millionaire?', 'icon' => 'crown',          'color' => 'from-amber-700 via-orange-800 to-red-900',     'description' => 'Climb the ladder of escalating questions with lifelines and a dramatic reveal.'],
        ['slug' => 'speed-dash',           'title' => 'The Endless Runner / Speed Dash','icon' => 'zap',            'color' => 'from-cyan-700 via-sky-800 to-blue-900',        'description' => 'Sprint through obstacles by answering correctly under increasing time pressure.'],
        ['slug' => 'tower-defense',        'title' => 'Tower Defense / Base Builder',   'icon' => 'shield',         'color' => 'from-emerald-700 via-teal-800 to-cyan-900',    'description' => 'Defend the base by answering questions to deploy towers and counter waves.'],
        ['slug' => 'detective',            'title' => 'Detective Mystery Solver',       'icon' => 'search',         'color' => 'from-slate-700 via-zinc-700 to-stone-800',     'description' => 'Gather clues and crack the case by deducing the right answer.'],
        ['slug' => 'card-battler',         'title' => 'Card Battler',                   'icon' => 'layers',         'color' => 'from-fuchsia-700 via-purple-800 to-indigo-900','description' => 'Battle opponents by playing answer cards to deal damage and unlock combos.'],
        ['slug' => 'crossword',            'title' => 'Crossword Puzzle',               'icon' => 'grid-3x3',       'color' => 'from-blue-700 via-sky-800 to-indigo-900',      'description' => 'Solve themed crossword puzzles built from your subject vocabulary.'],
        ['slug' => 'word-search',          'title' => 'Word Search',                    'icon' => 'search-code',    'color' => 'from-lime-700 via-emerald-800 to-teal-900',    'description' => 'Find hidden subject terms in a randomized letter grid.'],
        ['slug' => 'cryptogram',           'title' => 'Cryptogram',                     'icon' => 'lock',           'color' => 'from-violet-700 via-purple-800 to-fuchsia-900','description' => 'Decode encrypted quotes or facts to reveal the answer.'],
        ['slug' => 'anagrams',             'title' => 'Scrambled Words / Anagrams',     'icon' => 'shuffle',        'color' => 'from-rose-700 via-pink-800 to-fuchsia-900',    'description' => 'Unscramble jumbled letters back into the correct keyword.'],
        ['slug' => 'memory-match',         'title' => 'Memory Match Tiles',             'icon' => 'square-stack',   'color' => 'from-pink-700 via-rose-800 to-red-900',        'description' => 'Match question tiles with the correct answer pairs from memory.'],
        ['slug' => 'hangman',              'title' => 'Hangman',                        'icon' => 'spell-check',    'color' => 'from-stone-700 via-zinc-800 to-slate-900',     'description' => 'Guess letters one at a time before the figure is fully drawn.'],
        ['slug' => 'category-sorter',      'title' => 'Drag and Drop Category Sorter',  'icon' => 'columns-3',      'color' => 'from-orange-700 via-amber-800 to-yellow-900',  'description' => 'Drag items into the correct category bucket to score points.'],
        ['slug' => 'timeline-sequence',    'title' => 'Timeline / Sequence Ordering',   'icon' => 'list-ordered',   'color' => 'from-indigo-700 via-blue-800 to-sky-900',      'description' => 'Arrange events or steps into the correct chronological order.'],
        ['slug' => 'labeling-diagram',     'title' => 'Labeling Diagram Map',           'icon' => 'map-pin',        'color' => 'from-teal-700 via-cyan-800 to-sky-900',        'description' => 'Drop labels onto the right hotspots of a diagram or map.'],
        ['slug' => 'fill-blanks',          'title' => 'Fill-in-the-Blanks (Drag-to-Text)', 'icon' => 'pen-tool',    'color' => 'from-emerald-700 via-green-800 to-lime-900',   'description' => 'Drag words into the right slots of a sentence or paragraph.'],
        ['slug' => 'connector-lines',      'title' => 'Relational Connector Lines',     'icon' => 'git-fork',       'color' => 'from-purple-700 via-violet-800 to-indigo-900', 'description' => 'Connect prompts and answers by drawing matching lines.'],
        ['slug' => 'tug-of-war',           'title' => 'Tug-of-War (The Momentum Battle)','icon' => 'swords',        'color' => 'from-red-700 via-rose-800 to-pink-900',        'description' => 'Two-side momentum battle where each correct answer pulls the rope.'],
        ['slug' => 'snake-and-ladder',     'title' => 'Snake and Ladder',               'icon' => 'dice-5',         'color' => 'from-green-700 via-emerald-800 to-teal-900',   'description' => 'Roll the dice and answer to climb ladders, miss to slide down snakes.'],
    ];

    public function index()
    {
        return view('tools.games.index', [
            'games' => self::GAMES,
        ]);
    }

    public function play(string $slug)
    {
        $game = collect(self::GAMES)->firstWhere('slug', $slug);
        abort_if($game === null, 404);

        return view('tools.games.play', [
            'game'  => $game,
            'games' => self::GAMES,
        ]);
    }
}
