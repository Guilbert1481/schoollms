@extends('layouts.app')

@section('content')
<div class="p-6 space-y-4">

    <h1 class="text-xl font-extrabold text-slate-800">Validate Enrollment</h1>

    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Education-level tabs — only the levels offered in the structure tree.
         The red superscript shows how many students await validation. --}}
    <div class="flex flex-wrap gap-2 border-b border-slate-200">
        @forelse($offeredRoots as $lvl)
            @php $count = $counts[$lvl->id] ?? 0; @endphp
            <a href="{{ route('registrar.enrollments.index', ['level' => $lvl->id]) }}"
               class="relative px-4 py-2 text-sm font-semibold rounded-t-lg border-b-2 -mb-px
                      {{ $activeLevelId === (int) $lvl->id
                          ? 'border-indigo-600 text-indigo-700 bg-indigo-50'
                          : 'border-transparent text-slate-600 hover:text-indigo-700 hover:bg-slate-50' }}">
                {{ $lvl->name }}
                @if($count > 0)
                    <sup class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold leading-none">
                        {{ $count }}
                    </sup>
                @endif
            </a>
        @empty
            <span class="px-4 py-2 text-sm text-slate-400">No education levels are offered yet.</span>
        @endforelse
    </div>

    {{-- Title (left) + level-specific filters (right) --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-slate-800">
            {{ $activeLevel->name ?? 'Enrollments' }}
            <span class="font-medium text-slate-400">— awaiting validation</span>
        </h2>

        <div class="flex flex-wrap items-center gap-2">
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
