{{-- Report Card — current period snapshot. Basic ed: per grading period for
     the current school year. Higher ed: the current academic year + term.
     Pass $report (ReportCardService output), $student, and $rcEditable
     (registrar may enter per-period basic-ed grades). --}}
@php
    $report     = $report ?? null;
    $rcEditable = ($rcEditable ?? false) && $report && ($report['is_basic'] ?? false);
    $rcBadge = [
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'rose'    => 'bg-rose-100 text-rose-700',
        'sky'     => 'bg-sky-100 text-sky-700',
        'slate'   => 'bg-slate-100 text-slate-600',
    ];
    $rcText = [
        'emerald' => 'text-emerald-600', 'rose' => 'text-rose-600',
        'sky' => 'text-sky-600', 'slate' => 'text-slate-400',
    ];
@endphp

@if($report)
<div class="mx-auto w-full max-w-7xl space-y-4 {{ ($rcStandalone ?? false) ? 'p-4 md:p-6' : 'mt-8' }}">

    <div class="rounded-2xl bg-gradient-to-br from-emerald-600 via-teal-700 to-cyan-800 p-6 text-white shadow-lg">
        <div class="flex items-center gap-3">
            <i data-lucide="clipboard-check" class="w-7 h-7"></i>
            <div>
                <h2 class="text-xl md:text-2xl font-bold">Report Card</h2>
                <p class="text-sm text-emerald-100 mt-0.5">
                    @if($report['is_basic'] ?? false)
                        {{ $report['grade_label'] ?? '' }} &middot; {{ $report['ay_label'] ?? '' }} &middot; Current school year
                    @else
                        {{ $report['ay_label'] ?? '' }} &middot; {{ $report['term_label'] ?? '' }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    @if(! ($report['has_context'] ?? false))
        <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-slate-500">
            No grades for the current period yet.
        </div>
    @elseif($report['is_basic'])
        {{-- Basic ed: learning areas × grading periods --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr class="border-b border-slate-100">
                            <th class="px-5 py-2 text-left font-semibold">Learning Area</th>
                            @foreach($report['periods'] as $i => $p)
                                <th class="px-5 py-2 text-center font-semibold">{{ $report['period_labels'][$i] }}</th>
                            @endforeach
                            <th class="px-5 py-2 text-center font-semibold">Final</th>
                            <th class="px-5 py-2 text-center font-semibold">Remarks</th>
                            @if($rcEditable)<th class="px-5 py-2 text-center font-semibold">Action</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['rows'] as $r)
                            <tr class="border-t border-slate-50">
                                <td class="px-5 py-2 text-slate-800">{{ $r['learning_area'] }}</td>
                                @foreach($report['periods'] as $p)
                                    <td class="px-5 py-2 text-center font-semibold {{ $rcText[$r['cells'][$p]['tone']] }}">{{ $r['cells'][$p]['display'] }}</td>
                                @endforeach
                                <td class="px-5 py-2 text-center font-bold {{ $rcText[$r['final_tone']] }}">{{ $r['final'] }}</td>
                                <td class="px-5 py-2 text-center">
                                    <span class="inline-block rounded-full px-2 py-0.5 text-[11px] font-bold {{ $rcBadge[$r['final_tone']] }}">{{ $r['final_remark'] }}</span>
                                </td>
                                @if($rcEditable)
                                    <td class="px-5 py-2 text-center">
                                        <button type="button" onclick="openReportCardEdit(this)"
                                                data-subject-id="{{ $r['subject_id'] }}"
                                                data-node-id="{{ $r['education_node_id'] }}"
                                                data-name="{{ e($r['learning_area']) }}"
                                                data-g1="{{ $r['cells'][1]['grade_raw'] ?? '' }}"
                                                data-g2="{{ $r['cells'][2]['grade_raw'] ?? '' }}"
                                                data-g3="{{ $r['cells'][3]['grade_raw'] ?? '' }}"
                                                class="px-3 py-1 rounded text-xs font-semibold bg-blue-500 text-white hover:bg-blue-600">Edit</button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-slate-200 bg-slate-50">
                            <td class="px-5 py-2.5 font-bold text-slate-700">General Average</td>
                            @foreach($report['periods'] as $p)
                                <td class="px-5 py-2.5 text-center font-semibold text-slate-600">{{ $report['period_averages'][$p] }}</td>
                            @endforeach
                            <td class="px-5 py-2.5 text-center font-black text-slate-900">{{ $report['ga_display'] }}</td>
                            <td class="px-5 py-2.5 text-center">
                                @php $rt = $report['remark'] === 'Promoted' ? 'emerald' : ($report['remark'] === 'Retained' ? 'rose' : 'slate'); @endphp
                                <span class="inline-block rounded-full px-2 py-0.5 text-[11px] font-bold {{ $rcBadge[$rt] }}">{{ $report['remark'] }}</span>
                            </td>
                            @if($rcEditable)<td></td>@endif
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @else
        {{-- Higher ed: current term subjects --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr class="border-b border-slate-100">
                            <th class="px-5 py-2 text-left font-semibold">Subject</th>
                            <th class="px-5 py-2 text-left font-semibold">Teacher</th>
                            <th class="px-5 py-2 text-center font-semibold">Units</th>
                            <th class="px-5 py-2 text-center font-semibold">Grade</th>
                            <th class="px-5 py-2 text-center font-semibold">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['rows'] as $r)
                            <tr class="border-t border-slate-50">
                                <td class="px-5 py-2 text-slate-800">{{ $r['subject'] }}</td>
                                <td class="px-5 py-2 text-slate-500">{{ $r['teacher'] }}</td>
                                <td class="px-5 py-2 text-center text-slate-700">{{ $r['units'] }}</td>
                                <td class="px-5 py-2 text-center font-bold {{ $rcText[$r['tone']] }}">{{ $r['grade'] }}</td>
                                <td class="px-5 py-2 text-center">
                                    <span class="inline-block rounded-full px-2 py-0.5 text-[11px] font-bold {{ $rcBadge[$r['tone']] }}">{{ $r['remark'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-6 text-center text-slate-400 italic">No subjects enrolled this term.</td></tr>
                        @endforelse
                    </tbody>
                    @if(($report['gwa'] ?? null) !== null)
                        <tfoot>
                            <tr class="border-t border-slate-200 bg-slate-50">
                                <td class="px-5 py-2.5 font-bold text-slate-700" colspan="3">GWA (this term)</td>
                                <td class="px-5 py-2.5 text-center font-black text-slate-900">{{ number_format($report['gwa'], 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif
</div>

@if($rcEditable)
    {{-- Registrar: enter per-grading-period grades for a learning area. --}}
    <div id="reportCardEditModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
        <form method="POST" action="{{ route('registrar.transcripts.report-card-grade', $student->id) }}"
              class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            @csrf
            <input type="hidden" name="academic_year_id" value="{{ $report['academic_year_id'] ?? '' }}">
            <input type="hidden" name="subject_id" id="rc_subject_id">
            <input type="hidden" name="education_node_id" id="rc_node_id">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">Grading Period Marks</h3>
                <button type="button" onclick="closeReportCardEdit()" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-5 space-y-4 text-sm">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Learning Area</label>
                    <div id="rc_label" class="text-slate-800 font-semibold"></div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    @foreach($report['periods'] as $i => $p)
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">{{ $report['period_labels'][$i] }}</label>
                            <input type="number" step="0.01" min="0" max="100" name="grades[{{ $p }}]" id="rc_g{{ $p }}"
                                   class="w-full border border-slate-300 rounded px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    @endforeach
                </div>
                <p class="text-[11px] text-slate-400">Leave a period blank if not yet graded. The Final is the average of the graded periods.</p>
            </div>
            <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeReportCardEdit()" class="px-4 py-2 rounded text-sm bg-white border border-slate-300 text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded text-sm bg-indigo-600 text-white hover:bg-indigo-700 font-semibold">Save</button>
            </div>
        </form>
    </div>
    <script>
        function openReportCardEdit(btn) {
            var d = btn.dataset;
            document.getElementById('rc_subject_id').value = d.subjectId;
            document.getElementById('rc_node_id').value = d.nodeId || '';
            document.getElementById('rc_label').textContent = d.name;
            document.getElementById('rc_g1').value = d.g1 || '';
            document.getElementById('rc_g2').value = d.g2 || '';
            document.getElementById('rc_g3').value = d.g3 || '';
            document.getElementById('reportCardEditModal').classList.remove('hidden');
        }
        function closeReportCardEdit() { document.getElementById('reportCardEditModal').classList.add('hidden'); }
    </script>
@endif

@include('partials.hideable-teacher')
@endif
