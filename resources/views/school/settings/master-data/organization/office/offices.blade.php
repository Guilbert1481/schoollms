@extends('school.settings.master-data.organization.index')

@section('organization_content')

<h2 class="text-lg font-bold mb-4">Offices</h2>

<x-table.table
    tableKey="offices"
    :columns="$columns"
    :data="$data"
    createModal="officeCreateModal"
    editModal="officeEditModal"
    deleteRoute="school.settings.master.organization.offices.destroy"
    createLabel="Add Office"
/>

<x-modal.form id="officeCreateModal" title="Add Office">
    <form method="POST" action="{{ url('dynamic/' . $table) }}">
        @csrf

        <input type="hidden" name="table" value="{{ $table }}">

        @foreach($formColumns as $column)
            @if(!in_array($column, ['id','school_id','created_at','updated_at']))
                <div class="mb-3">
                    <label class="block text-sm mb-1">
                        {{ ucwords(str_replace('_',' ', $column)) }}
                    </label>

                    <input type="text" name="{{ $column }}" class="border p-2 w-full">
                </div>
            @endif
        @endforeach
    </form>
</x-modal.form>

@endsection