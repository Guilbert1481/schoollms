@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <h1 class="text-xl font-extrabold text-slate-800">Test Management</h1>

    {{-- Education-level tabs — only the levels this teacher is assigned to.
         Hidden automatically when the teacher spans a single level. --}}
    <x-table.level-tabs route="teacher.tests.management"
                        :levels="$levels"
                        :activeLevelId="$activeLevelId"
                        :showAll="$showAll" />

    <x-table.table
        tableKey="teacher_tests"
        :columns="$columns"
        :data="$rows"
        :hideActions="true"
        perPage="10"
        createRoute="teacher.tests.create"
        createLabel="New Test"
        emptyMessage="You have not created any tests here yet — build one in the Test Builder."
    >
        <x-slot:afterFilter>
            <select onchange="testMgmtApplyFilter('level_id', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All {{ $showAll ? 'Levels' : ($activeLevelIsBasic ? 'Grade Levels' : 'Year Levels') }}</option>
                @foreach($levelOptions as $lvlId => $lvlName)
                    <option value="{{ $lvlId }}" @selected((int) $levelId === (int) $lvlId)>{{ $lvlName }}</option>
                @endforeach
            </select>

            @if($showProgram)
                <select onchange="testMgmtApplyFilter('program_id', this.value)"
                        class="rounded border border-gray-300 px-2 py-2 text-sm">
                    <option value="">All Programs</option>
                    @foreach($programOptions as $pid => $pname)
                        <option value="{{ $pid }}" @selected((int) $programId === (int) $pid)>{{ $pname }}</option>
                    @endforeach
                </select>
            @endif

            <select onchange="testMgmtApplyFilter('section_id', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All Sections</option>
                @foreach($sectionOptions as $secId => $secName)
                    <option value="{{ $secId }}" @selected((int) $sectionId === (int) $secId)>{{ $secName }}</option>
                @endforeach
            </select>

            <select onchange="testMgmtApplyFilter('subject_id', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All Subjects</option>
                @foreach($subjectOptions as $subId => $subName)
                    <option value="{{ $subId }}" @selected((int) $subjectId === (int) $subId)>{{ $subName }}</option>
                @endforeach
            </select>
        </x-slot:afterFilter>
    </x-table.table>

</div>

<script>
    // Server-side filters: swap one query param and reload, keeping the active
    // level tab and the other filters intact.
    function testMgmtApplyFilter(key, value) {
        const url = new URL(window.location);
        if (value) {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
        window.location = url.toString();
    }
</script>
@endsection
