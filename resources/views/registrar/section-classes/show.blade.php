@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">
            Section Classes — {{ $section->name }}
            <span class="text-slate-400 font-normal">· {{ $section->educationNode?->name }}</span>
        </h1>
        <p class="text-sm text-slate-500">
            One class per learning area of the grade. Choosing a teacher creates (or re-assigns) that class;
            leaving a row blank skips it. Same rows the Teaching Assignments page manages.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('registrar.section-classes.store', $section) }}"
          class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        @csrf

        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Section Adviser</label>
                <select name="adviser_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— none —</option>
                    @foreach ($teachers as $t)
                        <option value="{{ $t['id'] }}" @selected($section->adviser_id == $t['id'])>{{ $t['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Set all subjects to</label>
                <select id="fill-all" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— choose a teacher —</option>
                    @foreach ($teachers as $t)
                        <option value="{{ $t['id'] }}">{{ $t['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($subjects->isEmpty())
            <p class="px-5 py-6 text-sm text-slate-500">
                This grade has no active learning areas yet (grade_level_subjects is empty for
                {{ $section->educationNode?->name }}).
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-500">
                        <tr>
                            <th class="px-5 py-2">Learning Area</th>
                            <th class="px-5 py-2">Code</th>
                            <th class="px-5 py-2">Class</th>
                            <th class="px-5 py-2">Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subjects as $s)
                            <tr class="border-t border-slate-100">
                                <td class="px-5 py-2 font-medium text-slate-800">{{ $s->name }}</td>
                                <td class="px-5 py-2 text-slate-500">{{ $s->code }}</td>
                                <td class="px-5 py-2">
                                    @if ($s->class_id)
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">Created</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-500">Not yet</span>
                                    @endif
                                </td>
                                <td class="px-5 py-2">
                                    <select name="teachers[{{ $s->subject_id }}]"
                                            class="teacher-pick w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                        <option value="">— keep empty —</option>
                                        @foreach ($teachers as $t)
                                            <option value="{{ $t['id'] }}" @selected($s->teacher_id == $t['id'])>{{ $t['name'] }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="flex items-center justify-between border-t border-slate-200 px-5 py-4">
            <a href="{{ route('admission.sections.index', ['term_id' => $section->term_id]) }}"
               class="text-sm text-slate-600 hover:underline">&larr; Back to Sections</a>
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Save Classes &amp; Adviser
            </button>
        </div>
    </form>
</div>

<script>
    document.getElementById('fill-all')?.addEventListener('change', function () {
        if (!this.value) return;
        document.querySelectorAll('select.teacher-pick').forEach(s => { s.value = this.value; });
    });
</script>
@endsection
