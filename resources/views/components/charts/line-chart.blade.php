{{-- resources/views/components/charts/line-chart.blade.php
     Reusable dependency-free SVG line chart.
     Usage: <x-charts.line-chart :labels="['Jan','Feb']" :values="[86,92]" color="blue" /> --}}

@props([
    'labels' => [],
    'values' => [],
    'color'  => 'blue',    // kpi-shell palette key
    'yMin'   => null,
    'yMax'   => null,
    'empty'  => 'No data to chart yet.',
])

@php
    $palette = [
        'violet'  => '#7c3aed', 'indigo' => '#4f46e5', 'blue' => '#2563eb',
        'sky'     => '#0284c7', 'amber'  => '#d97706', 'emerald' => '#059669',
        'rose'    => '#e11d48', 'red'    => '#dc2626', 'slate' => '#475569',
    ];
    $hex = $palette[$color] ?? $palette['blue'];

    $values = array_values(array_map('floatval', $values));
    $labels = array_values($labels);
    $n      = count($values);

    $w = 480; $h = 240; $padL = 40; $padR = 16; $padT = 20; $padB = 30;

    if ($n > 0) {
        $lo = $yMin ?? min(min($values) - 5, 0);
        $hi = $yMax ?? max($values) + 5;
        if ($hi <= $lo) { $hi = $lo + 1; }

        $plotW = $w - $padL - $padR;
        $plotH = $h - $padT - $padB;

        $pts = [];
        foreach ($values as $i => $v) {
            $x = $padL + ($n > 1 ? $plotW * $i / ($n - 1) : $plotW / 2);
            $y = $padT + $plotH * (1 - ($v - $lo) / ($hi - $lo));
            $pts[] = [round($x, 1), round($y, 1), $v, $labels[$i] ?? ''];
        }
        $polyline = implode(' ', array_map(fn ($p) => $p[0].','.$p[1], $pts));

        $gridLines = [];
        foreach ([0, 0.5, 1] as $f) {
            $gridLines[] = [
                'y'     => round($padT + $plotH * (1 - $f), 1),
                'label' => round($lo + ($hi - $lo) * $f),
            ];
        }
    }
@endphp

@if ($n < 2)
    <div class="flex items-center justify-center h-40 text-sm text-gray-400">{{ $empty }}</div>
@else
    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full h-auto" role="img" aria-label="Line chart">
        @foreach ($gridLines as $g)
            <line x1="{{ $padL }}" y1="{{ $g['y'] }}" x2="{{ $w - $padR }}" y2="{{ $g['y'] }}"
                  stroke="#e5e7eb" stroke-width="1" stroke-dasharray="4 4"/>
            <text x="{{ $padL - 8 }}" y="{{ $g['y'] + 4 }}" text-anchor="end"
                  font-size="11" fill="#9ca3af">{{ $g['label'] }}</text>
        @endforeach

        <polyline points="{{ $polyline }}" fill="none" stroke="{{ $hex }}" stroke-width="2.5"
                  stroke-linecap="round" stroke-linejoin="round"/>

        @foreach ($pts as $p)
            <circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="4" fill="{{ $hex }}" stroke="#fff" stroke-width="2"/>
            <text x="{{ $p[0] }}" y="{{ $p[1] - 10 }}" text-anchor="middle"
                  font-size="11" font-weight="700" fill="#374151">{{ rtrim(rtrim(number_format($p[2], 2, '.', ''), '0'), '.') }}</text>
            <text x="{{ $p[0] }}" y="{{ $h - 8 }}" text-anchor="middle"
                  font-size="11" fill="#9ca3af">{{ $p[3] }}</text>
        @endforeach
    </svg>
@endif
