@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Homework</h1>
        <p class="text-sm text-slate-500">Post homework for a class you teach. Students submit; you score each submission.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Class picker --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @if ($classes->isEmpty())
            <p class="text-sm text-slate-500">You have no classes assigned this term.</p>
        @else
            <form method="GET" action="{{ route('teacher.homework.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="min-w-[260px]">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Class</label>
                    <select name="class_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select a class…</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}" @selected(($context['class']->id ?? null) === $c->id)>
                                {{ $c->subject->name ?? $c->code }}@if ($c->section) &middot; {{ $c->section->name }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Open</button>
            </form>
        @endif
    </div>

    @if ($context)
        {{-- New homework --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-400">New homework</h2>
            <form method="POST" action="{{ route('teacher.homework.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="class_id" value="{{ $context['class']->id }}">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Title</label>
                    <input type="text" name="title" required maxlength="200" value="{{ old('title') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Instructions</label>
                    <textarea name="instructions" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('instructions') }}</textarea>
                </div>
                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Points</label>
                        <input type="number" step="0.01" min="0" max="1000" name="points" value="{{ old('points') }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Due</label>
                        <input type="datetime-local" name="due_at" value="{{ old('due_at') }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_published" value="1" checked class="rounded border-slate-300">
                            Publish to students
                        </label>
                    </div>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Counts toward</label>
                        <select name="grade_component_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Not graded / practice</option>
                            @foreach ($context['components'] as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @if ($context['components']->isEmpty())
                            <p class="mt-1 text-[11px] text-amber-600">
                                No grade components are set up for this class's level yet, so homework can only post as practice.
                                Once a grading scheme with components exists for this level, they'll appear here automatically.
                            </p>
                        @else
                            <p class="mt-1 text-[11px] text-slate-400">Graded homework in a component auto-feeds that component of the gradebook.</p>
                        @endif
                    </div>
                    @if ($context['track'] === 'basic')
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Grading period</label>
                            <select name="grading_period" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @foreach ([1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'] as $p => $label)
                                    <option value="{{ $p }}">{{ $label }} grading</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Post homework</button>
                </div>
            </form>
        </div>

        {{-- Existing homework --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4"><h2 class="text-lg font-semibold text-slate-800">Posted</h2></div>
            @if ($context['homework']->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-slate-500">No homework posted for this class yet.</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($context['homework'] as $hw)
                        <div class="flex items-center justify-between px-5 py-3">
                            <div>
                                <a href="{{ route('teacher.homework.show', $hw->id) }}" class="font-medium text-slate-800 hover:text-indigo-600">{{ $hw->title }}</a>
                                <div class="text-xs text-slate-400">
                                    @if (! $hw->is_published)<span class="text-amber-600">Draft</span> · @endif
                                    @if ($hw->due_at) Due {{ $hw->due_at->format('M j, Y g:i A') }} · @endif
                                    {{ $hw->submissions_count }} submission(s)
                                </div>
                            </div>
                            <a href="{{ route('teacher.homework.show', $hw->id) }}" class="text-sm text-indigo-600 hover:underline">View submissions</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
