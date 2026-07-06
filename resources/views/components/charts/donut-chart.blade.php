{{-- resources/views/components/charts/donut-chart.blade.php
     Reusable dependency-free SVG donut chart with legend.
     Usage:
       <x-charts.donut-chart
           :segments="[['label' => 'Paid', 'value' => 4, 'color' => 'emerald'], ...]"
           center-value="24" center-label="Total" /> --}}

@props([
    'segments'    => [],   // [['label'=>, 'value'=>, 'color'=> palette key], ...]
    'centerValue' => null,
    'centerLabel' => null,
    'empty'       => 'Nothing to show yet.',
])

@php
    $palette = [
        'violet'  => '#7c3aed', 'indigo' => '#4f46e5', 'blue' => '#2563eb',
        'sky'     => '#0284c7', 'amber'  => '#d97706', 'emerald' => '#059669',
        'rose'    => '#e11d48', 'red'    => '#dc2626', 'slate' => '#475569',
        'gray'    => '#d1d5db',
    ];

    $segments = array_values(array_filter($segments, fn ($s) => ($s['value'] ?? 0) > 0));
    $total    = array_sum(array_column($segments, 'value'));

    // Circle with pathLength-equivalent circumference of 100 (r = 100 / 2π).
    $r = 15.9155;
    $running = 0;
    foreach ($segments as $i => $s) {
        $pct = $total > 0 ? $s['value'] / $total * 100 : 0;
        $segments[$i]['pct']    = $pct;
        $segments[$i]['dash']   = round($pct, 3).' '.round(100 - $pct, 3);
        // 25 rotates the start to 12 o'clock; subtract what came before.
        $segments[$i]['offset'] = round(25 - $running, 3);
        $segments[$i]['hex']    = $palette[$s['color'] ?? 'slate'] ?? $palette['slate'];
        $running += $pct;
    }
@endphp

<div class="flex items-center gap-5">
    <svg viewBox="0 0 42 42" class="w-36 h-36 shrink-0" role="img" aria-label="Donut chart">
        <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="#f3f4f6" stroke-width="5"/>
        @foreach ($segments as $s)
            <circle cx="21" cy="21" r="{{ $r }}" fill="none"
                    stroke="{{ $s['hex'] }}" stroke-width="5"
                    stroke-dasharray="{{ $s['dash'] }}" stroke-dashoffset="{{ $s['offset'] }}"/>
        @endforeach
        <text x="21" y="21" text-anchor="middle" font-size="8" font-weight="800" fill="#111827">
            {{ $centerValue ?? $total }}
        </text>
        @if ($centerLabel)
            <text x="21" y="27" text-anchor="middle" font-size="3.2" fill="#9ca3af">{{ $centerLabel }}</text>
        @endif
    </svg>

    <div class="space-y-2 min-w-0">
        @forelse ($segments as $s)
            <div class="flex items-center gap-2 text-sm">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $s['hex'] }}"></span>
                <span class="text-gray-600 dark:text-gray-300 truncate">{{ $s['label'] }}</span>
                <span class="ml-auto font-bold text-gray-800 dark:text-gray-100 pl-2">
                    {{ $s['display'] ?? $s['value'] }} <span class="text-gray-400 font-medium">({{ round($s['pct']) }}%)</span>
                </span>
            </div>
        @empty
            <p class="text-sm text-gray-400">{{ $empty }}</p>
        @endforelse
    </div>
</div>
