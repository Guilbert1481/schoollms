<?php

namespace App\Support;

/**
 * Deterministic OMR sheet geometry. Coordinates are NORMALISED (0..1) inside the
 * fiducial-bounded region — resolution independent, so the printed sheet and the
 * camera detector agree on where a bubble or write-in box sits.
 *
 * The sheet is split into labelled sections in a fixed canonical order (True or
 * False → Multiple Choice → Matching → Identification, with the remaining types
 * reserved for later). Items are numbered by that same order at snapshot time, so
 * the printed numbers ascend down the page and grading (which maps by item number)
 * stays correct. Bump VERSION whenever the geometry or numbering changes; the
 * version travels in the QR.
 */
class OmrLayout
{
    public const VERSION = 'v2';

    /**
     * Canonical section metadata, in print/number order. `rank` drives both the
     * section order and the item numbering; `kind` splits bubble items from
     * write-in items; `options` is the bubble count per item; `cols` is how many
     * items sit across one row. Types flagged 'later' have no OMR rendering yet.
     */
    public const SECTIONS = [
        'true_false' => ['rank' => 1, 'kind' => 'bubble', 'title' => 'TRUE OR FALSE', 'options' => 2, 'cols' => 5],
        'mtf' => ['rank' => 2, 'kind' => 'write', 'title' => 'MODIFIED TRUE OR FALSE', 'options' => 0, 'cols' => 1],
        'multiple_choice' => ['rank' => 3, 'kind' => 'bubble', 'title' => 'MULTIPLE CHOICE', 'options' => 5, 'cols' => 4],
        'matching' => ['rank' => 4, 'kind' => 'write', 'title' => 'MATCHING TYPE', 'options' => 0, 'cols' => 2],
        'fib' => ['rank' => 5, 'kind' => 'write', 'title' => 'FILL IN THE BLANK', 'options' => 0, 'cols' => 2],
        'identification' => ['rank' => 6, 'kind' => 'write', 'title' => 'IDENTIFICATION', 'options' => 0, 'cols' => 2],
        'enumeration' => ['rank' => 7, 'kind' => 'write', 'title' => 'ENUMERATION', 'options' => 0, 'cols' => 2],
        'essay' => ['rank' => 8, 'kind' => 'write', 'title' => 'ESSAY', 'options' => 0, 'cols' => 1],
    ];

    /** Canonical types the OMR currently renders and grades. */
    public const SUPPORTED = ['true_false', 'multiple_choice', 'matching', 'identification'];

    /** A section header reserves this many item-row heights. */
    private const HEADER_UNITS = 0.95;

    /** Blank space after a section, in item-row heights. */
    private const SECTION_GAP = 0.35;

    /** Physical height of one item row, in inches. */
    private const ROW_IN = 0.30;

    /** Fold the many raw question_type spellings into one canonical key. */
    public static function canonicalType(string $type): string
    {
        return match ($type) {
            'tf', 'true_false' => 'true_false',
            'mcq', 'multiple_choice' => 'multiple_choice',
            'match', 'matching' => 'matching',
            'id', 'identification' => 'identification',
            default => $type,
        };
    }

    /** Section rank for numbering/ordering; unknown types sink to the end. */
    public static function rank(string $type): int
    {
        return self::SECTIONS[self::canonicalType($type)]['rank'] ?? 99;
    }

    /** Whether this question type has an OMR rendering today. */
    public static function isSupported(string $type): bool
    {
        return in_array(self::canonicalType($type), self::SUPPORTED, true);
    }

