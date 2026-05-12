@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    {{-- HEADER --}}
    @include('school.settings.partials.master-data._header')

    {{-- MASTER DATA TABS --}}
    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.master_data')
    ])

    {{-- TRAINING SUB TABS --}}
    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.training_master')
    ])

    {{-- CONTENT --}}
    <div class="bg-white p-4 rounded shadow">
        @yield('training_content')
    </div>

</div>
@endsection