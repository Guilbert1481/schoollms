@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="rounded-2xl bg-gradient-to-br from-indigo-600 via-blue-700 to-sky-800 p-6 md:p-8 text-white shadow-lg">
        <div class="flex items-center gap-3">
            <i data-lucide="scroll-text" class="w-8 h-8"></i>
            <div>
                <h1 class="text-2xl md:text-3xl font-bold">Transcript of Records</h1>
                <p class="text-sm md:text-base text-indigo-100 mt-1">
                    Master list of students by education level. Click a student to view their full transcript.
                </p>
            </div>
        </div>
    </div>

    {{-- Education-level tabs (hidden when the school only offers one level) --}}
    @if ($showTabs)
        <div class="flex flex-wrap gap-2 border-b border-slate-200">
            @foreach ($levels as $lvl)
                @php $count = $counts[$lvl->id] ?? 0; @endphp
                <a href="{{ route('registrar.transcripts.index', ['level' => $lvl->id]) }}"
                   class="px-4 py-2 text-sm font-semibold rounded-t-lg border-b-2 -mb-px
                          {{ ! ($showAll ?? false) && $activeLevelId === $lvl->id
                              ? 'border-indigo-600 text-indigo-700 bg-indigo-50'
                              : 'border-transparent text-slate-600 hover:text-indigo-700 hover:bg-slate-50' }}">
                    {{ $lvl->name }}
                    <span class="ml-1 inline-flex items-center justify-center min-w-[1.5rem] h-5 px-1.5 rounded-full text-[10px] font-bold
                                 {{ ! ($showAll ?? false) && $activeLevelId === $lvl->id ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600' }}">
                        {{ $count }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Master list --}}
    <x-table.table
        tableKey="transcript_master"
        :columns="$columns"
        :data="$rows->values()"
        :actions="config('tables.table-actions.transcript_master', [])"
    >
        @if($showTabs)
            <a href="{{ route('registrar.transcripts.index', ['level' => 'all']) }}"
               class="px-3 py-2 border rounded text-sm whitespace-nowrap transition
                      {{ ($showAll ?? false)
                          ? 'bg-indigo-600 text-white border-indigo-600'
                          : 'bg-white text-slate-700 border-gray-300 hover:bg-slate-50' }}">
                All
            </a>
        @endif
    </x-table.table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
