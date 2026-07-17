<?php

namespace App\Support;

/**
 * Deterministic OMR sheet geometry. Coordinates are NORMALISED (0..1) inside the
 * fiducial-bounded answer region — resolution independent, so the printed sheet,
 * the frozen snapshot, and (Phase 2b) the camera detector all agree on where a
 * bubble sits. Bump VERSION whenever the geometry changes; the version travels
 * in the QR so an old sheet is always graded against the layout it was printed
 * with.
 */
class OmrLayout
{
    public const VERSION = 'v1';

    /** Items per column before wrapping to the next column. */
    private const ROWS_PER_COL = 25;

    /**
     * Four registration markers at the corners of the answer region. The
     * detector maps these to the unit square before sampling bubbles.
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

    /**
     * Per-item bubble coordinates for the given item/option counts.
     *
     * @return array<int, array{n:int,options:array<int,array{label:string,x:float,y:float}>}>
     */
    public static function map(int $itemCount, int $optionCount = 5): array
    {
        $itemCount = max(0, $itemCount);
        $optionCount = max(1, min($optionCount, 26));

        $columns = (int) max(1, ceil($itemCount / self::ROWS_PER_COL));
        $colWidth = 1.0 / $columns;

        // Bubble spacing inside a column: leave room on the left for the number.
        $numPad = 0.28 * $colWidth;                 // left gutter for the item number
        $bubbleSpan = $colWidth - $numPad - 0.06 * $colWidth;
        $bubbleGap = $bubbleSpan / max(1, $optionCount);

        $rowHeight = 1.0 / self::ROWS_PER_COL;

        $items = [];
        for ($i = 0; $i < $itemCount; $i++) {
            $col = intdiv($i, self::ROWS_PER_COL);
            $row = $i % self::ROWS_PER_COL;

            $x0 = $col * $colWidth + $numPad;
            $y = round($row * $rowHeight + $rowHeight / 2, 5);

            $options = [];
            for ($j = 0; $j < $optionCount; $j++) {
                $options[] = [
                    'label' => chr(65 + $j),
                    'x' => round($x0 + ($j + 0.5) * $bubbleGap, 5),
                    'y' => $y,
                ];
            }

            $items[] = ['n' => $i + 1, 'options' => $options];
        }

        return $items;
    }
}
