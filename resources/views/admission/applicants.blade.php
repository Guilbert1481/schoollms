@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-indigo-50 rounded-2xl text-indigo-600">
                <i data-lucide="{{ ($isArchived ?? false) ? 'users' : 'file-text' }}" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                    @php
                        $pageTitle = ($isArchived ?? false) ? 'Applicant Directory' : 'Applications';
                    @endphp
                    @if(! $showTabs && $levelTitle)
                        {{ $pageTitle }} — {{ $levelTitle }}
                    @else
                        {{ $pageTitle }}
                    @endif
                </h1>
                <p class="text-sm text-slate-500">
                    @if($isArchived ?? false)
                        Permanent record of officially enrolled applicants and applicants from expired enrollment windows.
                    @else
                        Every enrolment application currently being processed.
                    @endif
                </p>
            </div>
        </div>
        <div class="text-sm text-slate-500">
            <span class="font-bold text-slate-800">{{ $total }}</span>
            {{ ($isArchived ?? false) ? 'archived' : 'active' }}
            application{{ $total === 1 ? '' : 's' }}
        </div>
    </div>

    {{-- Education-level tabs (transcript-style: flat horizontal w/ counts) --}}
    @if($showTabs)
        <div class="flex flex-wrap gap-2 border-b border-slate-200">
            @foreach ($levels as $lvl)
                @php $count = $counts[$lvl->id] ?? 0; @endphp
                <a href="{{ route($routeName ?? 'admission.applicants', ['level' => $lvl->id]) }}"
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

    {{-- Reusable master table (built-in filter, column toggle & pagination) --}}
    <x-table.table
        tableKey="applicants"
        :columns="$columns"
        :data="$rows->values()"
        :hideActions="true"
        perPage="20"
    >
        @if($showTabs)
            <a href="{{ route($routeName ?? 'admission.applicants', ['level' => 'all']) }}"
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
