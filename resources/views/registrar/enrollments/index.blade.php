@extends('layouts.app')

@section('content')
<div class="w-full space-y-4">

    <h1 class="text-xl font-extrabold text-slate-800">Validate Enrollment</h1>

    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Education-level tabs — only the levels offered in the structure tree.
         The red superscript shows how many students await validation. --}}
    @php
        $levelTabs = collect($offeredRoots)->map(fn ($lvl) => [
            'label'  => $lvl->name,
            'url'    => route('registrar.enrollments.index', ['level' => $lvl->id]),
            'count'  => $counts[$lvl->id] ?? 0,
            'active' => $activeLevelId === (int) $lvl->id,
        ])->all();
    @endphp
    <x-tabs.count-tabs :tabs="$levelTabs" empty="No education levels are offered yet." />

    {{-- Title (left) + level-specific filters (right) --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        @php
            $statusSubtitle = $statusFilter === 'all'
                ? 'pre-enrollment pipeline'
                : strtolower($statusOptions[$statusFilter] ?? 'awaiting validation');
        @endphp
        <h2 class="text-lg font-bold text-slate-800">
            {{ $activeLevel->name ?? 'Enrollments' }}
            <span class="font-medium text-slate-400">— {{ $statusSubtitle }}</span>
        </h2>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Status filter (always shown) — pre-official statuses only. --}}
            <select onchange="validateFilter('status', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="all" @selected($statusFilter === 'all')>All Statuses</option>
                @foreach($statusOptions as $sKey => $sLabel)
                    <option value="{{ $sKey }}" @selected($statusFilter === $sKey)>{{ $sLabel }}</option>
                @endforeach
            </select>

            @if($activeLevelIsBasic)
                <select onchange="validateFilter('year_level', this.value)"
                        class="rounded border border-gray-300 px-2 py-2 text-sm">
                    <option value="">All Grade Levels</option>
                    @foreach($gradeOptions as $val => $label)
                        <option value="{{ $val }}" @selected((string) $yearLevel === (string) $val)>{{ $label }}</option>
                    @endforeach
                </select>
            @else
                <select onchange="validateFilter('year_level', this.value)"
                        class="rounded border border-gray-300 px-2 py-2 text-sm">
                    <option value="">All Year Levels</option>
                    @foreach($yearOptions as $val => $label)
                        <option value="{{ $val }}" @selected((string) $yearLevel === (string) $val)>{{ $label }}</option>
                    @endforeach
                </select>
                <select onchange="validateFilter('program_id', this.value)"
                        class="rounded border border-gray-300 px-2 py-2 text-sm">
                    <option value="">All Programs</option>
                    @foreach($programOptions as $val => $label)
                        <option value="{{ $val }}" @selected((int) $programId === (int) $val)>{{ $label }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    <x-table.table
        tableKey="validate_enrollments"
        :columns="$columns"
        :data="$rows"
        :actions="$actions"
        perPage="20"
        emptyMessage="No students to validate for this selection."
    />
</div>

<script>
    // Apply a filter while keeping the active level tab.
    function validateFilter(key, value) {
        const url = new URL(window.location.href);
        if (value === '' || value === null) {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, value);
        }
        url.searchParams.set('level', @json($activeLevelId));
        window.location = url.toString();
    }
</script>
@endsection
