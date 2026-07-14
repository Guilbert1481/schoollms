{{-- Student "Grades" → Report Card (current period). --}}
@extends('layouts.app')

@section('content')
@php $rcStandalone = true; @endphp

@if($report)
    @include('partials.report-card')
@else
    <div class="mx-auto w-full max-w-7xl p-4 md:p-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-slate-500">
            No report card is available yet.
        </div>
    </div>
@endif
@endsection
