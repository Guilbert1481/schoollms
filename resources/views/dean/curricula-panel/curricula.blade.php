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

    <h2 class="text-lg font-bold mb-2">Curricula</h2>

    <x-table.table
        tableKey="curriculums"
        :columns="$columns"
        :data="$data"
        :actions="config('tables.table-actions.curriculums')"
        createModal="curriculumCreateModal"
        editModal="curriculumEditModal"
        deleteRoute="dean.curricula-panel.curriculums.destroy"
    />

    {{-- Create modal (draggable) --}}
    <x-modal.form id="curriculumCreateModal" title="Add Curriculum">
        <form method="POST" action="{{ route('dean.curricula-panel.curriculums.store') }}">
            @csrf
            @foreach($formColumns as $column)
                <div class="mb-3">
                    <label class="block text-sm mb-1">
                        {{ $labels[$column] ?? ucwords(str_replace('_',' ', $column)) }}
                    </label>
                    @if(isset($relations[$column]) && is_array($relations[$column]))
                        @php
                            $relation = $relations[$column];
                            $query = \Illuminate\Support\Facades\DB::table($relation['table']);
                            if(!empty($relation['whereSchool'])){
                                $query->where($relation['table'].'.school_id', auth()->user()->school_id);
                            }
                            $options = $query->get(['id', 'name']);
                        @endphp
                        <select name="{{ $column }}" class="border p-2 w-full">
                            <option value="">Select...</option>
                            @foreach($options as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="{{ $column }}" class="border p-2 w-full">
                    @endif
                </div>
            @endforeach
        </form>
    </x-modal.form>

    {{-- Edit modal (draggable) --}}
    <x-modal.form id="curriculumEditModal" title="Edit Curriculum">
        <form method="POST" action="{{ route('school.system.dynamic.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="table" value="curriculums">
            <input type="hidden" name="id">
            @foreach($formColumns as $column)
                <div class="mb-3">
                    <label class="block text-sm mb-1">
                        {{ $labels[$column] ?? ucwords(str_replace('_',' ', $column)) }}
                    </label>
                    @if(isset($relations[$column]) && is_array($relations[$column]))
                        @php
                            $relation = $relations[$column];
                            $query = \Illuminate\Support\Facades\DB::table($relation['table']);
                            if(!empty($relation['whereSchool'])){
                                $query->where($relation['table'].'.school_id', auth()->user()->school_id);
                            }
                            $options = $query->get(['id', 'name']);
                        @endphp
                        <select name="{{ $column }}" class="border p-2 w-full">
                            <option value="">Select...</option>
                            @foreach($options as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="{{ $column }}" class="border p-2 w-full">
                    @endif
                </div>
            @endforeach
        </form>
    </x-modal.form>

</div>
@endsection
