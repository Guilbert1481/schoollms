@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Gradebook</h1>
        <p class="text-sm text-slate-500">Enter component scores for a class, save a draft, then post the finals.</p>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
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
            <form method="GET" action="{{ route('teacher.gradebook.index') }}" class="flex flex-wrap items-end gap-3">
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
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    Open gradebook
                </button>
            </form>
        @endif
    </div>

    @if ($context)
        {{-- Basic ed grades per grading period — let the teacher pick which. --}}
        @if ($context['track'] === 'basic')
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-slate-600">Grading period:</span>
                @foreach ([1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'] as $p => $label)
                    <a href="{{ route('teacher.gradebook.index', ['class_id' => $context['class']->id, 'period' => $p]) }}"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $context['period'] === $p ? 'bg-slate-800 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        @endif

        @if (! $context['has_scheme'])
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-6 text-sm text-amber-800">
                No grading scheme is configured for this class's level yet. Ask your
                <span class="font-semibold">{{ $context['track'] === 'basic' ? 'Principal' : 'Dean' }}</span> to set one up under
                <span class="font-semibold">Settings → Grading Scheme</span> before entering grades.
            </div>
        @elseif ($context['roster']->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
                No students are enrolled in this class.
            </div>
        @else
            <form method="POST" action="{{ route('teacher.gradebook.draft') }}">
                @csrf
                <input type="hidden" name="class_id" value="{{ $context['class']->id }}">
                @if ($context['track'] === 'basic')
                    <input type="hidden" name="period" value="{{ $context['period'] }}">
                @endif

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h2 class="text-lg font-semibold text-slate-800">
                            {{ $context['class']->subject->name ?? $context['class']->code }}
                            @if ($context['class']->section) — {{ $context['class']->section->name }} @endif
                            @if ($context['track'] === 'basic') <span class="text-sm font-normal text-slate-400">· Q{{ $context['period'] }}</span> @endif
                        </h2>
                        <span class="text-sm text-slate-500">{{ $context['roster']->count() }} student(s)</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wider text-slate-400">
                                    <th class="px-4 py-3">Student</th>
                                    @foreach ($context['components'] as $component)
                                        <th class="px-3 py-3 text-center">{{ $component->name }}<br><span class="text-[10px] normal-case text-slate-300">{{ (float) $component->weight }}%</span></th>
                                    @endforeach
                                    <th class="px-3 py-3 text-center">Computed</th>
                                    <th class="px-3 py-3 text-center">Posted</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($context['roster'] as $row)
                                    <tr class="border-b border-slate-100">
                                        <td class="px-4 py-2">
                                            <div class="font-medium text-slate-800">{{ $row['name'] }}</div>
                                            <div class="text-xs text-slate-400">{{ $row['number'] }}</div>
                                        </td>
                                        @foreach ($context['components'] as $component)
                                            <td class="px-3 py-2 text-center">
                                                <input type="number" step="0.01" min="0" max="100"
                                                    name="scores[{{ $row['student_id'] }}][{{ $component->id }}]"
                                                    value="{{ isset($row['scores'][$component->id]) ? rtrim(rtrim(number_format((float) $row['scores'][$component->id], 2, '.', ''), '0'), '.') : '' }}"
                                                    class="w-20 rounded-lg border border-slate-300 px-2 py-1.5 text-center text-sm">
                                            </td>
                                        @endforeach
                                        {{-- Computed (preview, not yet posted) --}}
                                        <td class="px-3 py-2 text-center">
                                            @php($p = $row['preview'])
                                            @if ($p && $p->final !== null)
                                                <span class="font-semibold {{ $p->passed ? 'text-emerald-600' : 'text-rose-600' }}">{{ $p->final }}</span>
                                                @unless ($p->isComplete)<div class="text-[10px] text-amber-600">partial</div>@endunless
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                        {{-- Posted (on the record) --}}
                                        <td class="px-3 py-2 text-center">
                                            @if ($row['posted_final'] !== null)
                                                <span class="font-semibold text-slate-700">{{ rtrim(rtrim(number_format((float) $row['posted_final'], 2, '.', ''), '0'), '.') }}</span>
                                                <div class="text-[10px] uppercase text-slate-400">{{ $row['posted_status'] }}</div>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-5 py-4">
                        <p class="text-xs text-slate-400">Only complete grades (every component scored) are posted to the record.</p>
                        <div class="flex gap-3">
                            <button type="submit" formaction="{{ route('teacher.gradebook.draft') }}"
                                class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                Save draft
                            </button>
                            <button type="submit" formaction="{{ route('teacher.gradebook.post') }}"
                                class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                                Post grades
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    @endif
</div>
@endsection
