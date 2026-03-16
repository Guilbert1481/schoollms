{{-- views/admin/dashboard.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="w-full">


    {{-- KPI Section --}}
    @include('partials.dashboard.kpi-row')



    {{-- Other admin-specific sections --}}
    {{-- Inspiration Manager --}}
    {{-- Reports --}}
    {{-- Charts --}}
    
</div>
@endsection
