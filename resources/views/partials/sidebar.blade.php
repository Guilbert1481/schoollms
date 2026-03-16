{{-- views/partials/sidebar.blade.php --}}

@php
    $role = strtolower(auth()->user()->role);
    $allowedSections = config("sidebar.roles.$role", []);
    $menu = config('sidebar.menu');

    $activeSection = null;
    foreach ($allowedSections as $sectionKey) {
        $section = $menu[$sectionKey] ?? null;
        if ($section && isset($section['children'])) {
            foreach ($section['children'] as $child) {
                if (isset($child['active']) && request()->routeIs($child['active'])) {
                    $activeSection = $sectionKey;
                }
            }
        }
    }

    // fallback for classes if not passed (should not happen)
    $hoverClass = $hoverClass ?? 'hover:bg-slate-100';
    $textClass = $textClass ?? 'text-slate-600';
@endphp

<nav x-data="{ open: '{{ $activeSection }}' }" class="px-4 py-6 space-y-2 text-sm">
    {{-- Dashboard --}}
    <a href="{{ route('dashboard') }}"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200
       {{ request()->routeIs('dashboard')
            ? $textClass . ' bg-indigo-600/20 font-bold'
            : $textClass . ' ' . $hoverClass }}">
        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
        Dashboard
    </a>

    @foreach($allowedSections as $sectionKey)
        @php $section = $menu[$sectionKey] ?? null; @endphp

        @if($section)
            {{-- Dropdown Sections --}}
            @if(isset($section['children']))
                <div>
                    <button
                        @click="open === '{{ $sectionKey }}' ? open = null : open = '{{ $sectionKey }}'"
                        class="w-full flex items-center justify-between p-3 rounded-xl transition-all duration-200 {{ $hoverClass }} {{ $textClass }}">

                        <div class="flex items-center gap-3">
                            <i data-lucide="{{ $section['icon'] }}" class="w-5 h-5"></i>
                            {{ $section['label'] }}
                        </div>

                        <i data-lucide="chevron-down"
                           :class="open === '{{ $sectionKey }}' ? 'rotate-180' : ''"
                           class="w-4 h-4 transition-transform duration-300"></i>
                    </button>

                    <div x-show="open === '{{ $sectionKey }}'"
                        x-collapse
                        class="ml-14 md:ml-16 mt-2 space-y-1 border-l border-slate-200 pl-5">

                        @php
                            $visibleChildren = collect($section['children'] ?? [])
                                ->filter(function ($child) use ($role) {
                                    return !isset($child['roles']) ||
                                        in_array($role, array_map('strtolower', $child['roles']));
                                })
                                ->values();
                        @endphp

                        @if($visibleChildren->isNotEmpty())
                            @foreach($visibleChildren as $child)
                                <a href="{{ $child['route'] === '#' ? '#' : route($child['route']) }}"
                                class="flex items-center gap-2 py-2 text-sm transition
                                        {{ isset($child['active']) && request()->routeIs($child['active'])
                                            ? $textClass . ' font-semibold'
                                            : $textClass . ' ' . $hoverClass }}">
                                    <i data-lucide="{{ $child['icon'] ?? 'circle' }}" class="w-4 h-4"></i>
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        @endif

                    </div>
                </div>
            @else
                {{-- Single Links (Settings, etc.) --}}
                @if(isset($section['method']) && $section['method'] === 'post')
                    {{-- Logout (POST) --}}
                    <form method="POST" action="{{ route($section['route']) }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-3 p-3 rounded-xl transition-all duration-200 text-left {{ $hoverClass }}">
                            <i data-lucide="{{ $section['icon'] }}" class="w-5 h-5"></i>
                            {{ $section['label'] }}
                        </button>
                    </form>
                @else
                    <a href="{{ route($section['route']) }}"
                       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200 {{ $hoverClass }} {{ $textClass }}">
                        <i data-lucide="{{ $section['icon'] }}" class="w-5 h-5"></i>
                        {{ $section['label'] }}
                    </a>
                @endif
            @endif
        @endif
    @endforeach
</nav>