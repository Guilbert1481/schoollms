{{--
    Reusable tab bar with an optional red count superscript on each tab —
    e.g. education-level tabs that show how many students need attention.

    Usage:
        @php
            $tabs = [
                ['label' => 'Basic Ed', 'url' => route('x', ['level' => 1]), 'count' => 4, 'active' => true],
                ['label' => 'Undergraduate', 'url' => route('x', ['level' => 2]), 'count' => 0, 'active' => false],
            ];
        @endphp
        <x-tabs.count-tabs :tabs="$tabs" empty="No levels offered yet." />

    Each tab item: ['label', 'url', 'count' (int, 0 = no badge), 'active' (bool)].
    Optional `accent` prop: 'indigo' (default) or 'emerald'.
--}}
@props([
    'tabs' => [],
    'accent' => 'indigo',
    'empty' => null,
])

@php
    // Full literal class strings so Tailwind never purges them.
    $accents = [
        'indigo'  => ['active' => 'border-indigo-600 text-indigo-700 bg-indigo-50',   'hover' => 'hover:text-indigo-700'],
        'emerald' => ['active' => 'border-emerald-600 text-emerald-700 bg-emerald-50', 'hover' => 'hover:text-emerald-700'],
    ];
    $a = $accents[$accent] ?? $accents['indigo'];
@endphp

<div class="flex flex-wrap gap-2 border-b border-slate-200">
    @forelse($tabs as $tab)
        <a href="{{ $tab['url'] ?? '#' }}"
           class="relative px-4 py-2 text-sm font-semibold rounded-t-lg border-b-2 -mb-px
                  {{ ($tab['active'] ?? false)
                        ? $a['active']
                        : 'border-transparent text-slate-600 '.$a['hover'].' hover:bg-slate-50' }}">
            {{ $tab['label'] ?? '' }}
            @if((int) ($tab['count'] ?? 0) > 0)
                <sup class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold leading-none">
                    {{ $tab['count'] }}
                </sup>
            @endif
        </a>
    @empty
        @if($empty)
            <span class="px-4 py-2 text-sm text-slate-400">{{ $empty }}</span>
        @endif
    @endforelse
</div>
