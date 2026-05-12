@extends('layouts.app')

@section('content')

<div class="p-6">

    <h1 class="text-2xl font-bold mb-4">Assignment Management</h1>

    <div class="mt-2 mb-4">
        @include('components.tabs.horizontal-tab', ['tabs' => config('tabs.tabs.assignments')])
    </div>

    {{-- Only render the partial matching the current route --}}
    @if(request()->routeIs('admin.assignments.indexPrograms'))
        @include('admin.assignments.partials.programs')
    @elseif(request()->routeIs('admin.assignments.indexOffices'))
        @include('admin.assignments.partials.offices')
    @elseif(request()->routeIs('admin.assignments.indexSignatory'))
        @include('admin.assignments.partials.signatory')
    @elseif(request()->routeIs('admin.assignments.indexDepartments'))
        @include('admin.assignments.partials.departments')
    @else
        @include('admin.assignments.partials.colleges')
    @endif

</div>

@endsection