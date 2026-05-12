@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Curricula Management</h1>
        <p class="text-sm text-slate-500 mt-1">Manage curricula, subjects, and programs. Use the tabs below to switch views.</p>
    </div>

    {{-- Tabs navigation --}}
    @php
        $currentTab = request('tab', 'curricula');
        $baseUrl    = route('dean.curricula-panel.index');
        $tabs = [
            ['key' => 'curricula', 'label' => 'Curricula'],
            ['key' => 'subjects',  'label' => 'Subjects'],
            ['key' => 'programs',  'label' => 'Programs'],
        ];
    @endphp
    <div class="flex gap-6 border-b mb-6">
        @foreach ($tabs as $t)
            @php $isActive = $currentTab === $t['key']; @endphp
            <a href="{{ $baseUrl }}?tab={{ $t['key'] }}"
               class="px-3 py-2 border-b-2 text-sm font-semibold
                      {{ $isActive ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-600 hover:text-indigo-600' }}">
                {{ $t['label'] }}
            </a>
        @endforeach
    </div>

    {{-- Tab content --}}
    @if ($currentTab === 'curricula')
        @include('dean.curricula._curricula-list')
    @elseif ($currentTab === 'subjects')
        @include('dean.curricula.subjects-tab')
    @elseif ($currentTab === 'programs')
        @include('dean.curricula.programs-tab')
    @endif
</div>
@endsection
