@extends('school.settings.master-data.organization.index')

@section('organization_content')

<h2 class="text-lg font-bold mb-4">Positions</h2>

<x-table.table
    tableKey="positions"
    :columns="$columns"
    :data="$data"
    deleteRoute="school.settings.master.organization.positions.destroy"
    createLabel="Add Position"
/>

@endsection