{{-- resources/views/components/charts/bar-list.blade.php
     Reusable horizontal bar list (e.g. per-subject performance).
     Usage: <x-charts.bar-list :rows="[['label' => 'Math', 'value' => 95], ...]" :max="100" color="blue" /> --}}

@props([
    'rows'  => [],       // [['label'=>, 'value'=>, 'color'=> optional palette key], ...]
    'max'   => 100,
    'color' => 'blue',
    'empty' => 'No data to chart yet.',
])

@php
    $palette = [
        'violet'  => '#7c3aed', 'indigo' => '#4f46e5', 'blue' => '#2563eb',
        'sky'     => '#0284c7', 'amber'  => '#d97706', 'emerald' => '#059669',
        'rose'    => '#e11d48', 'red'    => '#dc2626', 'slate' => '#475569',
    ];
    $max = max(1, (float) $max);
@endphp

<div class="space-y-3">
    @forelse ($rows as $row)
        @php
            $hex = $palette[$row['color'] ?? $color] ?? $palette['blue'];
            $pct = min(100, max(0, (float) $row['value'] / $max * 100));
        @endphp
        <div class="flex items-center gap-3 text-sm">
            <span class="w-24 shrink-0 truncate text-gray-500 dark:text-gray-400">{{ $row['label'] }}</span>
            <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                <div class="h-full rounded-full" style="width: {{ $pct }}%; background: {{ $hex }}"></div>
            </div>
            <span class="w-10 shrink-0 text-right font-bold text-gray-800 dark:text-gray-100">
                {{ rtrim(rtrim(number_format((float) $row['value'], 2, '.', ''), '0'), '.') }}
            </span>
        </div>
    @empty
        <p class="text-sm text-gray-400">{{ $empty }}</p>
    @endforelse
</div>
