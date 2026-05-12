@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div class="flex items-start justify-between gap-4">
        <div>
            <nav class="text-xs text-slate-500 mb-2">
                <a href="{{ route('training.trainee.available-courses') }}" class="hover:underline">Available Courses</a>
                <span class="mx-1">/</span>
                <span class="text-slate-700 font-semibold">{{ $setting->title ?: $setting->name }}</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800">
                {{ $catalogLabel }}: Choose a Program
            </h1>
            <p class="text-sm text-slate-500">
                Select a program to view its subjects and enroll in the ones you want.
            </p>
        </div>
        <a href="{{ route('training.trainee.available-courses') }}"
           class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
            Back
        </a>
    </div>

    @if(empty($programs))
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
            No programs have been configured for this course yet.
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($programs as $p)
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-lg transition-all flex flex-col">
                    <div class="inline-flex items-center gap-2 text-xs text-indigo-600 font-semibold">
                        <span class="rounded-full bg-indigo-50 px-2 py-0.5">{{ $p['code'] }}</span>
                    </div>
                    <h2 class="mt-3 text-lg font-bold text-slate-900">{{ $p['name'] }}</h2>
                    <p class="mt-2 text-sm text-slate-600 flex-1">
                        {{ $p['description'] }}
                    </p>
                    <a href="{{ route('training.trainee.available-courses.program.subjects', ['session' => $setting->id, 'program' => $p['key']]) }}"
                       class="mt-5 inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        View Subjects
                    </a>
                </article>
            @endforeach
        </div>
    @endif

</div>
@endsection
