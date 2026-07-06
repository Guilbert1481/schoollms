{{-- resources/views/components/charts/radial-progress.blade.php
     Reusable ring progress indicator.
     Usage: <x-charts.radial-progress :percent="82" color="indigo" /> --}}

@props([
    'percent' => 0,
    'color'   => 'indigo',
    'size'    => 'w-16 h-16',   // tailwind sizing classes
])

@php
    $palette = [
        'violet'  => '#7c3aed', 'indigo' => '#4f46e5', 'blue' => '#2563eb',
        'sky'     => '#0284c7', 'amber'  => '#d97706', 'emerald' => '#059669',
        'rose'    => '#e11d48', 'red'    => '#dc2626', 'slate' => '#475569',
    ];
    $hex = $palette[$color] ?? $palette['indigo'];
    $pct = min(100, max(0, (float) $percent));
    $r   = 15.9155; // circumference = 100
@endphp

<svg viewBox="0 0 42 42" class="{{ $size }}" role="img" aria-label="{{ round($pct) }}% progress">
    <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="#f3f4f6" stroke-width="4.5"/>
    <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="{{ $hex }}" stroke-width="4.5"
            stroke-linecap="round" stroke-dasharray="{{ round($pct, 2) }} {{ round(100 - $pct, 2) }}"
            stroke-dashoffset="25"/>
    <text x="21" y="23.5" text-anchor="middle" font-size="9" font-weight="800" fill="#111827">{{ round($pct) }}%</text>
</svg>
