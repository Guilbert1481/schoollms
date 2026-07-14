@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('registrar.transcripts.index') }}"
               class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    {{ trim($student->first_name.' '.$student->middle_name.' '.$student->last_name) }}
                </h1>
                <p class="text-sm text-slate-500">
                    <span class="font-semibold">{{ $student->student_number ?? '—' }}</span>
                    &middot; {{ $programLabel }}
                </p>
                @if($isTransfereeOrShifter)
                    <span class="inline-flex items-center gap-1 mt-2 px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-700 border border-blue-200">
                        <i data-lucide="repeat" class="w-3 h-3"></i>
                        Transferee / Shifter — direct credit editing enabled
                    </span>
                @endif
            </div>
        </div>

        {{-- Summary --}}
        <div class="flex flex-wrap gap-3 text-sm">
            <div class="px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200">
                <div class="text-emerald-700 font-semibold">{{ $totalUnits }}/{{ $requiredUnits }} units</div>
                <div class="text-xs text-emerald-600">{{ $percentDone }}% complete</div>
            </div>
            <div class="px-3 py-2 rounded-lg bg-indigo-50 border border-indigo-200">
                <div class="text-indigo-700 font-semibold">GWA: {{ $gwa ?? '—' }}</div>
                <div class="text-xs text-indigo-600">Weighted average</div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @forelse($groups as $year => $semBuckets)
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Year {{ $year }}</h2>
            </div>

            @foreach($semBuckets as $sem => $semRows)
                <div class="border-b border-slate-100 last:border-b-0">
                    <div class="px-5 py-2 bg-white">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-indigo-600">
                            {{ $semRows->first()->semester_label ?? ('Sem '.$sem) }}
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm table-fixed">
                            <colgroup>
                                <col style="width: 150px;">
                                <col>
                                <col style="width: 150px;">
                                <col style="width: 70px;">
                                <col style="width: 100px;">
                                <col style="width: 110px;">
                                <col style="width: 120px;">
                            </colgroup>
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold">Subject Code</th>
                                    <th class="px-4 py-2 text-left font-semibold">Descriptive Title</th>
                                    <th class="px-4 py-2 text-left font-semibold">Teacher</th>
                                    <th class="px-4 py-2 text-center font-semibold">Units</th>
                                    <th class="px-4 py-2 text-center font-semibold">Final Grade</th>
                                    <th class="px-4 py-2 text-center font-semibold">Status</th>
                                    <th class="px-4 py-2 text-center font-semibold">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($semRows as $r)
                                    @php $pending = $pendingBySubject->get($r->subject_id); @endphp
                                    <tr class="border-t border-slate-100">
                                        <td class="px-4 py-2 font-mono text-slate-700">{{ $r->subject_code }}</td>
                                        <td class="px-4 py-2 text-slate-800">{{ $r->descriptive_title }}</td>
                                        <td class="px-4 py-2 text-slate-500">{{ $r->teacher ?? '—' }}</td>
                                        <td class="px-4 py-2 text-center text-slate-700">{{ $r->units }}</td>
                                        <td class="px-4 py-2 text-center font-semibold
                                            @if($r->_is_credit) text-blue-600
                                            @elseif($r->status_label === 'Failed') text-red-600
                                            @elseif($r->_is_passed) text-emerald-600
                                            @else text-slate-400 @endif">
                                            {{ $r->final_grade }}
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold
                                                @if($r->_is_credit) bg-blue-100 text-blue-700
                                                @elseif($r->status_label === 'Failed') bg-red-100 text-red-700
                                                @elseif($r->_is_passed) bg-emerald-100 text-emerald-700
                                                @elseif($r->status_label === 'Ongoing') bg-yellow-100 text-yellow-700
                                                @else bg-slate-100 text-slate-600 @endif">
                                                {{ $r->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            @if($pending)
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700">
                                                    <i data-lucide="clock" class="w-3 h-3"></i>
                                                    Pending approval
                                                </span>
                                            @else
                                                <button type="button"
                                                        onclick="openEditTranscriptModal({{ $r->subject_id }}, {{ $r->enrollment_subject_id ?? 'null' }}, '{{ addslashes($r->subject_code) }}', '{{ addslashes($r->descriptive_title) }}', {{ $r->final_grade_raw !== null ? $r->final_grade_raw : 'null' }})"
                                                        class="px-3 py-1 rounded text-xs font-semibold bg-blue-500 text-white hover:bg-blue-600">
                                                    Edit
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-4 py-3 text-center text-slate-400 italic text-xs">No subjects in this term.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-slate-500">
            No curriculum found for this student.
        </div>
    @endforelse
</div>

{{-- Edit Grade Modal --}}
<div id="editTranscriptModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
    <form method="POST"
          action="{{ route('registrar.transcripts.credit-edits.apply', $student->id) }}"
          class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        @csrf
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Edit Transcript Subject</h3>
            <button type="button" onclick="closeEditTranscriptModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-5 space-y-4 text-sm">
            @if($isTransfereeOrShifter)
                <div class="rounded-lg bg-blue-50 border border-blue-200 px-3 py-2 text-blue-800 text-xs">
                    <strong>Transferee / Shifter:</strong> Use status <em>Credit</em> for subjects credited from another program.
                </div>
            @else
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-emerald-800 text-xs">
                    <strong>Registrar Edit:</strong> Changes apply immediately to the official transcript.
                </div>
            @endif
            <input type="hidden" name="latest_enrollment_id" value="{{ $latestEnrollmentId ?? '' }}">

            <input type="hidden" name="subject_id" id="etr_subject_id">
            <input type="hidden" name="enrollment_subject_id" id="etr_enrollment_subject_id">

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Subject</label>
                <div id="etr_subject_label" class="text-slate-800 font-semibold"></div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Current Grade</label>
                <div id="etr_current_grade" class="text-slate-700"></div>
            </div>

            <div>
                <label for="etr_new_status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Status</label>
                <select name="new_status"
                        id="etr_new_status"
                        required
                        class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="passed">Passed</option>
                    <option value="failed">Failed</option>
                    <option value="credit">Credit (Transferred / Credited)</option>
                    <option value="enrolled">Ongoing</option>
                </select>
            </div>

            <div>
                <label for="etr_new_grade" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">
                    Final Grade <span class="text-slate-400 normal-case">(optional for Credit / Ongoing)</span>
                </label>
                <input type="number"
                       step="0.01"
                       min="0"
                       max="100"
                       name="new_grade"
                       id="etr_new_grade"
                       class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label for="etr_reason" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Reason / Justification</label>
                <textarea name="reason"
                          id="etr_reason"
                          rows="3"
                          class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                          placeholder="Why is this edit needed?"></textarea>
            </div>
        </div>
        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
            <button type="button" onclick="closeEditTranscriptModal()" class="px-4 py-2 rounded text-sm bg-white border border-slate-300 text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <button type="submit" class="px-4 py-2 rounded text-sm bg-indigo-600 text-white hover:bg-indigo-700 font-semibold">
                Apply Changes
            </button>
        </div>
    </form>
</div>

<script>
    function openEditTranscriptModal(subjectId, enrollmentSubjectId, code, name, currentGrade) {
        document.getElementById('etr_subject_id').value = subjectId;
        document.getElementById('etr_enrollment_subject_id').value = enrollmentSubjectId ?? '';
        document.getElementById('etr_subject_label').textContent = code + ' — ' + name;
        document.getElementById('etr_current_grade').textContent = currentGrade !== null && currentGrade !== undefined ? currentGrade : '—';
        document.getElementById('etr_new_grade').value = currentGrade ?? '';
        document.getElementById('etr_reason').value = '';
        document.getElementById('editTranscriptModal').classList.remove('hidden');
    }
    function closeEditTranscriptModal() {
        document.getElementById('editTranscriptModal').classList.add('hidden');
    }
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });
</script>

{{-- Report Card (current term) below the transcript --}}
@include('partials.report-card')

@include('partials.hideable-teacher')
@endsection
