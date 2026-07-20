@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Sectioning Workbench</h1>
            <p class="text-sm text-slate-500">
                Place enrolled basic-ed students into their grade's published sections.
                Auto-distribute only proposes — nothing is saved until you apply.
            </p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <label class="text-xs font-bold text-slate-600">Term</label>
            <select name="term_id" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @foreach ($terms as $t)
                    <option value="{{ $t->id }}" @selected($termId == $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @forelse ($grades as $grade)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <h2 class="text-lg font-semibold text-slate-800">
                    {{ $grade->node_name }}
                    <span class="text-sm font-normal text-slate-500">
                        · {{ $grade->students->count() }} unsectioned · {{ $grade->sections->count() }} published section(s)
                    </span>
                </h2>

                @if ($grade->students->isNotEmpty() && $grade->sections->isNotEmpty())
                    <form method="POST" action="{{ route('registrar.sectioning.distribute') }}" class="flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="term_id" value="{{ $termId }}">
                        <input type="hidden" name="education_node_id" value="{{ $grade->node_id }}">
                        <select name="strategy" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="alphabetical">Alphabetical (round-robin)</option>
                            <option value="random">Random (balanced)</option>
                        </select>
                        <button class="rounded-lg bg-slate-700 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Auto-distribute…
                        </button>
                    </form>
                @endif
            </div>

            {{-- Section occupancy --}}
            <div class="grid gap-3 px-5 py-4 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($grade->sections as $sec)
                    <div class="rounded-xl border border-slate-200 px-4 py-3">
                        <p class="font-bold text-slate-800">{{ $sec->name }}</p>
                        <p class="text-xs text-slate-500">
                            {{ $sec->taken }} / {{ $sec->capacity ?: '∞' }} students
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 sm:col-span-2 lg:col-span-4">
                        No published sections for this grade yet — create and publish them on the
                        <a href="{{ route('admission.sections.index', ['term_id' => $termId]) }}" class="text-indigo-600 hover:underline">Sections page</a>.
                    </p>
                @endforelse
            </div>

            {{-- Auto-distribution proposal for this grade --}}
            @if ($proposal && $proposalNode === $grade->node_id && $proposal['placements'] !== [])
                <div class="mx-5 mb-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                    <p class="mb-2 text-sm font-semibold text-indigo-900">
                        Proposed placement ({{ $proposal['strategy'] }}) — review, then apply:
                    </p>
                    <form method="POST" action="{{ route('registrar.sectioning.assign') }}">
                        @csrf
                        <input type="hidden" name="term_id" value="{{ $termId }}">
                        <div class="mb-3 grid gap-1 sm:grid-cols-2">
                            @foreach ($proposal['placements'] as $enrollmentId => $sectionId)
                                @php($stu = $grade->students->firstWhere('enrollment_id', $enrollmentId))
                                @php($sec = $grade->sections->firstWhere('id', $sectionId))
                                <div class="text-sm text-slate-700">
                                    {{ $stu ? $stu->last_name.', '.$stu->first_name : "Enrollment #{$enrollmentId}" }}
                                    <span class="text-slate-400">→</span>
                                    <span class="font-semibold">{{ $sec->name ?? "Section #{$sectionId}" }}</span>
                                    <input type="hidden" name="placements[{{ $enrollmentId }}]" value="{{ $sectionId }}">
                                </div>
                            @endforeach
                        </div>
                        @if ($proposal['leftover'] !== [])
                            <p class="mb-2 text-sm text-rose-700">
                                {{ count($proposal['leftover']) }} student(s) could not be placed — all sections are full.
                            </p>
                        @endif
                        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Apply {{ count($proposal['placements']) }} placement(s)
                        </button>
                    </form>
                </div>
            @endif

            {{-- Unsectioned students + manual bulk assign --}}
            @if ($grade->students->isEmpty())
                <p class="border-t border-slate-100 px-5 py-4 text-sm text-emerald-700">
                    Every enrolled student of this grade has a section. 🎉
                </p>
            @else
                <form method="POST" action="{{ route('registrar.sectioning.assign') }}" class="border-t border-slate-100">
                    @csrf
                    <input type="hidden" name="term_id" value="{{ $termId }}">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-slate-500">
                                <tr>
                                    <th class="px-5 py-2 w-8"></th>
                                    <th class="px-5 py-2">Student</th>
                                    <th class="px-5 py-2">Enrollment Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grade->students as $stu)
                                    <tr class="border-t border-slate-100">
                                        <td class="px-5 py-2">
                                            <input type="checkbox" name="enrollment_ids[]" value="{{ $stu->enrollment_id }}" checked>
                                        </td>
                                        <td class="px-5 py-2 font-medium text-slate-800">{{ $stu->last_name }}, {{ $stu->first_name }}</td>
                                        <td class="px-5 py-2 text-slate-500">{{ str_replace('_', ' ', $stu->status) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($grade->sections->isNotEmpty())
                        <div class="flex items-center gap-2 border-t border-slate-100 px-5 py-4">
                            <label class="text-xs font-bold text-slate-600">Assign checked to</label>
                            <select name="section_id" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @foreach ($grade->sections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }} ({{ $sec->taken }}/{{ $sec->capacity ?: '∞' }})</option>
                                @endforeach
                            </select>
                            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Assign
                            </button>
                        </div>
                    @endif
                </form>
            @endif
        </div>
    @empty
        <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">
            No basic-ed enrollments or grade sections for this term.
        </div>
    @endforelse
</div>
@endsection
