{{-- resources/views/components/gamified-configuration.blade.php

     The ONE shared pre-game configuration screen for every Gamified Quiz game
     (Engineering Constitution §11B). Games render their own game + result
     screens, but the setup shown before play lives here — no per-game forks.

     Usage (each game opts into the controls it supports and drops per-game
     extras into the default slot):

       <x-gamified-configuration
            title="Quiz Tug-of-War" subtitle="…" icon="⚔"
            :is-teacher="$ctx['role'] === 'teacher'"
            :items="[5,10,15,20]" :items-default="10"
            :types="['mcq','identification']"
            :difficulty="true"
            :modes="['solo','opponent','team']" mode-default="team"
            :section="true"
            start-label="Start now">
            (per-game extras go here — team builder, hearts, verb table…)
       &lt;/x-gamified-configuration&gt;

     JS contract (games listen; the component owns the form):
       • event 'gamified-config:start'  detail={items,type,difficulty,mode,section}
         — fired on Start; the game hides this screen and begins.
       • event 'gamified-config:mode'   detail={mode}
         — fired when the mode changes; the game reveals its mode-specific slot.
       • window.GamifiedConfig.read()            → current values
       • window.GamifiedConfig.error(msg)/.clearError()
       • window.GamifiedConfig.loading(bool,label)  → Start button busy state

     Presentation is build-independent (scoped .gconf-* under [data-gconf], not
     the shared Tailwind build) per the project's stale-build rule. --}}

@props([
    'title'        => 'Game Setup',
    'subtitle'     => null,
    'icon'         => '🎮',
    'isTeacher'    => false,
    'items'        => [5, 10, 15, 20], // number-of-items options; [] hides the control
    'itemsCustom'  => true,            // allow a free-input count
    'itemsDefault' => 10,
    'types'        => [],              // allowed question types (canonical); [] hides
    'typeDefault'  => null,
    'difficulty'   => false,          // show the difficulty select
    'modes'        => [],             // ['solo','opponent','team']; [] hides
    'modeDefault'  => null,
    'section'      => false,          // teacher-only section select
    'scoring'      => false,          // teacher-only scoring block (opponent/team)
    'startLabel'   => 'Start',
    'optionsUrl'   => null,           // difficulty-availability + sections feed
])

@php
    // Canonical question_type → human label. Games pass the subset they can
    // actually render; only mcq/true_false/identification are wired today.
    $typeLabels = [
        'mcq'             => 'Multiple Choice',
        'multiple_choice' => 'Multiple Choice',
        'true_false'      => 'True or False',
        'identification'  => 'Identification',
        'mtf'             => 'Modified True/False',
        'fib'             => 'Fill in the Blank',
        'enumeration'     => 'Enumeration',
        'matching'        => 'Matching Type',
    ];
    $itemsList   = array_values(array_filter((array) $items, fn ($n) => (int) $n > 0));
    $typesList   = array_values(array_filter((array) $types, fn ($t) => isset($typeLabels[$t])));
    $modesList   = array_values(array_intersect((array) $modes, ['solo', 'opponent', 'team']));
    $modeLabels  = ['solo' => 'Solo', 'opponent' => 'With opponent', 'team' => 'Team vs Team'];
    $optionsUrl  = $optionsUrl ?? route('tools.games.config-options');
    $typeDefault = $typeDefault ?: ($typesList[0] ?? null);
    $modeDefault = $modeDefault ?: ($modesList[0] ?? null);
@endphp

@once
@verbatim
<style>
    [data-gconf]{ --g-blue:#1f5fd0; --g-blue-deep:#0f3aa0; --g-navy:#0f2545; --g-ink:#1f2a3a; --g-line:#e2e8f2; --g-soft:#eef3fb; }
    [data-gconf] *{ box-sizing:border-box; }
    [data-gconf]{ max-width:640px; margin:0 auto; }
    [data-gconf] .gconf-hidden{ display:none !important; }

    [data-gconf] .gconf-card{ background:#fff; border:1px solid var(--g-line); border-radius:18px; overflow:hidden; box-shadow:0 14px 40px rgba(15,37,69,.10); }
    [data-gconf] .gconf-head{ background:linear-gradient(125deg,var(--g-navy),#1a3b74); color:#fff; padding:26px 26px 22px; text-align:center; }
    [data-gconf] .gconf-badge{ display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.28); border-radius:999px; padding:5px 14px; font-size:20px; line-height:1; }
    [data-gconf] .gconf-title{ font-size:25px; font-weight:900; letter-spacing:.01em; margin:12px 0 3px; }
    [data-gconf] .gconf-sub{ font-size:13px; color:#c6d6f0; max-width:440px; margin:0 auto; }
    [data-gconf] .gconf-body{ padding:20px 24px 24px; }

    [data-gconf] .gconf-scope{ background:var(--g-soft); border:1px solid #d7e3f6; border-radius:12px; padding:10px 14px; margin-bottom:18px; }
    [data-gconf] .gconf-scope-l{ color:var(--g-blue-deep); font-size:10px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }
    [data-gconf] .gconf-scope-v{ color:var(--g-ink); font-size:14px; font-weight:800; margin-top:2px; }
    [data-gconf] .gconf-scope-h{ color:#6b7a90; font-size:11px; margin-top:2px; }

    [data-gconf] .gconf-grid{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    [data-gconf] .gconf-field{ min-width:0; }
    [data-gconf] .gconf-field.gconf-wide{ grid-column:1 / -1; }
    [data-gconf] .gconf-label{ display:block; color:#586a82; font-size:10px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; margin-bottom:5px; }
    [data-gconf] .gconf-sel, [data-gconf] .gconf-num{ width:100%; appearance:none; background:#fff; color:var(--g-ink); border:1.5px solid var(--g-line); border-radius:10px; padding:10px 12px; font-size:14px; font-weight:600; cursor:pointer; }
    [data-gconf] .gconf-num{ cursor:text; }
    [data-gconf] .gconf-sel:focus, [data-gconf] .gconf-num:focus{ outline:none; border-color:var(--g-blue); box-shadow:0 0 0 3px rgba(31,95,208,.15); }

    [data-gconf] .gconf-teacher{ margin-top:14px; border:1px solid #dbe7d8; background:#f3f9f1; border-radius:12px; padding:12px 14px; }
    [data-gconf] .gconf-teacher-t{ display:flex; align-items:center; gap:6px; font-size:10px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:#4b7b45; margin-bottom:10px; }

    [data-gconf] .gconf-slot:not(:empty){ margin-top:16px; }
    [data-gconf] .gconf-error{ margin-top:14px; background:#fff2f0; border:1px solid #f6c6bf; color:#b3261a; border-radius:10px; padding:10px 14px; font-size:13px; }

    [data-gconf] .gconf-start{ margin-top:18px; width:100%; border:0; cursor:pointer; border-radius:12px; padding:15px; font-size:15px; font-weight:900; letter-spacing:.06em; text-transform:uppercase; color:#fff; background:linear-gradient(120deg,var(--g-blue),var(--g-blue-deep)); box-shadow:0 10px 24px rgba(31,95,208,.30); transition:filter .15s; }
    [data-gconf] .gconf-start:hover:not(:disabled){ filter:brightness(1.06); }
    [data-gconf] .gconf-start:disabled{ opacity:.65; cursor:default; }

    @media (max-width:560px){ [data-gconf] .gconf-grid{ grid-template-columns:1fr; } }
</style>
@endverbatim
@endonce

<div data-gconf {{ $attributes }}>
    <div class="gconf-card">
        <div class="gconf-head">
            <span class="gconf-badge">{{ $icon }}</span>
            <div class="gconf-title">{{ $title }}</div>
            @if($subtitle)<div class="gconf-sub">{{ $subtitle }}</div>@endif
        </div>

        <div class="gconf-body">
            <div class="gconf-scope">
                <div class="gconf-scope-l">Practicing</div>
                <div id="gconfScope" class="gconf-scope-v">All subjects</div>
                <div class="gconf-scope-h">Change content from the &#9776; menu (top right).</div>
            </div>

            <div class="gconf-grid">
                @if($itemsList)
                    <div class="gconf-field">
                        <label class="gconf-label" for="gconfItems">Number of items</label>
                        <select id="gconfItems" class="gconf-sel">
                            @foreach($itemsList as $n)
                                <option value="{{ $n }}" @selected((int) $n === (int) $itemsDefault)>{{ $n }}</option>
                            @endforeach
                            @if($itemsCustom)<option value="custom">Custom…</option>@endif
                        </select>
                        @if($itemsCustom)
                            <input id="gconfItemsCustom" class="gconf-num gconf-hidden" type="number" min="1" max="50" placeholder="Enter a number (1–50)" style="margin-top:8px;">
                        @endif
                    </div>
                @endif

                @if($typesList)
                    <div class="gconf-field">
                        <label class="gconf-label" for="gconfType">Question type</label>
                        <select id="gconfType" class="gconf-sel">
                            @foreach($typesList as $t)
                                <option value="{{ $t }}" @selected($t === $typeDefault)>{{ $typeLabels[$t] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($difficulty)
                    <div class="gconf-field">
                        <label class="gconf-label" for="gconfDiff">Difficulty</label>
                        <select id="gconfDiff" class="gconf-sel">
                            <option value="average">Average</option>
                            <option value="advanced" data-optional>Advanced</option>
                            <option value="mixed" selected>Mixed (average &rarr; advanced)</option>
                        </select>
                    </div>
                @endif

                @if($modesList)
                    <div class="gconf-field">
                        <label class="gconf-label" for="gconfMode">Play mode</label>
                        <select id="gconfMode" class="gconf-sel">
                            @foreach($modesList as $m)
                                <option value="{{ $m }}" @selected($m === $modeDefault)>{{ $modeLabels[$m] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            @if($section && $isTeacher)
                <div class="gconf-teacher">
                    <div class="gconf-teacher-t"><span>🔒</span> Teacher only</div>
                    <label class="gconf-label" for="gconfSection">Section</label>
                    <select id="gconfSection" class="gconf-sel"><option value="">All my sections</option></select>
                </div>
            @endif

            @if($scoring && $isTeacher)
                <div class="gconf-teacher">
                    <div class="gconf-teacher-t"><span>🏅</span> Scoring — recitation for the day (teacher only)</div>
                    <div class="gconf-grid">
                        <div class="gconf-field">
                            <label class="gconf-label" for="gconfPoints">Points / correct</label>
                            <input id="gconfPoints" class="gconf-num" type="number" min="0" max="100" value="1">
                        </div>
                        <div class="gconf-field">
                            <label class="gconf-label" for="gconfWinPct">Winner %</label>
                            <input id="gconfWinPct" class="gconf-num" type="number" min="0" max="100" value="100">
                        </div>
                        <div class="gconf-field">
                            <label class="gconf-label" for="gconfLosePct">Non-winner %</label>
                            <input id="gconfLosePct" class="gconf-num" type="number" min="0" max="100" value="85">
                        </div>
                    </div>
                    <div style="font-size:11px;color:#6b7a90;margin-top:8px;">Shown on the result screen. Writing this to the official gradebook ships in a later, reviewed update.</div>
                </div>
            @endif

            {{-- Per-game extras: team builder, hearts, verb table, opponent picker… --}}
            <div class="gconf-slot">{{ $slot }}</div>

            <div id="gconfError" class="gconf-error gconf-hidden"></div>
            <button type="button" id="gconfStart" class="gconf-start">{{ $startLabel }}</button>
        </div>
    </div>
</div>

<script>
(function () {
    const OPTIONS_URL = @json($optionsUrl);
    const el = (id) => document.getElementById(id);

    // ---- scope line follows the shared ☰ menu -------------------------------
    function refreshScope() {
        if (window.GameScope && typeof window.GameScope.summary === 'function') {
            el('gconfScope').textContent = window.GameScope.summary();
        }
    }
    document.addEventListener('gamescope:changed', () => { refreshScope(); loadOptions(); });
    refreshScope();

    // ---- number of items (with optional custom free input) ------------------
    const itemsSel = el('gconfItems');
    const itemsCustom = el('gconfItemsCustom');
    if (itemsSel && itemsCustom) {
        itemsSel.addEventListener('change', () => {
            itemsCustom.classList.toggle('gconf-hidden', itemsSel.value !== 'custom');
            if (itemsSel.value === 'custom') itemsCustom.focus();
        });
    }
    function itemsValue() {
        if (!itemsSel) return null;
        if (itemsSel.value === 'custom') {
            const n = parseInt((itemsCustom && itemsCustom.value) || '', 10);
            return Number.isFinite(n) ? Math.min(50, Math.max(1, n)) : null;
        }
        return parseInt(itemsSel.value, 10);
    }

    // ---- mode changes tell the game to reveal its slot extras ---------------
    const modeSel = el('gconfMode');
    if (modeSel) {
        modeSel.addEventListener('change', () => {
            document.dispatchEvent(new CustomEvent('gamified-config:mode', { detail: { mode: modeSel.value } }));
        });
    }

    // ---- difficulty availability + sections (server-fed) --------------------
    async function loadOptions() {
        const diffSel = el('gconfDiff');
        const sectionSel = el('gconfSection');
        if (!diffSel && !sectionSel) return;
        try {
            const params = new URLSearchParams();
            const typeSel = el('gconfType');
            if (typeSel) params.set('type', typeSel.value);
            const scope = window.GameScope || {};
            ['subject_id', 'topic_id', 'lesson_id', 'competency_id', 'academic_level_id'].forEach(k => {
                if (scope[k]) params.set(k, scope[k]);
            });
            const res = await fetch(OPTIONS_URL + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();

            // Advanced is hidden when the bank has none for this scope/type.
            if (diffSel) {
                const adv = diffSel.querySelector('option[value="advanced"]');
                if (adv) {
                    const available = !!(data.advanced_available);
                    adv.classList.toggle('gconf-hidden', !available);
                    adv.disabled = !available;
                    if (!available && diffSel.value === 'advanced') diffSel.value = 'mixed';
                }
            }
            if (sectionSel && Array.isArray(data.sections)) {
                const keep = sectionSel.value;
                sectionSel.innerHTML = '<option value="">All my sections</option>'
                    + data.sections.map(s => '<option value="' + s.id + '">' + escapeHtml(s.label) + '</option>').join('');
                sectionSel.value = keep;
            }
        } catch (e) { /* non-fatal: fall back to the static options */ }
    }
    // re-check availability when the type changes (identification has no tiers authored, etc.)
    if (el('gconfType')) el('gconfType').addEventListener('change', loadOptions);

    // ---- public API the games use ------------------------------------------
    function read() {
        const intVal = (id, fallback) => {
            const n = el(id) ? parseInt(el(id).value, 10) : NaN;
            return Number.isFinite(n) ? Math.min(100, Math.max(0, n)) : fallback;
        };
        return {
            items:      itemsValue(),
            type:       el('gconfType') ? el('gconfType').value : null,
            difficulty: el('gconfDiff') ? el('gconfDiff').value : null,
            mode:       el('gconfMode') ? el('gconfMode').value : null,
            section:    el('gconfSection') ? (el('gconfSection').value || null) : null,
            // Teacher scoring (display-only; no gradebook write yet).
            scoring:    el('gconfWinPct') ? {
                points:     intVal('gconfPoints', 1),
                winner_pct: intVal('gconfWinPct', 100),
                loser_pct:  intVal('gconfLosePct', 85),
            } : null,
        };
    }
    window.GamifiedConfig = {
        read,
        error(msg) { const e = el('gconfError'); e.textContent = msg; e.classList.remove('gconf-hidden'); },
        clearError() { el('gconfError').classList.add('gconf-hidden'); },
        loading(on, label) {
            const b = el('gconfStart');
            b.disabled = !!on;
            if (on) { b.dataset.label = b.dataset.label || b.textContent; b.textContent = label || 'Loading…'; }
            else if (b.dataset.label) { b.textContent = b.dataset.label; }
        },
    };

    // ---- Start: validate the shared fields, then hand off to the game -------
    el('gconfStart').addEventListener('click', () => {
        window.GamifiedConfig.clearError();
        const cfg = read();
        if (el('gconfItems') && !cfg.items) {
            return window.GamifiedConfig.error('Enter a valid number of items (1–50).');
        }
        document.dispatchEvent(new CustomEvent('gamified-config:start', { detail: cfg }));
    });

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    loadOptions();
})();
</script>
