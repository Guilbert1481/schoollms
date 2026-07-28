{{-- Form 137 — Learner's Permanent Academic Record (basic education).
     The basic-ed counterpart of the Transcript of Records: grouped by GRADE
     LEVEL instead of Year/Semester, learning areas with recorded final grades,
     a simple-mean General Average, and a Promoted/Retained remark.
     Shared (read-only) by the student view and the registrar viewer. --}}

@extends('layouts.app')

@section('content')
@php
    $toneBadge = [
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'rose'    => 'bg-rose-100 text-rose-700',
        'amber'   => 'bg-amber-100 text-amber-700',
        'slate'   => 'bg-slate-100 text-slate-600',
    ];
    $toneText = [
        'emerald' => 'text-emerald-600',
        'rose'    => 'text-rose-600',
        'amber'   => 'text-amber-600',
        'slate'   => 'text-slate-400',
    ];
    $backUrl   = $backUrl   ?? route('student.dashboard');
    $backLabel = $backLabel ?? 'Back';
    $editable  = $editable  ?? false;   // registrar view = editable; student view = read-only
    $studentName = trim(($student->first_name ?? '').' '.($student->middle_name ?? '').' '.($student->last_name ?? '').' '.($student->suffix ?? ''));
@endphp

<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ $backLabel }}
        </a>
    </div>

    <div class="rounded-2xl bg-gradient-to-br from-indigo-600 via-blue-700 to-sky-800 p-6 md:p-8 text-white shadow-lg">
        <div class="flex items-center gap-3">
            <i data-lucide="scroll-text" class="w-8 h-8"></i>
            <div>
                <h1 class="text-2xl md:text-3xl font-bold">Form 137 — Permanent Academic Record</h1>
                <p class="text-sm md:text-base text-indigo-100 mt-1">
                    {{ $studentName ?: 'Learner' }}
                    @if(!empty($student->lrn)) &middot; LRN {{ $student->lrn }} @endif
                    @if(!empty($student->student_number)) &middot; {{ $student->student_number }} @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Level</div>
            <div class="mt-2 text-xl font-bold text-slate-900">{{ $summary['level'] }}</div>
            <div class="mt-1 text-xs text-slate-500">Curriculum track</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Current Grade Level</div>
            <div class="mt-2 text-xl font-bold text-slate-900">{{ $summary['current_grade'] ?? '—' }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ $summary['grade_levels'] }} grade level{{ $summary['grade_levels'] === 1 ? '' : 's' }} on record</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">General Average</div>
            <div class="mt-2 text-xl font-bold text-slate-900">
                {{ $summary['general_average'] !== null ? number_format($summary['general_average'], 2) : '—' }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Latest grade level</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Standing</div>
            <div class="mt-2">
                @php $rt = $summary['remark'] === 'Promoted' ? 'emerald' : ($summary['remark'] === 'Retained' ? 'rose' : 'slate'); @endphp
                <span class="inline-block rounded-full px-3 py-1 text-sm font-bold {{ $toneBadge[$rt] }}">
                    {{ $summary['remark'] ?? '—' }}
                </span>
            </div>
            <div class="mt-1 text-xs text-slate-500">Latest grade level</div>
        </div>
    </div>

    {{-- Grade-level sections (newest first) --}}
    @forelse ($sections as $section)
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 bg-slate-50 border-b border-slate-200">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">
                    {{ $section['grade_label'] }}
                    <span class="ml-1 font-semibold text-slate-400 normal-case">· {{ $section['sy_label'] }}</span>
                </h2>
                <span class="inline-block rounded-full px-3 py-0.5 text-xs font-bold {{ $toneBadge[$section['remark_tone']] }}">
                    {{ $section['remark'] }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm record-resizable" style="table-layout: fixed;">
                    {{-- One shared data-table key ("form137rec") across every grade-level
                         section so a single drag resizes that column everywhere. --}}
                    <colgroup>
                        <col data-table="form137rec" data-col-key="code"    style="width: 110px;">
                        <col data-table="form137rec" data-col-key="area">
                        <col data-table="form137rec" data-col-key="teacher" style="width: 150px;">
                        <col data-table="form137rec" data-col-key="grade"   style="width: 110px;">
                        <col data-table="form137rec" data-col-key="remarks" style="width: 110px;">
                        @if($editable)<col data-table="form137rec" data-col-key="action" style="width: 90px;">@endif
                    </colgroup>
                    <thead class="bg-white text-slate-600">
                        <tr class="border-b border-slate-100">
                            <th class="px-5 py-2 text-left font-semibold relative" data-table="form137rec" data-column="code">Code<div class="col-resizer" data-table="form137rec" data-col-key="code" title="Drag to resize · double-click to fit"><span class="col-resizer-line"></span></div></th>
                            <th class="px-5 py-2 text-left font-semibold relative" data-table="form137rec" data-column="area">Learning Area<div class="col-resizer" data-table="form137rec" data-col-key="area" title="Drag to resize · double-click to fit"><span class="col-resizer-line"></span></div></th>
                            <th class="px-5 py-2 text-left font-semibold relative" data-table="form137rec" data-column="teacher">Teacher<div class="col-resizer" data-table="form137rec" data-col-key="teacher" title="Drag to resize · double-click to fit"><span class="col-resizer-line"></span></div></th>
                            <th class="px-5 py-2 text-center font-semibold relative" data-table="form137rec" data-column="grade">Final Grade<div class="col-resizer" data-table="form137rec" data-col-key="grade" title="Drag to resize · double-click to fit"><span class="col-resizer-line"></span></div></th>
                            <th class="px-5 py-2 text-center font-semibold relative" data-table="form137rec" data-column="remarks">Remarks<div class="col-resizer" data-table="form137rec" data-col-key="remarks" title="Drag to resize · double-click to fit"><span class="col-resizer-line"></span></div></th>
                            @if($editable)<th class="px-5 py-2 text-center font-semibold">Action</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($section['rows'] as $r)
                            <tr class="border-t border-slate-50">
                                <td class="px-5 py-2 font-mono text-slate-500" data-table="form137rec" data-column="code">{{ $r['code'] ?? '—' }}</td>
                                <td class="px-5 py-2 text-slate-800" data-table="form137rec" data-column="area">
                                    {{ $r['learning_area'] }}
                                    @if(!empty($r['transferred_from']))
                                        <div class="text-[11px] font-semibold text-sky-600 mt-0.5">
                                            <i data-lucide="repeat" class="inline w-3 h-3"></i> {{ $r['transferred_from'] }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-2 text-slate-500" data-table="form137rec" data-column="teacher">{{ $r['teacher'] ?? '—' }}</td>
                                <td class="px-5 py-2 text-center font-bold {{ $toneText[$r['tone']] }}" data-table="form137rec" data-column="grade">{{ $r['final_grade'] }}</td>
                                <td class="px-5 py-2 text-center" data-table="form137rec" data-column="remarks">
                                    <span class="inline-block rounded-full px-2 py-0.5 text-[11px] font-bold {{ $toneBadge[$r['tone']] }}">
                                        {{ $r['remark'] }}
                                    </span>
                                </td>
                                @if($editable)
                                    <td class="px-5 py-2 text-center">
                                        <button type="button"
                                                onclick="openForm137Edit(this)"
                                                data-subject-id="{{ $r['subject_id'] }}"
                                                data-node-id="{{ $r['education_node_id'] }}"
                                                data-code="{{ e($r['code'] ?? '') }}"
                                                data-name="{{ e($r['learning_area']) }}"
                                                data-grade="{{ $r['grade_raw'] ?? '' }}"
                                                data-status="{{ $r['status_raw'] }}"
                                                data-teacher="{{ e($r['teacher_raw'] ?? '') }}"
                                                data-from="{{ e(str_replace('Transferred from ', '', $r['transferred_from'] ?? '')) }}"
                                                class="px-3 py-1 rounded text-xs font-semibold bg-blue-500 text-white hover:bg-blue-600">
                                            Edit
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        {{-- One cell per column (no colspan) so the hideable
                             Teacher column stays aligned. --}}
                        <tr class="border-t border-slate-200 bg-slate-50">
                            <td class="px-5 py-2.5"></td>
                            <td class="px-5 py-2.5 font-bold text-slate-700">General Average</td>
                            <td class="px-5 py-2.5"></td>
                            <td class="px-5 py-2.5 text-center font-black text-slate-900">{{ $section['ga_display'] }}</td>
                            <td class="px-5 py-2.5"></td>
                            @if($editable)<td class="px-5 py-2.5"></td>@endif
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-slate-500">
            No academic record on file yet.
        </div>
    @endforelse
</div>

{{-- Report Card (current school year) below the Form 137 — registrar only:
     staff record quarterly grades through its edit modal. Students already
     see the read-only card under Academics → Grades, so it is not repeated
     on their transcript. --}}
@includeWhen($editable, 'partials.report-card')

@if($editable)
    {{-- Registrar edit modal — record a learning area's final grade for this
         grade level (writes the permanent record). Works for any grade level,
         including a transferee's past grades from another school: mark Credit
         and note the source school, the school year, and the teacher. --}}
    <div id="form137EditModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
        <form method="POST" action="{{ route('registrar.transcripts.form137-grade', $student->id) }}"
              class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            @csrf
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">Edit Learning Area</h3>
                <button type="button" onclick="closeForm137Edit()" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-5 space-y-4 text-sm">
                <input type="hidden" name="subject_id" id="f137_subject_id">
                <input type="hidden" name="education_node_id" id="f137_node_id">

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Learning Area</label>
                    <div id="f137_label" class="text-slate-800 font-semibold"></div>
                </div>

                <div>
                    <label for="f137_status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Status</label>
                    <select name="new_status" id="f137_status" required onchange="toggleF137Transfer()"
                            class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="passed">Passed</option>
                        <option value="failed">Failed</option>
                        <option value="credit">Credit (Transferred from another school)</option>
                        <option value="enrolled">Ongoing</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="f137_grade" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">
                            Final Grade <span class="text-slate-400 normal-case">(opt.)</span>
                        </label>
                        <input type="number" step="0.01" min="0" max="100" name="new_grade" id="f137_grade"
                               class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="f137_sy" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">
                            School Year <span class="text-slate-400 normal-case">(opt.)</span>
                        </label>
                        <input type="text" name="school_year" id="f137_sy" placeholder="e.g. 2024-2025"
                               class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label for="f137_teacher" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">
                        Teacher <span class="text-slate-400 normal-case">(optional)</span>
                    </label>
                    <input type="text" name="teacher_name" id="f137_teacher" placeholder="Adviser / subject teacher"
                           class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div id="f137_transfer_wrap" class="hidden">
                    <label for="f137_from" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Transferred From (School)</label>
                    <input type="text" name="transferred_from" id="f137_from" placeholder="e.g. San Isidro Elementary School"
                           class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeForm137Edit()" class="px-4 py-2 rounded text-sm bg-white border border-slate-300 text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded text-sm bg-indigo-600 text-white hover:bg-indigo-700 font-semibold">Save Grade</button>
            </div>
        </form>
    </div>

    <script>
        function openForm137Edit(btn) {
            var d = btn.dataset;
            document.getElementById('f137_subject_id').value = d.subjectId;
            document.getElementById('f137_node_id').value = d.nodeId || '';
            document.getElementById('f137_label').textContent = (d.code ? d.code + ' — ' : '') + d.name;
            document.getElementById('f137_grade').value = d.grade || '';
            document.getElementById('f137_teacher').value = d.teacher || '';
            document.getElementById('f137_sy').value = '';
            var s = (d.status || '').toLowerCase();
            document.getElementById('f137_status').value =
                (s === 'credit' || s === 'credited' || s === 'transferred') ? 'credit'
                : (s === 'passed' ? 'passed' : (s === 'failed' ? 'failed' : 'enrolled'));
            document.getElementById('f137_from').value = d.from || '';
            toggleF137Transfer();
            document.getElementById('form137EditModal').classList.remove('hidden');
        }
        function closeForm137Edit() {
            document.getElementById('form137EditModal').classList.add('hidden');
        }
        function toggleF137Transfer() {
            var isCredit = document.getElementById('f137_status').value === 'credit';
            document.getElementById('f137_transfer_wrap').classList.toggle('hidden', !isCredit);
        }
    </script>
@endif

@include('partials.hideable-teacher')
@include('partials.resizable-record-cols')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
