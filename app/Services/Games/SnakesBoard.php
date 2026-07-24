<?php

namespace App\Services\Games;

/**
 * The Snakes & Ladders board engine — pure, deterministic board math shared by
 * every delivery (graded, practice, future live). No DB, no randomness of its
 * own: dice come from the caller, layouts are data, and every rule here is a
 * plain function so the whole board is unit-testable.
 *
 * Board contract: exactly 100 tiles, 10×10, serpentine (boustrophedon)
 * numbering — bottom row 1–10 left→right, next row 11–20 right→left, ending
 * with 100 at the TOP-LEFT. Tile 1 is START, tile 100 is FINISH.
 */
class SnakesBoard
{
    public const SIZE = 100;

    public const COLS = 10;

    public const ROWS = 10;

    /** Movement policies (how many tiles a correct answer earns). */
    public const POLICY_CLASSIC = 'classic';     // random 1–6

    public const POLICY_KNOWLEDGE = 'knowledge'; // dice range from question difficulty

    public const POLICY_ACCURACY = 'accuracy';   // fixed steps + streak bonus

    /** Exact-finish rules for reaching tile 100. */
    public const FINISH_EXACT = 'exact';   // overshoot = no movement

    public const FINISH_BOUNCE = 'bounce'; // overshoot bounces back off 100

    public const FINISH_CAP = 'cap';       // overshoot caps at 100

    public const POLICIES = [self::POLICY_CLASSIC, self::POLICY_KNOWLEDGE, self::POLICY_ACCURACY];

    public const FINISH_RULES = [self::FINISH_EXACT, self::FINISH_BOUNCE, self::FINISH_CAP];

    /**
     * Serpentine coordinates for a tile. Row 0 is the BOTTOM row (tiles 1–10);
     * col 0 is the LEFT column. Even rows run left→right, odd rows right→left —
     * which puts 100 at row 9, col 0 (top-left), matching the classic board.
     *
     * @return array{row: int, col: int}
     */
    public static function coord(int $tile): array
    {
        if ($tile < 1 || $tile > self::SIZE) {
            throw new \InvalidArgumentException("Tile {$tile} is off the board (1–".self::SIZE.').');
        }

        $row = intdiv($tile - 1, self::COLS);
        $offset = ($tile - 1) % self::COLS;
        $col = $row % 2 === 0 ? $offset : (self::COLS - 1 - $offset);

        return ['row' => $row, 'col' => $col];
    }

    /**
     * The curated default layout — friendly classic spread: snakes bite high
     * and drop mid/low, ladders lift from the early and middle board. Passes
     * validate() by construction (guarded by tests).
     *
     * @return array{snakes: array<int, int>, ladders: array<int, int>}
     */
    public static function defaultLayout(): array
    {
        return [
            'snakes' => [98 => 78, 95 => 56, 87 => 24, 72 => 51, 62 => 42, 49 => 30, 36 => 15],
            'ladders' => [4 => 25, 13 => 46, 21 => 58, 33 => 70, 43 => 77, 59 => 81, 66 => 88],
        ];
    }

    /**
     * A reproducible randomized layout: same seed → same board. Generates the
     * same counts as the default layout and always returns a valid board (the
     * candidate loop only accepts placements validate() would pass).
     *
     * @return array{snakes: array<int, int>, ladders: array<int, int>}
     */
    public static function randomLayout(int $seed): array
    {
        mt_srand($seed);

        $used = [];    // tiles already serving as an origin or destination
        $snakes = [];
        $ladders = [];

        $place = function (bool $isSnake) use (&$used) {
            for ($tries = 0; $tries < 200; $tries++) {
                if ($isSnake) {
                    $from = mt_rand(30, self::SIZE - 1);          // head high, never on 100
                    $to = mt_rand(2, max(2, $from - 15));         // meaningful slide down
                } else {
                    $from = mt_rand(2, 65);                        // base low, never on 1
                    $to = mt_rand(min(self::SIZE - 1, $from + 15), self::SIZE - 1); // real climb
                }
                if ($from === $to || $from <= 1 || $from >= self::SIZE) {
                    continue;
                }
                // No tile hosts two features, and no chains: a destination can
                // never be another feature's origin (and vice versa).
                if (isset($used[$from]) || isset($used[$to])) {
                    continue;
                }
                $used[$from] = true;
                $used[$to] = true;

                return [$from, $to];
            }

            return null;
        };

        foreach ([true, false] as $isSnake) {
            $want = count(self::defaultLayout()[$isSnake ? 'snakes' : 'ladders']);
            for ($i = 0; $i < $want; $i++) {
                $pair = $place($isSnake);
                if ($pair === null) {
                    continue;
                }
                if ($isSnake) {
                    $snakes[$pair[0]] = $pair[1];
                } else {
                    $ladders[$pair[0]] = $pair[1];
                }
            }
        }

        mt_srand(); // restore entropy for anything else using mt_rand

        return ['snakes' => $snakes, 'ladders' => $ladders];
    }

