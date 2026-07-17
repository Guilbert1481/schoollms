@extends('layouts.app')

@section('page-title', 'Answer Sheets')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Print Answer Sheets</h1>
        <p class="text-sm text-slate-500">
            Choose a section — one OMR answer sheet is generated per enrolled student, with a unique QR code.
        </p>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-5 max-w-2xl">
        <div class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-3">
            {{ $test->title }} · {{ $test->subject?->name ?? 'Subject' }}
        </div>

        @if ($sections->isEmpty())
            <p class="text-sm text-slate-500 italic">
                No sections with enrolled students were found for your school. Enrol students into a section first.
            </p>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($sections as $section)
                    <li>
                        <a href="{{ route('teacher.tests.answer-sheets', $test) }}?section_id={{ $section->id }}"
                           class="flex items-center justify-between py-3 px-1 hover:bg-slate-50 rounded-lg">
                            <span class="font-semibold text-slate-800">
                                Grade {{ $section->year_level }} – {{ $section->name }}
                            </span>
                            <span class="text-xs text-slate-500">
                                {{ $section->student_count }} student{{ $section->student_count == 1 ? '' : 's' }}
                                &nbsp;→&nbsp;<span class="text-indigo-600 font-semibold">Generate</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

</div>
@endsection
