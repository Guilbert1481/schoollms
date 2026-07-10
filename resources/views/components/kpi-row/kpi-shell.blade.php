{{-- resources/views/components/kpi-row/kpi-shell.blade.php --}}

@props([
    'title'    => '',
    'icon'     => 'activity',
    'accent'   => null,   // optional per-card colour key (violet|sky|amber|rose|…)
    'subtitle' => null,   // optional small caption under the value
    'delta'    => null,   // optional trend caption, e.g. "2.6% vs last quarter"
    'deltaUp'  => null,   // true = green up-arrow, false = red down-arrow, null = neutral gray
])

@php

    $identity = app(\App\Services\DashboardIdentityService::class)
        ->forUser(auth()->user());

    $kpi = $identity['kpi'] ?? [];

    $cardStyle = $kpi['card_style'] ?? 'soft';
    $borderStyle = $kpi['border_style'] ?? 'subtle';
    $backgroundTint = $kpi['background_tint'] ?? 'neutral';
    $accentColor = $kpi['accent_color'] ?? 'indigo';


    // Card Style
    $cardClasses = match ($cardStyle) {
        'glass' => 'bg-white/10 backdrop-blur-xl',
        'flat'  => 'bg-white dark:bg-gray-800',
        default => "bg-{$accentColor}-50 dark:bg-{$accentColor}-900/10 shadow-sm",
    };

    // Border Style
    $borderClasses = match ($borderStyle) {
        'bold'   => 'border-2 border-gray-300 dark:border-gray-600',
        'none'   => 'border-0',
        default  => 'border border-gray-100 dark:border-gray-700',
    };

    // Background Tint
    $tintClasses = match ($backgroundTint) {
        'brand' => 'ring-1 ring-blue-200 dark:ring-blue-800',
        'dark'  => 'bg-gray-900 text-white',
        default => '',
    };

    // Optional per-card icon colour. Inline hex keeps it build-independent
    // (dynamic bg-{color}-100 utilities may be purged from the compiled CSS).
    $palette = [
        'violet'  => ['bg' => '#ede9fe', 'fg' => '#7c3aed'],
        'indigo'  => ['bg' => '#e0e7ff', 'fg' => '#4f46e5'],
        'blue'    => ['bg' => '#dbeafe', 'fg' => '#2563eb'],
        'sky'     => ['bg' => '#e0f2fe', 'fg' => '#0284c7'],
        'amber'   => ['bg' => '#fef3c7', 'fg' => '#d97706'],
        'emerald' => ['bg' => '#d1fae5', 'fg' => '#059669'],
        'rose'    => ['bg' => '#ffe4e6', 'fg' => '#e11d48'],
        'red'     => ['bg' => '#fee2e2', 'fg' => '#dc2626'],
        'slate'   => ['bg' => '#f1f5f9', 'fg' => '#475569'],
    ];
    $iconStyle = ($accent && isset($palette[$accent]))
        ? 'background-color:'.$palette[$accent]['bg'].';color:'.$palette[$accent]['fg'].';'
        : null;
@endphp


<div class="{{ $cardClasses }} {{ $borderClasses }} {{ $tintClasses }} p-6 rounded-2xl transition hover:shadow-lg">


    <div class="flex justify-between items-start">

        <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                {{ $title }}
            </p>
            <div class="text-3xl font-black mt-2 text-gray-900 dark:text-gray">
                {{ $slot }}
            </div>
            @if($subtitle)
                <p class="mt-1 text-xs font-semibold text-gray-400">{{ $subtitle }}</p>
            @endif
            @if($delta)
                @php
                    // Inline hex (purge-safe): emerald-600 up, red-600 down, gray-400 neutral.
                    $deltaColor = $deltaUp === true ? '#059669' : ($deltaUp === false ? '#dc2626' : '#9ca3af');
                    $deltaArrow = $deltaUp === true ? '↑' : ($deltaUp === false ? '↓' : '');
                @endphp
                <p class="mt-1 text-xs font-bold" style="color: {{ $deltaColor }};">{{ $deltaArrow }} {{ $delta }}</p>
            @endif
        </div>

        @if($iconStyle)
            <div class="p-3 rounded-xl" style="{{ $iconStyle }}">
                <i data-lucide="{{ $icon }}" class="w-6 h-6"></i>
            </div>
        @else
            <div class="p-3 rounded-xl bg-{{ $accentColor }}-100 text-{{ $accentColor }}-600 dark:bg-{{ $accentColor }}-900/30 dark:text-{{ $accentColor }}-400">
                <i data-lucide="{{ $icon }}" class="w-6 h-6"></i>
            </div>
        @endif

    </div>
</div>
