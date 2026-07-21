@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">My Classes</h1>
        <p class="text-sm text-slate-500">Every class you teach this term. Jump straight to attendance, grades, homework, or the roster.</p>
    </div>

    @if ($classes->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <p class="text-sm text-slate-500">You have no classes assigned this term.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($classes as $class)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-base font-semibold text-slate-800">{{ $class->subject->name ?? $class->code }}</h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                @if ($class->section){{ $class->section->name }} &middot; @endif{{ $class->code }}
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-600">
                            {{ $class->students_count }} student{{ $class->students_count === 1 ? '' : 's' }}
                        </span>
                    </div>

                    @if ($class->schedule || $class->room)
                        <p class="mt-3 text-xs text-slate-400">
                            @if ($class->schedule){{ $class->schedule }}@endif
                            @if ($class->room) &middot; Room {{ $class->room }}@endif
                        </p>
                    @endif

                    <div class="mt-4 grid grid-cols-3 gap-2">
                        <a href="{{ route('teacher.attendance.index', ['type' => 'session', 'class_id' => $class->id]) }}"
                           class="rounded-lg bg-slate-50 px-2 py-2 text-center text-xs font-medium text-slate-700 hover:bg-slate-100">Attendance</a>
                        <a href="{{ route('teacher.gradebook.index', ['class_id' => $class->id]) }}"
                           class="rounded-lg bg-slate-50 px-2 py-2 text-center text-xs font-medium text-slate-700 hover:bg-slate-100">Grades</a>
                        <a href="{{ route('teacher.homework.index', ['class_id' => $class->id]) }}"
                           class="rounded-lg bg-slate-50 px-2 py-2 text-center text-xs font-medium text-slate-700 hover:bg-slate-100">Homework</a>
                    </div>

                    <div class="mt-2">
                        <a href="{{ route('teacher.students.index', ['class_id' => $class->id]) }}"
                           class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:underline">
                            View students
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
