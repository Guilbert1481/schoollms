@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Students</h1>
        <p class="text-sm text-slate-500">Pick a class you teach to see its enrolled students.</p>
    </div>

    {{-- Class picker --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @if ($classes->isEmpty())
            <p class="text-sm text-slate-500">You have no classes assigned this term.</p>
        @else
            <form method="GET" action="{{ route('teacher.students.index') }}" class="flex flex-wrap items-end gap-3">
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
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 class="text-lg font-semibold text-slate-800">
                    {{ $context['class']->subject->name ?? $context['class']->code }}
                    @if ($context['class']->section)<span class="text-sm font-normal text-slate-400">&middot; {{ $context['class']->section->name }}</span>@endif
                </h2>
                <span class="text-sm text-slate-500">{{ $context['students']->count() }} enrolled</span>
            </div>

            @if ($context['students']->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-slate-500">No students enrolled in this class yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-slate-400">
                                <th class="px-5 py-3 font-semibold">#</th>
                                <th class="px-5 py-3 font-semibold">Student No.</th>
                                <th class="px-5 py-3 font-semibold">Name</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($context['students'] as $i => $student)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-3 text-slate-400">{{ $i + 1 }}</td>
                                    <td class="px-5 py-3 font-medium text-slate-700">{{ $student->student_number ?? '—' }}</td>
                                    <td class="px-5 py-3 text-slate-700">
                                        {{ trim($student->last_name.', '.$student->first_name.' '.($student->middle_name ?? '')) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