    /**
     * Order a raw item list [{type, order, ...passthrough}] into the canonical
     * section sequence and assign each a 1-based item number. Passthrough keys
     * (e.g. a question model) are preserved; canonical/kind/options/section_title
     * are added. Both the frozen snapshot and the live sheet call this so their
     * numbering always matches.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function sequence(array $items): array
    {
        usort($items, fn ($a, $b) => [self::rank($a['type']), $a['order']] <=> [self::rank($b['type']), $b['order']]);

        $n = 0;
        foreach ($items as &$item) {
            $canon = self::canonicalType($item['type']);
            $meta = self::SECTIONS[$canon] ?? ['kind' => 'write', 'options' => 0, 'title' => strtoupper($canon)];
            $item['canonical'] = $canon;
            $item['kind'] = $meta['kind'];
            $item['options'] = $meta['options'];
            $item['section_title'] = $meta['title'];
            $item['n'] = ++$n;
        }
        unset($item);

        return $items;
    }

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

    /**
     * Full region layout for a sequenced item list: section header positions,
     * A–E bubbles, write-in boxes, and alternating shade bands, all normalised to
     * the same fiducial region, plus a suggested print height.
     *
     * @param  array<int, array<string, mixed>>  $items  output of sequence()
     * @return array{
     *   headers: array<int, array{title:string,y:float}>,
     *   bubbles: array<int, array{n:int,num:array{x:float,y:float},options:array<int,array{label:string,display:string,x:float,y:float}>}>,
     *   writes: array<int, array{n:int,type:string,num:array{x:float,y:float},box:array{x:float,y:float,w:float,h:float}}>,
     *   bands: array<int, array{y:float,h:float}>,
     *   region_height_in: float
     * }
     */
    public static function regions(array $items): array
    {
        $byType = [];
        foreach ($items as $item) {
            $byType[$item['canonical']][] = $item;
        }

        $plan = [];
        $units = 0.0;
        foreach (self::SECTIONS as $canon => $meta) {
            if (empty($byType[$canon])) {
                continue;
            }
            $list = $byType[$canon];
            $cols = max(1, $meta['cols']);
            $rows = (int) ceil(count($list) / $cols);
            $headerAt = $units;
            $units += self::HEADER_UNITS;
            $startAt = $units;
            $units += $rows + self::SECTION_GAP;

            $plan[] = compact('meta', 'list', 'cols', 'rows', 'headerAt', 'startAt');
        }

        $units = max(1.0, $units);
        $unit = 1.0 / $units;

        $headers = [];
        $bubbles = [];
        $writes = [];
        $bands = [];

        foreach ($plan as $p) {
            $headers[] = [
                'title' => $p['meta']['title'],
                'y' => round(($p['headerAt'] + self::HEADER_UNITS * 0.55) * $unit, 5),
            ];

            $cols = $p['cols'];
            $rows = $p['rows'];
            $colWidth = 1.0 / $cols;
            $isBubble = $p['meta']['kind'] === 'bubble';

            foreach ($p['list'] as $idx => $item) {
                $col = intdiv($idx, $rows); // column-major fill, like a scantron
                $row = $idx % $rows;
                $cy = round(($p['startAt'] + $row + 0.5) * $unit, 5);

                if ($isBubble) {
                    $opt = max(1, (int) $item['options']);
                    $numPad = 0.30 * $colWidth;
                    $span = $colWidth - $numPad - 0.05 * $colWidth;
                    $gap = $span / $opt;
                    $x0 = $col * $colWidth + $numPad;
                    $displays = $item['canonical'] === 'true_false' ? ['T', 'F'] : range('A', 'Z');

                    $options = [];
                    for ($j = 0; $j < $opt; $j++) {
                        $options[] = [
                            'label' => chr(65 + $j),
                            'display' => $displays[$j] ?? chr(65 + $j),
                            'x' => round($x0 + ($j + 0.5) * $gap, 5),
                            'y' => $cy,
                        ];
                    }

                    $bubbles[] = [
                        'n' => $item['n'],
                        'num' => ['x' => round($col * $colWidth + 0.04 * $colWidth, 5), 'y' => $cy],
                        'options' => $options,
                    ];
                } else {
                    $numPad = 0.09 * $colWidth;

                    $writes[] = [
                        'n' => $item['n'],
                        'type' => $item['canonical'],
                        'num' => ['x' => round($col * $colWidth + 0.02 * $colWidth, 5), 'y' => $cy],
                        'box' => [
                            'x' => round($col * $colWidth + $numPad, 5),
                            'y' => round($cy - $unit * 0.30, 5),
                            'w' => round($colWidth - $numPad - 0.04 * $colWidth, 5),
                            'h' => round($unit * 0.60, 5),
                        ],
                    ];
                }
            }

            if ($isBubble) {
                for ($r = 1; $r < $rows; $r += 2) {
                    $bands[] = ['y' => round(($p['startAt'] + $r) * $unit, 5), 'h' => round($unit, 5)];
                }
            }
        }

        return [
            'headers' => $headers,
            'bubbles' => $bubbles,
            'writes' => $writes,
            'bands' => $bands,
            'region_height_in' => round(min(9.5, max(2.2, $units * self::ROW_IN)), 3),
        ];
    }
}
