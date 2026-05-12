views/components/kpi-row/stat-shell.blade.php


@props([
    'title',
    'id',
    'type' => 'line',
    'color' => 'indigo'
])

@php
    $identity = app(\App\Services\DashboardIdentityService::class)->forUser(auth()->user());
    $kpi = $identity['kpi'] ?? [];

    $cardStyle = $kpi['card_style'] ?? 'soft';
    $borderStyle = $kpi['border_style'] ?? 'subtle';
    $accentColor = $color ?? ($kpi['accent_color'] ?? 'indigo');

    $cardClasses = match ($cardStyle) {
        'glass' => 'bg-white/10 backdrop-blur-xl border-white/20 shadow-xl',
        'flat'  => 'bg-white dark:bg-slate-800 shadow-sm',
        default => "bg-white dark:bg-slate-900 shadow-sm",
    };

    $borderClasses = match ($borderStyle) {
        'bold'   => 'border-2 border-slate-200 dark:border-slate-700',
        'none'   => 'border-0',
        default  => 'border border-slate-100 dark:border-slate-800',
    };
@endphp

<div class="{{ $cardClasses }} {{ $borderClasses }} p-6 rounded-3xl transition-all duration-300 hover:shadow-md group">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">
                {{ $title }}
            </h2>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                Analytics Module • {{ ucfirst($type) }} View
            </p>
        </div>
        <div class="flex -space-x-1">
            <div class="w-2 h-2 rounded-full bg-{{ $accentColor }}-500 animate-pulse"></div>
            <div class="w-2 h-2 rounded-full bg-{{ $accentColor }}-500 opacity-20"></div>
        </div>
    </div>

    <div class="relative h-[280px] w-full">
        <canvas id="chart_{{ $id }}"></canvas>
    </div>

    <div class="mt-6 pt-4 border-t border-slate-50 dark:border-slate-800 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">Live System Data</span>
        </div>
        <button class="text-[10px] font-black text-{{ $accentColor }}-600 hover:text-{{ $accentColor }}-700 transition uppercase tracking-widest">
            Details →
        </button>
    </div>
</div>