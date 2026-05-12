@extends('layouts.app')

@section('content')

<div class="p-6">

    <h1 class="text-2xl font-bold mb-4">Service Plan</h1>

    <div class="mt-2 mb-4">
        @include('components.tabs.horizontal-tab', ['tabs' => config('tabs.tabs.service_plan')])
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        @if(request()->routeIs('admin.service-plan.addons'))
            @include('admin.service-plan.partials.addons')
        @else
            @include('admin.service-plan.partials.features')
        @endif
    </div>

</div>

@endsection
