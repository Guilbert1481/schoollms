<?php

namespace Tests\Unit;

use App\Services\Games\SnakesBoard;
use PHPUnit\Framework\TestCase;

/**
 * Pure board math: serpentine coordinates, layout validation, movement
 * resolution (exact / bounce / cap), and single-application snake/ladder
 * events. No DB — the board engine is deliberately side-effect free.
 */
class SnakesBoardTest extends TestCase
{
    public function test_serpentine_coordinates_for_the_landmark_tiles(): void
    {
        // row 0 = BOTTOM row, col 0 = LEFT column.
        $this->assertSame(['row' => 0, 'col' => 0], SnakesBoard::coord(1));    // START, bottom-left
        $this->assertSame(['row' => 0, 'col' => 9], SnakesBoard::coord(10));   // bottom-right
        $this->assertSame(['row' => 1, 'col' => 9], SnakesBoard::coord(11));   // row 2 starts on the RIGHT
        $this->assertSame(['row' => 1, 'col' => 0], SnakesBoard::coord(20));   // row 2 ends on the LEFT
        $this->assertSame(['row' => 2, 'col' => 0], SnakesBoard::coord(21));   // row 3 back to the LEFT
        $this->assertSame(['row' => 4, 'col' => 9], SnakesBoard::coord(50));   // odd row runs right→left
        $this->assertSame(['row' => 9, 'col' => 9], SnakesBoard::coord(91));   // top row starts on the RIGHT
        $this->assertSame(['row' => 9, 'col' => 1], SnakesBoard::coord(99));
        $this->assertSame(['row' => 9, 'col' => 0], SnakesBoard::coord(100));  // FINISH at top-LEFT
    }

    public function test_every_tile_maps_to_a_unique_cell(): void
    {
        $seen = [];
        for ($tile = 1; $tile <= 100; $tile++) {
            $c = SnakesBoard::coord($tile);
            $this->assertGreaterThanOrEqual(0, $c['row']);
            $this->assertLessThan(10, $c['row']);
            $this->assertGreaterThanOrEqual(0, $c['col']);
            $this->assertLessThan(10, $c['col']);
            $seen[$c['row'].'-'.$c['col']] = true;
        }
        $this->assertCount(100, $seen); // 100 tiles → 100 distinct cells
    }

    public function test_off_board_tiles_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SnakesBoard::coord(101);
    }

    public function test_the_default_layout_is_valid(): void
    {
        $this->assertSame([], SnakesBoard::validate(SnakesBoard::defaultLayout()));
    }

    public function test_validation_rejects_bad_layouts(): void
    {
        $problems = SnakesBoard::validate([
            'snakes' => [40 => 60, 1 => 5, 105 => 3],   // upward snake, START origin, off-board
            'ladders' => [50 => 20, 40 => 80],           // downward ladder, tile shared with a snake
        ]);

        $text = implode(' ', $problems);
        $this->assertStringContainsString('must move downward', $text);
        $this->assertStringContainsString('must move upward', $text);
        $this->assertStringContainsString('off the board', $text);
        $this->assertStringContainsString('START or FINISH', $text);
        $this->assertStringContainsString('hosts both a snake and a ladder', $text);
    }

    public function test_validation_rejects_chained_features(): void
    {
        // 30's ladder lands on 60, which is a snake's head → forbidden chain.
        $problems = SnakesBoard::validate([
            'snakes' => [60 => 12],
            'ladders' => [30 => 60],
        ]);
        $this->assertStringContainsString('chained moves are not allowed', implode(' ', $problems));
    }

    public function test_random_layouts_are_valid_and_reproducible(): void
    {
        $a = SnakesBoard::randomLayout(12345);
        $b = SnakesBoard::randomLayout(12345);
        $c = SnakesBoard::randomLayout(54321);

        $this->assertSame($a, $b);              // same seed → same board
        $this->assertNotSame($a, $c);           // different seed → different board
        $this->assertSame([], SnakesBoard::validate($a));
        $this->assertSame([], SnakesBoard::validate($c));
        $this->assertNotEmpty($a['snakes']);
        $this->assertNotEmpty($a['ladders']);
    }

    public function test_plain_movement_and_ladder_and_snake_resolution(): void
    {
        $layout = ['snakes' => [27 => 9], 'ladders' => [25 => 60]];

        $plain = SnakesBoard::resolveMove(10, 4, $layout, SnakesBoard::FINISH_EXACT);
        $this->assertSame(14, $plain['to']);
        $this->assertNull($plain['event']);

        $ladder = SnakesBoard::resolveMove(23, 2, $layout, SnakesBoard::FINISH_EXACT);
        $this->assertSame(25, $ladder['landed']);
        $this->assertSame(60, $ladder['to']);
        $this->assertSame('ladder', $ladder['event']['type']);

        $snake = SnakesBoard::resolveMove(24, 3, $layout, SnakesBoard::FINISH_EXACT);
        $this->assertSame(27, $snake['landed']);
        $this->assertSame(9, $snake['to']);
        $this->assertSame('snake', $snake['event']['type']);
    }

    public function test_exact_finish_rule_blocks_overshoot(): void
    {
        $move = SnakesBoard::resolveMove(97, 5, SnakesBoard::defaultLayout(), SnakesBoard::FINISH_EXACT);
        $this->assertFalse($move['moved']);
        $this->assertSame(97, $move['to']);

        $exact = SnakesBoard::resolveMove(97, 3, ['snakes' => [], 'ladders' => []], SnakesBoard::FINISH_EXACT);
        $this->assertTrue($exact['finished']);
        $this->assertSame(100, $exact['to']);
    }

    public function test_bounce_back_finish_rule(): void
    {
        // 97 + 5 → 100, bounce back 2 → 98.
        $move = SnakesBoard::resolveMove(97, 5, ['snakes' => [], 'ladders' => []], SnakesBoard::FINISH_BOUNCE);
        $this->assertTrue($move['moved']);
        $this->assertTrue($move['bounced']);
        $this->assertSame(98, $move['to']);
        $this->assertFalse($move['finished']);
    }

    public function test_cap_finish_rule(): void
    {
        $move = SnakesBoard::resolveMove(97, 5, ['snakes' => [], 'ladders' => []], SnakesBoard::FINISH_CAP);
        $this->assertSame(100, $move['to']);
        $this->assertTrue($move['finished']);
    }

    public function test_knowledge_and_accuracy_dice_ranges(): void
    {
        $this->assertSame(['min' => 1, 'max' => 6], SnakesBoard::diceRange(SnakesBoard::POLICY_CLASSIC, null));
        $this->assertSame(['min' => 1, 'max' => 3], SnakesBoard::diceRange(SnakesBoard::POLICY_KNOWLEDGE, 'average'));
        $this->assertSame(['min' => 4, 'max' => 6], SnakesBoard::diceRange(SnakesBoard::POLICY_KNOWLEDGE, 'advanced'));
        $this->assertSame(['min' => 4, 'max' => 4], SnakesBoard::diceRange(SnakesBoard::POLICY_ACCURACY, 'average'));
    }
}
