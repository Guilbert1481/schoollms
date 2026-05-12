@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    @include('school.settings.partials.master-data._header')

    {{-- Master Data Tabs --}}
    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.master_data')
    ])

    {{-- Organization Sub Tabs --}}
    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.organization_master')
    ])

    <div class="bg-white p-4 rounded shadow">
        @yield('organization_content')
    </div>

</div>
@endsection