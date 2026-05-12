@extends('layouts.app')

@section('content')
<div class="w-full p-6 bg-slate-50 min-h-screen flex flex-col gap-6"
     x-data="caProductionReports()">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="clipboard-check" class="w-6 h-6 text-indigo-600"></i>
                Production Reports
            </h1>
            <p class="text-sm text-slate-500 mt-1">Topics still needing content &mdash; production gaps at a glance.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 min-h-[480px] flex items-center justify-center text-slate-400">
        <div class="text-center">
            <i data-lucide="clipboard-check" class="w-10 h-10 mx-auto mb-3 text-slate-300"></i>
            <p>Content gap report will list here.</p>
        </div>
    </div>

</div>
<script src="{{ asset('js/course_architect/production_reports.js') }}" defer></script>
@endsection