    /**
     * Validate a board layout. Empty result = valid.
     *
     * @param  array{snakes?: array<int, int>, ladders?: array<int, int>}  $layout
     * @return array<int, string> human-readable problems
     */
    public static function validate(array $layout): array
    {
        $snakes = (array) ($layout['snakes'] ?? []);
        $ladders = (array) ($layout['ladders'] ?? []);
        $problems = [];

        foreach (['snake' => $snakes, 'ladder' => $ladders] as $kind => $map) {
            foreach ($map as $from => $to) {
                $from = (int) $from;
                $to = (int) $to;
                if ($from < 1 || $from > self::SIZE || $to < 1 || $to > self::SIZE) {
                    $problems[] = ucfirst($kind)." {$from}→{$to} is off the board.";

                    continue;
                }
                if ($kind === 'snake' && $to >= $from) {
                    $problems[] = "Snake {$from}→{$to} must move downward.";
                }
                if ($kind === 'ladder' && $to <= $from) {
                    $problems[] = "Ladder {$from}→{$to} must move upward.";
                }
                if ($from === 1 || $from === self::SIZE) {
                    $problems[] = ucfirst($kind)." cannot start on START or FINISH (tile {$from}).";
                }
            }
        }

        $snakeFroms = array_map('intval', array_keys($snakes));
        $ladderFroms = array_map('intval', array_keys($ladders));
        foreach (array_intersect($snakeFroms, $ladderFroms) as $tile) {
            $problems[] = "Tile {$tile} hosts both a snake and a ladder.";
        }

        // No chains: any feature's destination must not be any feature's origin
        // (a landing that triggers a second slide/climb, or an infinite loop).
        $origins = array_merge($snakeFroms, $ladderFroms);
        $destinations = array_map('intval', array_merge(array_values($snakes), array_values($ladders)));
        foreach (array_intersect($destinations, $origins) as $tile) {
            $problems[] = "Tile {$tile} is both a destination and an origin — chained moves are not allowed.";
        }

        return $problems;
    }

    /**
     * Resolve one earned move, server-side: apply the finish rule, then a
     * single snake or ladder at the landing tile. Never chains.
     *
     * @param  array{snakes: array<int, int>, ladders: array<int, int>}  $layout
     * @return array{
     *     from: int, dice: int, moved: bool, landed: int, to: int,
     *     event: ?array{type: string, from: int, to: int},
     *     bounced: bool, finished: bool
     * }
     */
    public static function resolveMove(int $from, int $dice, array $layout, string $finishRule): array
    {
        $target = $from + $dice;
        $bounced = false;

        if ($target > self::SIZE) {
            switch ($finishRule) {
                case self::FINISH_BOUNCE:
                    $target = self::SIZE - ($target - self::SIZE); // bounce off 100
                    $bounced = true;
                    break;
                case self::FINISH_CAP:
                    $target = self::SIZE;
                    break;
                case self::FINISH_EXACT:
                default:
                    // Overshoot = the token stays put this turn.
                    return [
                        'from' => $from, 'dice' => $dice, 'moved' => false,
                        'landed' => $from, 'to' => $from, 'event' => null,
                        'bounced' => false, 'finished' => false,
                    ];
            }
        }

        $landed = $target;
        $to = $landed;
        $event = null;

        $snakes = (array) ($layout['snakes'] ?? []);
        $ladders = (array) ($layout['ladders'] ?? []);
        if (isset($snakes[$landed])) {
            $to = (int) $snakes[$landed];
            $event = ['type' => 'snake', 'from' => $landed, 'to' => $to];
        } elseif (isset($ladders[$landed])) {
            $to = (int) $ladders[$landed];
            $event = ['type' => 'ladder', 'from' => $landed, 'to' => $to];
        }

        return [
            'from' => $from, 'dice' => $dice, 'moved' => true,
            'landed' => $landed, 'to' => $to, 'event' => $event,
            'bounced' => $bounced, 'finished' => $to === self::SIZE,
        ];
    }

    /**
     * The dice range a correct answer earns under a movement policy. The bank
     * has two difficulty tiers (average / advanced), so Knowledge Dice maps
     * average → 1–3 and advanced → 4–6. Accuracy Movement is a fixed 4 tiles
     * (+1 bonus tile on every 3-correct streak, applied by the caller).
     *
     * @return array{min: int, max: int}
     */
    public static function diceRange(string $policy, ?string $difficulty): array
    {
        return match ($policy) {
            self::POLICY_KNOWLEDGE => $difficulty === 'advanced'
                ? ['min' => 4, 'max' => 6]
                : ['min' => 1, 'max' => 3],
            self::POLICY_ACCURACY => ['min' => 4, 'max' => 4],
            default => ['min' => 1, 'max' => 6],
        };
    }
}
