@extends('layouts.app')

@section('page-title', 'Record OMR Answers')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Record OMR Answers</h1>
        <p class="text-sm text-slate-500">
            {{ $test->title }} — shade/confirm each student's answers, then record. Camera scanning comes later;
            for now marks are entered manually.
        </p>
    </div>

    {{-- Scoped, build-independent styles. --}}
    <style>
        .omr-rec .card { background:#fff; border:1px solid #e6ebf3; border-radius:14px; padding:18px 20px; box-shadow:0 8px 22px rgba(0,0,0,.04); }
        .omr-rec label.lbl { display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; }
        .omr-rec select { width:100%; max-width:420px; padding:8px 10px; border:1px solid #d1d5db; border-radius:8px; background:#fff; font-size:14px; }
        .omr-rec .rec-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(190px, 1fr)); gap:8px 22px; margin-top:14px; }
        .omr-rec .rec-row { display:flex; align-items:center; gap:8px; font-size:13px; }
        .omr-rec .rec-row .n { width:26px; text-align:right; font-weight:700; color:#334155; }
        .omr-rec .opt { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border:1.4px solid #cbd5e1; border-radius:50%; cursor:pointer; font-size:12px; color:#475569; user-select:none; }
        .omr-rec .opt.on { background:#1d4ed8; border-color:#1d4ed8; color:#fff; }
        .omr-rec .btn { padding:9px 18px; border:0; border-radius:9px; font-weight:700; cursor:pointer; }
        .omr-rec .btn-primary { background:#2563eb; color:#fff; }
        .omr-rec .btn-primary:disabled { opacity:.5; cursor:not-allowed; }
        .omr-rec .res { margin-top:12px; font-size:14px; }
        .omr-rec .res b { font-size:20px; }
        .omr-rec .muted { color:#94a3b8; font-size:13px; }
        .omr-rec .pill { display:inline-block; font-size:11px; font-weight:700; padding:2px 8px; border-radius:999px; }
        .omr-rec .pill.graded { background:#dcfce7; color:#166534; }
        .omr-rec .pill.pending { background:#fef9c3; color:#854d0e; }
    </style>

    <div class="omr-rec space-y-4">

        {{-- Section picker --}}
        <div class="card">
            <form method="GET" action="{{ route('teacher.tests.omr.record', $test) }}">
                <label class="lbl" for="section">Section</label>
                <select id="section" name="section_id" onchange="this.form.submit()">
                    <option value="">— Select a section —</option>
                    @foreach ($sections as $sec)
                        <option value="{{ $sec->id }}" @selected($sectionId == $sec->id)>
                            {{ $sec->name }}@if($sec->year_level) (Year {{ $sec->year_level }})@endif — {{ $sec->student_count }} student(s)
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        @if ($section)
            <div class="card">
                <label class="lbl" for="student">Student</label>
                <select id="student">
                    <option value="">— Select a student —</option>
                    @foreach ($roster as $i => $r)
                        <option value="{{ $i }}">{{ $r['name'] }} ({{ $r['student_number'] }})</option>
                    @endforeach
                </select>

                <div id="entry" style="display:none; margin-top:16px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div class="muted" id="entryHint">Tap A–E per item. Leaving a row empty records it as blank; two marks record as multiple.</div>
                        <span id="statusPill"></span>
                    </div>
                    <div class="rec-grid" id="recGrid"></div>
                    <div style="margin-top:16px; display:flex; align-items:center; gap:12px;">
                        <button type="button" class="btn btn-primary" id="recordBtn">Record answers</button>
                        <span class="res" id="recResult"></span>
                    </div>
                </div>
            </div>
        @elseif ($sectionId)
            <div class="card muted">No enrolled students found for this section.</div>
        @endif
    </div>
</div>

@if ($section)
<script>
(function () {
    const ROSTER   = @json($roster);
    const SCAN_URL = @json(route('teacher.tests.omr.scan'));
    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content;
    const LETTERS  = ['A', 'B', 'C', 'D', 'E'];

    const studentSel = document.getElementById('student');
    const entry      = document.getElementById('entry');
    const grid       = document.getElementById('recGrid');
    const btn        = document.getElementById('recordBtn');
    const resultEl   = document.getElementById('recResult');
    const pill       = document.getElementById('statusPill');

    let current = null; // selected roster entry

    function renderGrid(r) {
        grid.innerHTML = '';
        for (let n = 1; n <= r.item_count; n++) {
            const marks = (r.marks && r.marks[n]) ? r.marks[n] : [];
            const row = document.createElement('div');
            row.className = 'rec-row';
            row.innerHTML = '<span class="n">' + n + '.</span>' +
                LETTERS.map(l =>
                    '<span class="opt' + (marks.includes(l) ? ' on' : '') + '" data-n="' + n + '" data-l="' + l + '">' + l + '</span>'
                ).join('');
            grid.appendChild(row);
        }
        pill.innerHTML = r.graded
            ? '<span class="pill graded">Recorded · ' + r.raw_score + '/' + r.max_score + ' (' + r.percentage + '%)</span>'
            : '<span class="pill pending">Not yet recorded</span>';
        resultEl.textContent = '';
    }

    grid.addEventListener('click', (e) => {
        const opt = e.target.closest('.opt');
        if (opt) opt.classList.toggle('on');
    });

    studentSel.addEventListener('change', () => {
        const i = studentSel.value;
        if (i === '') { entry.style.display = 'none'; current = null; return; }
        current = ROSTER[i];
        entry.style.display = '';
        renderGrid(current);
    });

    function collectMarks(r) {
        const answers = [];
        for (let n = 1; n <= r.item_count; n++) {
            const marks = Array.from(grid.querySelectorAll('.opt.on[data-n="' + n + '"]')).map(el => el.dataset.l);
            answers.push({ n: n, marks: marks });
        }
        return answers;
    }

    async function submit(allowRescan) {
        if (!current) return;
        btn.disabled = true;
        resultEl.textContent = 'Recording…';
        try {
            const res = await fetch(SCAN_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    sheet_token: current.sheet_token,
                    marked_answers: collectMarks(current),
                    source: 'manual',
                    allow_rescan: !!allowRescan,
                }),
            });
            const data = await res.json();

            if (res.status === 409 && data.error === 'already_scanned') {
                btn.disabled = false;
                if (confirm('This student already has a recorded result. Re-record and replace it?')) {
                    return submit(true);
                }
                resultEl.textContent = 'Kept the existing result.';
                return;
            }

            if (!res.ok || !data.ok) {
                resultEl.textContent = '⚠ ' + (data.error || data.message || 'Failed to record.');
                btn.disabled = false;
                return;
            }

            const s = data.result;
            resultEl.innerHTML = 'Recorded: <b>' + s.raw_score + '/' + s.max_score + '</b> (' + s.percentage + '%) · ' +
                s.correct_count + ' correct, ' + s.incorrect_count + ' wrong, ' + s.blank_count + ' blank, ' + s.multiple_count + ' multiple.';
            pill.innerHTML = '<span class="pill graded">Recorded · ' + s.raw_score + '/' + s.max_score + ' (' + s.percentage + '%)</span>';
            current.graded = true; current.raw_score = s.raw_score; current.max_score = s.max_score; current.percentage = s.percentage;
        } catch (err) {
            resultEl.textContent = '⚠ Network error while recording.';
        } finally {
            btn.disabled = false;
        }
    }

    btn.addEventListener('click', () => submit(false));
})();
</script>
@endif
@endsection
