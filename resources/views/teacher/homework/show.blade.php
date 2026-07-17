@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $homework->title }}</h1>
            <p class="text-sm text-slate-500">
                @if ($homework->points) {{ rtrim(rtrim(number_format((float) $homework->points, 2, '.', ''), '0'), '.') }} pts · @endif
                @if ($homework->due_at) Due {{ $homework->due_at->format('M j, Y g:i A') }} · @endif
                {{ $submissions->count() }} submission(s)
            </p>
        </div>
        <a href="{{ route('teacher.homework.index', ['class_id' => $homework->class_id]) }}"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back</a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if ($homework->instructions)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-700 shadow-sm">{{ $homework->instructions }}</div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($submissions->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-slate-500">No submissions yet.</p>
        @else
            <form method="POST" action="{{ route('teacher.homework.grade', $homework->id) }}">
                @csrf
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-3">Student</th>
                                <th class="px-5 py-3">Submitted</th>
                                <th class="px-5 py-3">Response</th>
                                <th class="px-5 py-3">Score</th>
                                <th class="px-5 py-3">Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($submissions as $sub)
                                <tr class="border-b border-slate-100 align-top">
                                    <td class="px-5 py-3">
                                        <div class="font-medium text-slate-800">{{ trim(($sub->student->last_name ?? '').', '.($sub->student->first_name ?? '')) }}</div>
                                        <div class="text-xs text-slate-400">{{ $sub->student->student_number ?? '' }}</div>
                                    </td>
                                    <td class="px-5 py-3 text-xs text-slate-500">{{ $sub->submitted_at?->format('M j, g:i A') ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        @if ($sub->body)<p class="max-w-xs whitespace-pre-line text-slate-700">{{ $sub->body }}</p>@endif
                                        @if ($sub->file_path)
                                            <a href="{{ route('teacher.homework.file', $sub->id) }}" class="text-xs text-indigo-600 hover:underline">📎 {{ $sub->file_name ?: 'file' }}</a>
                                        @endif
                                        @if (! $sub->body && ! $sub->file_path)<span class="text-slate-300">—</span>@endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <input type="number" step="0.01" min="0" @if ($homework->points) max="{{ $homework->points }}" @endif
                                            name="grades[{{ $sub->id }}][score]"
                                            value="{{ $sub->score !== null ? rtrim(rtrim(number_format((float) $sub->score, 2, '.', ''), '0'), '.') : '' }}"
                                            class="w-20 rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                    </td>
                                    <td class="px-5 py-3">
                                        <input type="text" name="grades[{{ $sub->id }}][feedback]" value="{{ $sub->feedback }}"
                                            class="w-full min-w-[160px] rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end border-t border-slate-200 px-5 py-4">
                    <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Save grades</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
