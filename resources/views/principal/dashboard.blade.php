{{-- views/principal/dashboard.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="w-full">

    <div class="mb-4">
        <h1 class="text-2xl font-bold text-slate-800">Principal Dashboard</h1>
        <p class="text-sm text-slate-500">Basic Education oversight — subjects, grade levels, faculty, and student performance.</p>
    </div>

    {{-- KPI Section --}}
    @include('partials.dashboard.kpi-row')

</div>
@endsection
