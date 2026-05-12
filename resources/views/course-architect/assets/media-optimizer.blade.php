@extends('layouts.app')

@section('content')
<div class="w-full p-6 bg-slate-50 min-h-screen flex flex-col gap-6"
     x-data="caMediaOptimizer()">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="film" class="w-6 h-6 text-indigo-600"></i>
                Media Optimizer
            </h1>
            <p class="text-sm text-slate-500 mt-1">File size reduction &amp; transcoding queue.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 min-h-[480px] flex items-center justify-center text-slate-400">
        <div class="text-center">
            <i data-lucide="film" class="w-10 h-10 mx-auto mb-3 text-slate-300"></i>
            <p>Transcoding jobs &amp; bandwidth savings will appear here.</p>
        </div>
    </div>

</div>
<script src="{{ asset('js/course_architect/media_optimizer.js') }}" defer></script>
@endsection
