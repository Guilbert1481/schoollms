<?php

namespace App\Support;

/**
 * Deterministic OMR sheet geometry. Coordinates are NORMALISED (0..1) inside the
 * fiducial-bounded region — resolution independent, so the printed sheet, and the
 * camera detector, all agree on where a bubble or write-in box sits.
 *
 * A sheet has ONE region with the A–E bubbles at the top and the write-in
 * (identification / matching) boxes below, so a single deskew locates both.
 * Bump VERSION whenever the geometry changes; the version travels in the QR.
 */
class OmrLayout
{
    public const VERSION = 'v1';

    /** Max rows in a bubble column before wrapping to the next column. */
    private const MAX_ROWS = 25;

    /**
     * Four registration markers at the corners of the region. The detector maps
     * these to the unit square before sampling.
     *
     * @return array<int, array{x:float,y:float}>
     */
    public static function fiducials(): array
    {
        return [
            ['x' => 0.0, 'y' => 0.0],
            ['x' => 1.0, 'y' => 0.0],
            ['x' => 0.0, 'y' => 1.0],
            ['x' => 1.0, 'y' => 1.0],
        ];
    }

    /** Rows per bubble column for the given item count. */
    public static function rows(int $itemCount): int
    {
        $itemCount = max(1, $itemCount);
        $columns = (int) max(1, (int) ceil($itemCount / self::MAX_ROWS));

        return (int) max(1, (int) ceil($itemCount / $columns));
    }

    /**
     * Full region layout: A–E bubbles (top) + write-in boxes (bottom), all
     * normalised to the same region. Also returns a suggested print height so
     * the sheet stays on one page.
     *
     * @return array{
     *   bubbles: array<int, array{n:int,num:array{x:float,y:float},options:array<int,array{label:string,x:float,y:float}>}>,
     *   writes: array<int, array{idx:int,num:array{x:float,y:float},box:array{x:float,y:float,w:float,h:float}}>,
     *   region_height_in: float
     * }
     */
    public static function regions(int $bubbleCount, int $writeCount, int $optionCount = 5): array
    {
        $bubbleCount = max(0, $bubbleCount);
        $writeCount = max(0, $writeCount);
        $optionCount = max(1, min($optionCount, 26));

        $bubbleRows = $bubbleCount > 0 ? self::rows($bubbleCount) : 0;
        $gap = ($bubbleCount > 0 && $writeCount > 0) ? 1 : 0;
        $totalUnits = max(1, $bubbleRows + $gap + $writeCount);
        $unit = 1.0 / $totalUnits;

        $bubbles = [];
        if ($bubbleCount > 0) {
            $columns = (int) max(1, (int) ceil($bubbleCount / self::MAX_ROWS));
            $colWidth = 1.0 / $columns;
            $numPad = 0.28 * $colWidth;
            $bubbleSpan = $colWidth - $numPad - 0.06 * $colWidth;
            $bubbleGap = $bubbleSpan / max(1, $optionCount);

            for ($i = 0; $i < $bubbleCount; $i++) {
                $col = intdiv($i, $bubbleRows);
                $row = $i % $bubbleRows;
                $x0 = $col * $colWidth + $numPad;
                $y = round(($row + 0.5) * $unit, 5);

                $options = [];
                for ($j = 0; $j < $optionCount; $j++) {
                    $options[] = [
                        'label' => chr(65 + $j),
                        'x' => round($x0 + ($j + 0.5) * $bubbleGap, 5),
                        'y' => $y,
                    ];
                }

                $bubbles[] = ['n' => $i + 1, 'num' => ['x' => round($x0, 5), 'y' => $y], 'options' => $options];
            }
        }

        $writes = [];
        $writeStart = $bubbleRows + $gap;
        for ($i = 0; $i < $writeCount; $i++) {
            $cy = round(($writeStart + $i + 0.5) * $unit, 5);
            $writes[] = [
                'idx' => $i,
                'num' => ['x' => 0.05, 'y' => $cy],
                'box' => [
                    'x' => 0.12,
                    'y' => round($cy - $unit * 0.32, 5),
                    'w' => 0.80,
                    'h' => round($unit * 0.64, 5),
                ],
            ];
        }

        return [
            'bubbles' => $bubbles,
            'writes' => $writes,
            'region_height_in' => round(min(9.0, max(2.2, $totalUnits * 0.32)), 3),
        ];
    }
}
