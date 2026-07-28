{{-- views/partials/sidebar.blade.php --}}

@php
    $user = auth()->user();
    $role = strtolower($user->role ?? '');
    // normalize "Course Architect" / "course-architect" → "course_architect"
    $role = str_replace(['-', ' '], '_', $role);

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

    $hoverClass = $hoverClass ?? 'hover:bg-slate-100';
    $textClass = $textClass ?? 'text-slate-600';

    // Basic-ed students see "Form 137" instead of "Transcript" (ADR-0006 /
    // Form 137 decisions). Memoised per request inside the service.
    $isBasicEdStudent = ($role === 'student' && $user)
        ? app(\App\Services\Dashboard\StudentDashboardService::class)->isBasicEd($user)
        : false;

    // Registrar → Transcript of Records: per-student grade-view visibility.
    // Hidden by default; the registrar grants per student on request. Applies to
    // every student (basic ed AND higher ed), not just the actively-enrolled — a
    // read-only lookup, a missing/blank flag means hidden.
    $studentGV = ($role === 'student' && $user)
        ? \App\Models\Student::where('user_id', $user->id)
            ->where('school_id', (int) ($user->school_id ?? 0))
            ->first(['show_grades', 'show_form137'])
        : null;
    $canSeeGrades = $role === 'student' ? (bool) ($studentGV->show_grades ?? false) : true;
    $canSeeForm137 = $role === 'student' ? (bool) ($studentGV->show_form137 ?? false) : true;

    // Modality Request is non-basic-ed only and only while the 2-week
    // post-enrollment window is open (same rule the controller enforces).
    $modalityItemVisible = ($role === 'student' && $user)
        ? (! $isBasicEdStudent
            && app(\App\Services\Dashboard\StudentDashboardService::class)->modalityWindowOpen($user))
        : true;
@endphp

<nav x-data="{ open: '{{ $activeSection }}' }" class="px-4 py-6 space-y-2 text-sm">
    {{-- Dashboard (skipped for superadmin — they use Platform Home) --}}
    @if($role !== 'superadmin')
    <a href="{{ route('dashboard') }}"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200
       {{ request()->routeIs('dashboard')
            ? $textClass . ' bg-indigo-600/20 font-bold'
            : $textClass . ' ' . $hoverClass }}">
        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
        Dashboard
    </a>
    @endif

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
                        class="ml-4 mt-2 space-y-1 border-l border-slate-200 pl-3">

                        @php
                            $visibleChildren = collect($section['children'] ?? [])
                                ->filter(function ($child) use ($role) {
                                    return !isset($child['roles']) ||
                                        in_array($role, array_map('strtolower', $child['roles']));
                                })
                                // Per-student grade-view toggles (Registrar →
                                // Transcript of Records): drop the item when off.
                                ->reject(function ($child) use ($role, $canSeeGrades, $canSeeForm137) {
                                    if ($role !== 'student') {
                                        return false;
                                    }
                                    return match ($child['route'] ?? '') {
                                        'student.report-card'      => ! $canSeeGrades,
                                        'student.transcript.index' => ! $canSeeForm137,
                                        default => false,
                                    };
                                })
                                // Modality Request: basic ed never sees it; others
                                // only while the 2-week request window is open.
                                ->reject(fn ($child) => ($child['route'] ?? '') === 'student.services.modality.index'
                                    && ! $modalityItemVisible)
                                ->values();
                        @endphp

                        @if($visibleChildren->isNotEmpty())
                            @foreach($visibleChildren as $child)

                                @if(isset($child['children']))
                                    {{-- Parent Item --}}
                                    <div class="mt-2">
                                        <div class="flex items-center gap-2 py-2 font-semibold {{ $textClass }}">
                                            <i data-lucide="{{ $child['icon'] ?? 'circle' }}" class="w-4 h-4"></i>
                                            {{ $child['label'] }}
                                        </div>

                                        <div class="ml-4 space-y-1">
                                            @foreach($child['children'] as $sub)
                                                <a href="{{ $sub['route'] === '#'
                                                    ? '#'
                                                    : route($sub['route'], $sub['params'] ?? []) }}"
                                                class="flex items-center gap-2 py-2 text-sm transition
                                                    {{ (isset($sub['active']) && request()->routeIs($sub['active']))
                                                        || (isset($sub['params']) && request()->is('school/system/dynamic/'.$sub['params']))
                                                        ? $textClass . ' font-semibold'
                                                        : $textClass . ' ' . $hoverClass }}">
                                                    <i data-lucide="{{ $sub['icon'] ?? 'circle' }}" class="w-4 h-4"></i>
                                                    {{ $sub['label'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>

                                @else
                                    {{-- Normal Item --}}
                                    <a href="{{ $child['route'] === '#'
                                        ? '#'
                                        : route($child['route'], $child['params'] ?? []) }}"
                                    class="flex items-center gap-2 py-2 text-sm transition
                                        {{ (isset($child['active']) && request()->routeIs($child['active']))
                                            || (isset($child['params']) && request()->is('school/system/dynamic/'.$child['params']))
                                            ? $textClass . ' font-semibold'
                                            : $textClass . ' ' . $hoverClass }}">
                                        <i data-lucide="{{ $child['icon'] ?? 'circle' }}" class="w-4 h-4"></i>
                                        {{ ($isBasicEdStudent && ($child['route'] ?? '') === 'student.transcript.index')
                                            ? 'Form 137'
                                            : $child['label'] }}
                                    </a>
                                @endif

                            @endforeach
                        @endif

                    </div>
                </div>
            @else
                {{-- Single Links (Settings, logout, etc.) --}}
                @php $sectionRoute = $section['route'] ?? null; @endphp

                @if($sectionRoute) {{-- Only render if a route is actually defined --}}
                    @if(isset($section['method']) && $section['method'] === 'post')
                        {{-- Logout logic --}}
                        <form method="POST" action="{{ route($sectionRoute) }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center gap-3 p-3 rounded-xl transition-all duration-200 text-left {{ $hoverClass }}">
                                <i data-lucide="{{ $section['icon'] }}" class="w-5 h-5"></i>
                                {{ $section['label'] }}
                            </button>
                        </form>
                    @else
                        <a href="{{ $sectionRoute === '#' ? '#' : route($sectionRoute) }}"
                        class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200 {{ $hoverClass }} {{ $textClass }}">
                            <i data-lucide="{{ $section['icon'] }}" class="w-5 h-5"></i>
                            {{ $section['label'] }}
                        </a>
                    @endif
                @endif
            @endif
        @endif
    @endforeach
</nav>