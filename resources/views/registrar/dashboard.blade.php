{{-- views/admin/dashboard.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">


    {{-- KPI Section --}}
    @include('partials.dashboard.kpi-row')


   @include('partials.dashboard.stat-card', ['trendCharts' => $trendCharts])



    {{-- Other admin-specific sections --}}
    {{-- Inspiration Manager --}}
    {{-- Reports --}}
    {{-- Charts --}}
    
</div>
@endsection
