@extends('layouts.app')

@section('content')
<div class="w-full p-6 bg-slate-50 min-h-screen flex flex-col gap-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="gauge" class="w-6 h-6 text-indigo-600"></i>
                {{ config('roles.catalog.'.auth()->user()->role.'.label', 'Course Architect') }} Dashboard
            </h1>
            <p class="text-sm text-slate-500 mt-1">Production stats &mdash; competency coverage, pending content, pipeline health.</p>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-2 text-slate-500 text-xs uppercase tracking-wide">
                <i data-lucide="target" class="w-4 h-4"></i> Competency Attached
            </div>
            <div class="mt-2 text-3xl font-bold text-indigo-600">{{ $stats['attached_competency_pct'] }}%</div>
            <p class="text-xs text-slate-500 mt-1">Topics linked to Program Head competencies.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-2 text-slate-500 text-xs uppercase tracking-wide">
                <i data-lucide="clipboard-list" class="w-4 h-4"></i> Pending Topics
            </div>
            <div class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['pending_topics'] }}</div>
            <p class="text-xs text-slate-500 mt-1">Topics waiting on content production.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-2 text-slate-500 text-xs uppercase tracking-wide">
                <i data-lucide="check-circle" class="w-4 h-4"></i> Published Lessons
            </div>
            <div class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['published_lessons'] }}</div>
            <p class="text-xs text-slate-500 mt-1">Released to learner-facing path.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-2 text-slate-500 text-xs uppercase tracking-wide">
                <i data-lucide="git-branch" class="w-4 h-4"></i> Active Paths
            </div>
            <div class="mt-2 text-3xl font-bold text-sky-600">{{ $stats['active_paths'] }}</div>
            <p class="text-xs text-slate-500 mt-1">Auto-sequenced learning journeys.</p>
        </div>
    </div>

    {{-- Activity Placeholder --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-3">Recent Production Activity</h2>
        <p class="text-sm text-slate-500">No recent activity yet.</p>
    </div>

</div>
<script src="{{ asset('js/course_architect/dashboard.js') }}" defer></script>
@endsection
