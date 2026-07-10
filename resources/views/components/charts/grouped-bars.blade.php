{{-- resources/views/components/charts/grouped-bars.blade.php
     Reusable dependency-free SVG grouped (multi-series) vertical bar chart.
     Usage:
        <x-charts.grouped-bars
            :labels="['Aug','Sep']"
            :series="[
                ['label' => 'Revenue',  'color' => 'blue', 'values' => [1400000, 1600000]],
                ['label' => 'Expenses', 'color' => 'red',  'values' => [1100000, 1200000]],
            ]"
            empty="No data to chart yet." /> --}}

@props([
    'labels' => [],
    'series' => [],   // [['label' => string, 'color' => paletteKey, 'values' => [numeric,...]], ...]
    'empty'  => 'No data to chart yet.',
])

@php
    $palette = [
        'violet'  => '#7c3aed', 'indigo' => '#4f46e5', 'blue' => '#2563eb',
        'sky'     => '#0284c7', 'amber'  => '#d97706', 'emerald' => '#059669',
        'rose'    => '#e11d48', 'red'    => '#dc2626', 'slate' => '#475569',
    ];

    $labels = array_values($labels);
    $series = array_values(array_filter($series, fn ($s) => !empty($s['values'])));
    $n      = count($labels);
    $sCount = count($series);

    // Compact value labels above bars: 1.4M / 25K / 980.
    $compact = function ($v) {
        $v = (float) $v;
        if ($v >= 1_000_000) return rtrim(rtrim(number_format($v / 1_000_000, 1, '.', ''), '0'), '.').'M';
        if ($v >= 1_000)     return rtrim(rtrim(number_format($v / 1_000, 1, '.', ''), '0'), '.').'K';
        return rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    };

    $max = 0;
    foreach ($series as $s) {
        foreach ($s['values'] as $v) { $max = max($max, (float) $v); }
    }

    if ($n > 0 && $sCount > 0 && $max > 0) {
        $w = 480; $h = 240; $padL = 40; $padR = 8; $padT = 20; $padB = 30;
        $plotW = $w - $padL - $padR;
        $plotH = $h - $padT - $padB;
        $hi    = $max * 1.15; // headroom for the value labels

        $groupW = $plotW / $n;
        $gapIn  = 3;                                          // gap between bars in a group
        $barW   = max(4, min(28, ($groupW * 0.62 - $gapIn * ($sCount - 1)) / $sCount));

        $bars = [];
        foreach ($labels as $i => $label) {
            $clusterW = $barW * $sCount + $gapIn * ($sCount - 1);
            $x0 = $padL + $groupW * $i + ($groupW - $clusterW) / 2;
            foreach ($series as $j => $s) {
                $v  = (float) ($s['values'][$i] ?? 0);
                $bh = $hi > 0 ? $plotH * $v / $hi : 0;
                $bars[] = [
                    'x'   => round($x0 + $j * ($barW + $gapIn), 1),
                    'y'   => round($padT + $plotH - $bh, 1),
                    'h'   => round($bh, 1),
                    'hex' => $palette[$s['color'] ?? 'blue'] ?? $palette['blue'],
                    'lab' => $v > 0 ? $compact($v) : '',
                ];
            }
        }

        $gridLines = [];
        foreach ([0, 0.5, 1] as $f) {
            $gridLines[] = [
                'y'     => round($padT + $plotH * (1 - $f), 1),
                'label' => $compact($hi * $f),
            ];
        }
    }
@endphp

@if ($n === 0 || $sCount === 0 || $max <= 0)
    <div class="flex items-center justify-center h-40 text-sm text-gray-400">{{ $empty }}</div>
@else
    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-4 mb-2">
        @foreach ($series as $s)
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500">
                <span class="inline-block w-2.5 h-2.5 rounded-sm"
                      style="background-color: {{ $palette[$s['color'] ?? 'blue'] ?? $palette['blue'] }};"></span>
                {{ $s['label'] }}
            </span>
        @endforeach
    </div>

    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full h-auto" role="img" aria-label="Grouped bar chart">
        @foreach ($gridLines as $g)
            <line x1="{{ $padL }}" y1="{{ $g['y'] }}" x2="{{ $w - $padR }}" y2="{{ $g['y'] }}"
                  stroke="#e5e7eb" stroke-width="1" stroke-dasharray="4 4"/>
            <text x="{{ $padL - 8 }}" y="{{ $g['y'] + 4 }}" text-anchor="end"
                  font-size="10" fill="#9ca3af">{{ $g['label'] }}</text>
        @endforeach

        @foreach ($bars as $b)
            @if ($b['h'] > 0)
                <rect x="{{ $b['x'] }}" y="{{ $b['y'] }}" width="{{ $barW }}" height="{{ $b['h'] }}"
                      rx="2" fill="{{ $b['hex'] }}"/>
                @if ($b['lab'] !== '')
                    <text x="{{ $b['x'] + $barW / 2 }}" y="{{ $b['y'] - 4 }}" text-anchor="middle"
                          font-size="9" font-weight="700" fill="#374151">{{ $b['lab'] }}</text>
                @endif
            @endif
        @endforeach

        @foreach ($labels as $i => $label)
            <text x="{{ $padL + $groupW * $i + $groupW / 2 }}" y="{{ $h - 8 }}" text-anchor="middle"
                  font-size="10" fill="#9ca3af">{{ $label }}</text>
        @endforeach
    </svg>
@endif
