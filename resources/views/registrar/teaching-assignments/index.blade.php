@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Teaching Assignments</h1>
        <p class="text-sm text-slate-500">Assign a teacher to teach a subject to a section. Each assignment feeds that teacher's gradebook and attendance.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Add assignment --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
         x-data="{
            teacher: '',
            subjectsByTeacher: {{ \Illuminate\Support\Js::from($subjectsByTeacher) }},
            allSubjects: {{ \Illuminate\Support\Js::from($subjects) }},
            get subjectOptions() {
                const q = this.subjectsByTeacher[this.teacher];
                return (!q || q.length === 0) ? this.allSubjects : this.allSubjects.filter(s => q.includes(s.id));
            }
         }">
        @if ($teachers->isEmpty())
            <p class="text-sm text-slate-500">No teachers exist for this school yet.</p>
        @else
            <form method="POST" action="{{ route('registrar.teaching-assignments.store') }}" class="grid gap-4 md:grid-cols-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Teacher</label>
                    <select name="teacher_id" x-model="teacher" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select…</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t['id'] }}">{{ $t['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Subject</label>
                    <select name="subject_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select a teacher first…</option>
                        <template x-for="s in subjectOptions" :key="s.id">
                            <option :value="s.id" x-text="s.code + ' — ' + s.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Section</label>
                    <select name="section_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select…</option>
                        @foreach ($sections as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Assign
                    </button>
                </div>
            </form>
        @endif
    </div>

    {{-- Current assignments --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-800">Current assignments</h2>
        </div>
        @if ($assignments->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-slate-500">No teaching assignments yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3">Teacher</th>
                            <th class="px-5 py-3">Subject</th>
                            <th class="px-5 py-3">Section</th>
                            <th class="px-5 py-3">Term</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assignments as $a)
                            <tr class="border-b border-slate-100">
                                <td class="px-5 py-3 font-medium text-slate-800">{{ $a->teacher }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $a->subject }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $a->section }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $a->term ?? '—' }}@if ($a->ay) · {{ $a->ay }}@endif</td>
                                <td class="px-5 py-3 text-right">
                                    <form method="POST" action="{{ route('registrar.teaching-assignments.destroy', $a->id) }}"
                                          onsubmit="return confirm('Remove this teaching assignment?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-sm text-rose-500 hover:underline">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
