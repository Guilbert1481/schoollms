@extends('layouts.enrollment')

@section('content')
<div class="step-indicator">STEP 7 OF 9</div>
<div class="progress-bar"><div class="progress-fill" style="width:77%"></div></div>

<h3 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:8px;">Pick Your Classes</h3>
<p style="color:#64748b;font-size:13px;margin-bottom:16px;">
    Tick the classes you want to enrol in. The validation panel on the right updates as you choose,
    so you can see schedule conflicts, prerequisite issues, and unit overloads before you submit.
</p>



<form id="irregular-form" method="POST" action="{{ route('public.apply.higher_irregular.step6.store', $term->id) }}">
    @csrf

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:flex-start;">
        {{-- ---------- LEFT: class catalogue ---------- --}}
        <div>
            @if ($classes->isEmpty())
                <div style="padding:24px;background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;color:#92400e;">
                    @if (empty($draft['curriculum_id']))
                        No active curriculum is on file for the selected program yet, so no classes can be offered. Please choose a different program or contact the registrar.
                    @else
                        No open classes are available for {{ $term->name }}.
                    @endif
                </div>
            @else
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
                @foreach ($classes as $c)
                    @php
                        $checked   = $picked->contains($c->id);
                        $remaining = max(0, ($c->capacity ?? 0) - ($c->students_count ?? 0));
                        $full      = $c->capacity && $remaining <= 0;
                        $m         = $meta[$c->subject_id] ?? null;
                        $subj      = $c->subject;
                        $section   = $c->section;
                    @endphp
                    <label style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 14px;display:flex;flex-direction:column;gap:8px;box-shadow:0 1px 2px 0 #f1f5f9;cursor:pointer;position:relative;min-width:0;">
                        <input type="checkbox" class="irregular-class" name="class_ids[]"
                               value="{{ $c->id }}"
                               data-subject="{{ $c->subject_id }}"
                               data-units="{{ $m->units ?? 3 }}"
                               style="margin-bottom:6px;"
                               @checked($checked) @disabled($full && !$checked)>
                        <div style="font-weight:700;color:#1e293b;font-size:14px;">{{ $c->code }}</div>
                        <div style="font-size:13px;color:#334155;">{{ $subj?->code }} — {{ $subj?->name }}</div>
                        <div style="font-size:12px;color:#64748b;">
                            {{ $c->schedule ?: 'Schedule TBA' }}
                            @if ($c->room) · Room {{ $c->room }} @endif
                        </div>
                        <div style="font-size:12px;color:#64748b;">
                            Section: {{ $section?->name ?? 'N/A' }}
                        </div>
                        @if ($m)
                            <div style="font-size:11px;color:#0e7490;">Year {{ $m->year_level }} · Sem {{ $m->semester }} · {{ $m->units }} units · {{ $m->is_core ? 'Core' : ($m->is_elective ? 'Elective' : 'Other') }}</div>
                        @else
                            <div style="font-size:11px;color:#b91c1c;">Not in curriculum</div>
                        @endif
                        <div style="font-size:11px;color:{{ $full ? '#b91c1c' : '#16a34a' }};font-weight:700;align-self:flex-end;">
                            {{ $full ? 'FULL' : ($remaining.' / '.$c->capacity.' seats') }}
                        </div>
                    </label>
                @endforeach
                </div>
            @endif
        </div>

        {{-- ---------- RIGHT: live validation panel ---------- --}}
        <div style="position:sticky;top:20px;">
            <div id="validation-panel" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px;font-size:13px;">
                <div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">
                    Live Validation
                </div>
                <div id="validation-summary">
                    <div style="color:#64748b;">Pick a class to see live validation results.</div>
                </div>

                <div id="validation-status" style="display:none;margin-top:12px;padding:10px 12px;border-radius:8px;font-weight:700;"></div>

                <div id="validation-failures" style="display:none;margin-top:12px;">
                    <div style="font-size:11px;font-weight:800;color:#991b1b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Errors</div>
                    <ul id="failures-list" style="margin:0 0 0 18px;padding:0;color:#991b1b;"></ul>
                </div>

                <div id="validation-warnings" style="display:none;margin-top:12px;">
                    <div style="font-size:11px;font-weight:800;color:#92400e;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Warnings</div>
                    <ul id="warnings-list" style="margin:0 0 0 18px;padding:0;color:#92400e;"></ul>
                </div>
            </div>
        </div>
    </div>

    <div class="form-footer" style="display:flex;justify-content:space-between;margin-top:32px;padding-top:20px;border-top:1px solid #e2e8f0;">
        <a href="{{ route('public.apply.higher_irregular.step5', $term->id) }}" class="btn btn-back">Back</a>
        <button id="next-btn" type="submit" class="btn btn-next" disabled>Next Step</button>
    </div>
