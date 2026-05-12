@extends('layouts.app')

@section('content')
<div class="w-full p-6 bg-slate-50 min-h-screen flex flex-col gap-6"
     x-data="caAssessmentLab()">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="flask-conical" class="w-6 h-6 text-indigo-600"></i>
                Assessment Lab
            </h1>
            <p class="text-sm text-slate-500 mt-1">Quizzes &amp; Mastery Gates for the auto-path.</p>
        </div>
        <button class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <i data-lucide="plus" class="w-4 h-4"></i> New Assessment
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 min-h-[480px] flex items-center justify-center text-slate-400">
        <div class="text-center">
            <i data-lucide="flask-conical" class="w-10 h-10 mx-auto mb-3 text-slate-300"></i>
            <p>Quiz &amp; mastery gate authoring coming soon.</p>
        </div>
    </div>

</div>
<script src="{{ asset('js/course_architect/assessment_lab.js') }}" defer></script>
@endsection
