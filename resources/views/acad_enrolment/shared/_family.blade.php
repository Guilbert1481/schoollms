{{--
    Shared family / emergency contact form body.
    Inputs:
        $action     POST URL
        $parents    Collection<Guardian> (existing parents/guardians)
        $emergency  ?Guardian
        $stepLabel
        $progressPct
        $backUrl
--}}
@php
    $stepLabel   = $stepLabel   ?? null;
    $progressPct = $progressPct ?? 40;
    $backUrl     = $backUrl     ?? null;
    $parents     = $parents     ?? collect();
    $emergency   = $emergency   ?? null;
@endphp

<style>
    .family-section { display: flex; flex-direction: column; gap: 28px; }
    .family-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        padding: 24px;
    }
    .family-card h4 {
        font-size: 13px; font-weight: 800; color: #1e293b;
        text-transform: uppercase; letter-spacing: .05em; margin-bottom: 18px;
        display: flex; justify-content: space-between; align-items: center;
    }
    .family-card .remove-btn {
        background: transparent; border: none; color: #ef4444; cursor: pointer;
        font-size: 12px; font-weight: 600; text-transform: none; letter-spacing: 0;
    }
    .family-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    .family-grid .input-group { display: flex; flex-direction: column; gap: 6px; }
    .family-grid label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .05em; }
    .family-grid input, .family-grid select {
        padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;
    }
    .add-row-btn {
        background: #f1f5f9; border: 1px dashed #cbd5e1; border-radius: 10px;
        padding: 14px; font-weight: 600; color: #475569; cursor: pointer; width: 100%;
    }
    .add-row-btn:hover { background: #e2e8f0; }
</style>

@if ($stepLabel)
    <div class="step-indicator">{{ $stepLabel }}</div>
    <div class="progress-bar"><div class="progress-fill" style="width: {{ $progressPct }}%"></div></div>
@endif

<h3 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:24px;">Family &amp; Emergency Contact</h3>

<form method="POST" action="{{ $action }}">
    @csrf

    <div class="family-section">
        <div>
            <h4 style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">
                Parents / Guardians
            </h4>
            <div id="parents-container" style="display:flex;flex-direction:column;gap:16px;">
                @forelse ($parents as $i => $p)
                    @include('acad_enrolment.shared._parent_row', ['i' => $i, 'p' => $p])
                @empty
                    @include('acad_enrolment.shared._parent_row', ['i' => 0, 'p' => null])
                @endforelse
            </div>
            <div style="margin-top:12px;">
                <button type="button" id="add-parent" class="add-row-btn">+ Add another parent / guardian</button>
            </div>
        </div>

        <div class="family-card">
            <h4>Emergency Contact</h4>
            <div class="family-grid">
                <div class="input-group">
                    <label>First Name*</label>
                    <input type="text" name="emergency[first_name]" required value="{{ old('emergency.first_name', $emergency->first_name ?? '') }}">
                </div>
                <div class="input-group">
                    <label>Last Name*</label>
                    <input type="text" name="emergency[last_name]" required value="{{ old('emergency.last_name', $emergency->last_name ?? '') }}">
                </div>
                <div class="input-group">
                    <label>Relationship*</label>
                    <input type="text" name="emergency[relationship]" required placeholder="Aunt, uncle, etc."
                           value="{{ old('emergency.relationship', $emergency->relationship ?? '') }}">
                </div>
                <div class="input-group">
                    <label>Mobile Number*</label>
                    <input type="text" name="emergency[mobile_number]" required value="{{ old('emergency.mobile_number', $emergency->mobile_number ?? '') }}">
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="emergency[email]" value="{{ old('emergency.email', $emergency->email ?? '') }}">
                </div>
                <div class="input-group">
                    <label>Address</label>
                    <input type="text" name="emergency[address]" value="{{ old('emergency.address', $emergency->address ?? '') }}">
                </div>
            </div>
        </div>
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
    const container = document.getElementById('parents-container');
    const addBtn    = document.getElementById('add-parent');
    if (!container || !addBtn) return;

    addBtn.addEventListener('click', () => {
        const rows = container.querySelectorAll('[data-parent-row]');
        const i    = rows.length;
        const tpl  = rows[0].cloneNode(true);
        tpl.querySelectorAll('input, select').forEach(el => {
            el.name  = el.name.replace(/parents\[\d+\]/, `parents[${i}]`);
            el.value = '';
        });
        tpl.dataset.parentRow = i;
        container.appendChild(tpl);
    });

    container.addEventListener('click', (e) => {
        if (!e.target.matches('.remove-btn')) return;
        const rows = container.querySelectorAll('[data-parent-row]');
        if (rows.length <= 1) return;
        e.target.closest('[data-parent-row]').remove();
    });
})();
</script>
@endpush
