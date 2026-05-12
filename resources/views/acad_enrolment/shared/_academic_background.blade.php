{{--
    Shared academic-background form body.
    Inputs:
        $action       POST URL
        $backgrounds  Collection<StudentAcademicBackground>
        $stepLabel
        $progressPct
        $backUrl
        $levelOptions array of education_level keys (defaults to basic ed)
--}}
@php
    $backgrounds   = $backgrounds   ?? collect();
    $levelOptions  = $levelOptions  ?? ['kinder','elementary','junior_high','senior_high','undergraduate'];
    $progressPct   = $progressPct   ?? 52;
    $stepLabel     = $stepLabel     ?? null;
    $backUrl       = $backUrl       ?? null;
@endphp

<style>
    .bg-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:20px; }
    .bg-grid { display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    .bg-grid .input-group { display:flex; flex-direction:column; gap:6px; }
    .bg-grid label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .05em; }
    .bg-grid input, .bg-grid select {
        padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;
    }
</style>

@if ($stepLabel)
    <div class="step-indicator">{{ $stepLabel }}</div>
    <div class="progress-bar"><div class="progress-fill" style="width: {{ $progressPct }}%"></div></div>
@endif

<h3 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:8px;">Academic Background</h3>
<p style="color:#64748b;font-size:13px;margin-bottom:24px;">List your previous schools (most recent first). New students may leave this section blank.</p>

<form method="POST" action="{{ $action }}">
    @csrf

    <div id="bg-container" style="display:flex;flex-direction:column;gap:16px;">
        @php $rows = $backgrounds->count() ? $backgrounds : collect([null]); @endphp
        @foreach ($rows as $i => $b)
            <div class="bg-card" data-bg-row="{{ $i }}">
                <div style="display:flex;justify-content:space-between;margin-bottom:14px;">
                    <strong style="font-size:13px;color:#1e293b;">Previous school #{{ $i + 1 }}</strong>
                    <button type="button" class="remove-btn" style="background:transparent;border:none;color:#ef4444;font-weight:600;cursor:pointer;">Remove</button>
                </div>
                <div class="bg-grid">
                    <div class="input-group">
                        <label>Level</label>
                        @php $lvl = old("backgrounds.$i.education_level", $b->education_level ?? ''); @endphp
                        <select name="backgrounds[{{ $i }}][education_level]">
                            <option value="">— Select —</option>
                            @foreach ($levelOptions as $opt)
                                <option value="{{ $opt }}" @selected($lvl === $opt)>{{ ucfirst(str_replace('_',' ',$opt)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="input-group">
                        <label>School Name</label>
                        <input type="text" name="backgrounds[{{ $i }}][school_name]" value="{{ old("backgrounds.$i.school_name", $b->school_name ?? '') }}">
                    </div>
                    <div class="input-group">
                        <label>School Type</label>
                        @php $st = old("backgrounds.$i.school_type", $b->school_type ?? ''); @endphp
                        <select name="backgrounds[{{ $i }}][school_type]">
                            <option value="">—</option>
                            <option value="public"  @selected($st === 'public')>Public</option>
                            <option value="private" @selected($st === 'private')>Private</option>
                            <option value="home"    @selected($st === 'home')>Home School</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Address</label>
                        <input type="text" name="backgrounds[{{ $i }}][school_address]" value="{{ old("backgrounds.$i.school_address", $b->school_address ?? '') }}">
                    </div>
                    <div class="input-group">
                        <label>Last Grade Completed</label>
                        <input type="text" name="backgrounds[{{ $i }}][last_grade_level]" value="{{ old("backgrounds.$i.last_grade_level", $b->last_grade_level ?? '') }}">
                    </div>
                    <div class="input-group">
                        <label>Year Ended</label>
                        <input type="number" min="1950" max="2100" name="backgrounds[{{ $i }}][year_ended]" value="{{ old("backgrounds.$i.year_ended", $b->year_ended ?? '') }}">
                    </div>
                    <div class="input-group">
                        <label>GPA</label>
                        <input type="number" step="0.01" min="0" max="100" name="backgrounds[{{ $i }}][gpa]" value="{{ old("backgrounds.$i.gpa", $b->gpa ?? '') }}">
                    </div>
                    <div class="input-group" style="grid-column: span 2;">
                        <label>Honors / Notes</label>
                        <input type="text" name="backgrounds[{{ $i }}][honors]" value="{{ old("backgrounds.$i.honors", $b->honors ?? '') }}">
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div style="margin-top:14px;">
        <button type="button" id="add-bg" style="background:#f1f5f9;border:1px dashed #cbd5e1;border-radius:10px;padding:14px;font-weight:600;color:#475569;cursor:pointer;width:100%;">
            + Add another previous school
        </button>
    </div>

    <div class="form-footer" style="display:flex;justify-content:space-between;margin-top:32px;padding-top:20px;border-top:1px solid #e2e8f0;">
        @if ($backUrl)
            <a href="{{ $backUrl }}" class="btn btn-back">Back</a>
        @else
            <span></span>
        @endif
        <button type="submit" class="btn btn-next">Next Step</button>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const container = document.getElementById('bg-container');
    const addBtn    = document.getElementById('add-bg');
    if (!container || !addBtn) return;

    addBtn.addEventListener('click', () => {
        const rows = container.querySelectorAll('[data-bg-row]');
        const i    = rows.length;
        const tpl  = rows[0].cloneNode(true);
        tpl.querySelectorAll('input, select').forEach(el => {
            el.name  = el.name.replace(/backgrounds\[\d+\]/, `backgrounds[${i}]`);
            el.value = '';
        });
        tpl.dataset.bgRow = i;
        container.appendChild(tpl);
    });

    container.addEventListener('click', (e) => {
        if (!e.target.matches('.remove-btn')) return;
        const rows = container.querySelectorAll('[data-bg-row]');
        if (rows.length <= 1) return;
        e.target.closest('[data-bg-row]').remove();
    });
})();
</script>
@endpush
