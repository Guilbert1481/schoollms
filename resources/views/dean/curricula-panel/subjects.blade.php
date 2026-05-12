@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6 space-y-6">

    @include('dean.curricula-panel.partials._header')

    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.curricula_panel'),
    ])

    @if(session('success'))
        <div class="bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    <h2 class="text-lg font-bold mb-2">Subjects</h2>

    <x-table.table
        tableKey="subjects"
        :columns="$columns"
        :data="$data"
        :actions="config('tables.table-actions.subjects')"
        createModal="subjectCreateModal"
        editModal="subjectEditModal"
        deleteRoute="dean.curricula-panel.subjects.destroy"
    />

    {{-- Create modal (draggable) --}}
    <x-modal.form id="subjectCreateModal" title="Add Subject">
        <form method="POST" action="{{ route('dean.curricula-panel.subjects.store') }}">
            @csrf
            @foreach($formColumns as $column)
                <div class="mb-3">
                    <label class="block text-sm mb-1">
                        {{ $labels[$column] ?? ucwords(str_replace('_',' ', $column)) }}
                    </label>
                    <input type="text" name="{{ $column }}" class="border p-2 w-full">
                </div>
            @endforeach
        </form>
    </x-modal.form>

    {{-- Edit modal (draggable) --}}
    <x-modal.form id="subjectEditModal" title="Edit Subject">
        <form method="POST" action="{{ route('school.system.dynamic.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="table" value="subjects">
            <input type="hidden" name="id">
            @foreach($formColumns as $column)
                <div class="mb-3">
                    <label class="block text-sm mb-1">
                        {{ $labels[$column] ?? ucwords(str_replace('_',' ', $column)) }}
                    </label>
                    <input type="text" name="{{ $column }}" class="border p-2 w-full">
                </div>
            @endforeach
        </form>
    </x-modal.form>

</div>
@endsection
