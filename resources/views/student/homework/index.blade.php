@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Homework</h1>
        <p class="text-sm text-slate-500">Your assignments across all your classes. Submit text and/or a file.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($homework->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">No homework posted yet.</div>
    @else
        @foreach ($homework as $hw)
            @php($mine = $hw->submissions->first())
            @php($cls = $classes[$hw->class_id] ?? null)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-2 flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">{{ $hw->title }}</h2>
                        <p class="text-xs text-slate-400">
                            @if ($cls){{ $cls->subject ?? '' }}@if ($cls->section) · {{ $cls->section }}@endif · @endif
                            @if ($hw->due_at) Due {{ $hw->due_at->format('M j, Y g:i A') }} · @endif
                            @if ($hw->points) {{ rtrim(rtrim(number_format((float) $hw->points, 2, '.', ''), '0'), '.') }} pts @endif
                        </p>
                    </div>
                    @if ($mine && $mine->score !== null)
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">
                            {{ rtrim(rtrim(number_format((float) $mine->score, 2, '.', ''), '0'), '.') }}@if ($hw->points)/{{ rtrim(rtrim(number_format((float) $hw->points, 2, '.', ''), '0'), '.') }}@endif
                        </span>
                    @elseif ($mine)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">Submitted</span>
                    @endif
                </div>

                @if ($hw->instructions)<p class="mb-3 whitespace-pre-line text-sm text-slate-700">{{ $hw->instructions }}</p>@endif

                @if ($mine && $mine->feedback)
                    <div class="mb-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                        <span class="font-medium">Teacher feedback:</span> {{ $mine->feedback }}
                    </div>
                @endif

                <form method="POST" action="{{ route('student.homework.submit', $hw->id) }}" enctype="multipart/form-data" class="space-y-3 border-t border-slate-100 pt-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Your answer</label>
                        <textarea name="body" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ $mine->body ?? '' }}</textarea>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <input type="file" name="file" class="text-sm">
                        @if ($mine && $mine->file_path)
                            <a href="{{ route('student.homework.file', $mine->id) }}" class="text-xs text-indigo-600 hover:underline">📎 {{ $mine->file_name ?: 'your file' }}</a>
                        @endif
                        <button type="submit" class="ml-auto rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            {{ $mine ? 'Update submission' : 'Submit' }}
                        </button>
                    </div>
                </form>
            </div>
        @endforeach
    @endif
</div>
@endsection
