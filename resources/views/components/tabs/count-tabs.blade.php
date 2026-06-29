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

    // Label shown on the mobile hamburger trigger = the active tab.
    $activeTab   = collect($tabs)->firstWhere('active', true);
    $activeLabel = $activeTab['label'] ?? ($empty ?? 'Menu');
@endphp

<style>
    /* Mobile (< 768px): tabs collapse into a hamburger dropdown. Real @media
       queries + custom classes so this works regardless of the compiled build. */
    .ctabs-toggle { display: none; }
    @media (max-width: 767.98px) {
        .ctabs-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            width: 100%;
            padding: .5rem .75rem;
            border: 1px solid #e2e8f0;
            border-radius: .5rem;
            background: #fff;
            font-size: .875rem;
            font-weight: 700;
            color: #334155;
        }
        .ctabs-list {
            display: none;
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 4px);
            z-index: 40;
            flex-direction: column;
            gap: .25rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: .5rem;
            box-shadow: 0 12px 28px -8px rgba(15, 23, 42, .25);
            padding: .375rem;
        }
        .ctabs-list.is-open { display: flex; }
        .ctabs-list > a { border-radius: .375rem; margin-bottom: 0; }
    }
    @media (min-width: 768px) {
        .ctabs-list { display: flex !important; } /* horizontal, unchanged */
    }
</style>

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    {{-- Mobile-only hamburger trigger (hidden on desktop). --}}
    <button type="button" class="ctabs-toggle" @click="open = ! open">
        <span class="inline-flex items-center gap-2 truncate">
            <i data-lucide="menu" class="h-4 w-4 shrink-0 text-indigo-600"></i>
            <span class="truncate">{{ $activeLabel }}</span>
        </span>
        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400"></i>
    </button>

    {{-- Tab list: horizontal bar on desktop, dropdown on mobile. --}}
    <div class="ctabs-list flex flex-wrap gap-2 border-b border-slate-200" :class="{ 'is-open': open }">
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
</div>