</form>

<script>
    (function () {
        const form        = document.getElementById('irregular-form');
        const checkboxes  = form.querySelectorAll('.irregular-class');
        const summaryEl   = document.getElementById('validation-summary');
        const statusEl    = document.getElementById('validation-status');
        const failuresBox = document.getElementById('validation-failures');
        const failuresUl  = document.getElementById('failures-list');
        const warningsBox = document.getElementById('validation-warnings');
        const warningsUl  = document.getElementById('warnings-list');
        const nextBtn     = document.getElementById('next-btn');
        const csrf        = document.querySelector('meta[name="csrf-token"]')?.content
                            || form.querySelector('input[name="_token"]').value;
        const url         = @json($validateUrl);

        let inflight  = null;
        let debounceT = null;

        function selectedIds() {
            return Array.from(checkboxes).filter(c => c.checked).map(c => parseInt(c.value, 10));
        }

        function render(data) {
            summaryEl.innerHTML =
                '<div style="display:flex;justify-content:space-between;font-size:13px;">' +
                  '<span><strong>'+ data.class_count +'</strong> classes</span>' +
                  '<span><strong>'+ data.total_units +'</strong> units</span>' +
                '</div>' +
                '<div style="margin-top:6px;font-size:12px;color:#64748b;">' +
                  'Approval: <strong style="color:#5a57d6;">'+ String(data.approval).replace(/_/g,' ') +'</strong>' +
                '</div>';

            statusEl.style.display = 'block';
            if (data.passed) {
                statusEl.style.background = '#dcfce7';
                statusEl.style.color      = '#166534';
                statusEl.textContent      = data.has_warnings ? '✓ Can submit (with warnings)' : '✓ All checks passed';
            } else {
                statusEl.style.background = '#fee2e2';
                statusEl.style.color      = '#991b1b';
                statusEl.textContent      = '✗ Fix errors before submitting';
            }

            const renderList = (items, ul, box) => {
                ul.innerHTML = '';
                if (!items || items.length === 0) { box.style.display = 'none'; return; }
                for (const it of items) {
                    const li = document.createElement('li');
                    li.style.marginBottom = '4px';
                    li.textContent = it.message || '';
                    ul.appendChild(li);
                }
                box.style.display = 'block';
            };
            renderList(data.failures, failuresUl, failuresBox);
            renderList(data.warnings, warningsUl, warningsBox);

            nextBtn.disabled = !data.passed || data.class_count === 0;
        }

        function runValidation() {
            const ids = selectedIds();

            if (ids.length === 0) {
                summaryEl.innerHTML = '<div style="color:#64748b;">Pick a class to see live validation results.</div>';
                statusEl.style.display = 'none';
                failuresBox.style.display = 'none';
                warningsBox.style.display = 'none';
                nextBtn.disabled = true;
                return;
            }

            if (inflight) inflight.abort();
            inflight = new AbortController();

            statusEl.style.display = 'block';
            statusEl.style.background = '#e0e7ff';
            statusEl.style.color      = '#3730a3';
            statusEl.textContent      = 'Validating…';

            const body = new URLSearchParams();
            body.append('_token', csrf);
            for (const id of ids) body.append('class_ids[]', id);

            fetch(url, {
                method:  'POST',
                signal:  inflight.signal,
                headers: {
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type':     'application/x-www-form-urlencoded',
                },
                body,
            })
            .then(r => r.ok ? r.json() : r.json().then(j => Promise.reject(j)))
            .then(render)
            .catch(err => {
                if (err.name === 'AbortError') return;
                statusEl.style.background = '#fee2e2';
                statusEl.style.color      = '#991b1b';
                statusEl.textContent      = err?.error || 'Validation request failed.';
                nextBtn.disabled = true;
            });
        }

        function debounced() {
            clearTimeout(debounceT);
            debounceT = setTimeout(runValidation, 250);
        }

        checkboxes.forEach(c => c.addEventListener('change', debounced));
        // Run once on load if anything is pre-checked.
        if (selectedIds().length > 0) runValidation();
    })();
</script>
@endsection
