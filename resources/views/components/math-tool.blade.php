{{--
    Floating math tool — reusable equation editor / calculator.

    Usage:  <x-math-tool />                    (full: editor + numeric evaluation)
            <x-math-tool :evaluate="false" />  (editor only — no calculator result;
                                                use on student-facing test pages)

    A small floating icon opens a draggable panel with a MathLive field. "Insert"
    writes the expression — converted to plain linear text (x², √(x+1), (a+b)/(c)) —
    into whichever text input/textarea was last focused, so it works with any
    existing form without changing how answers are stored or graded.

    Self-hosted assets only (public/vendor/mathlive, public/vendor/compute-engine):
    no network calls beyond our own origin, so it is safe on test pages.

    Styling is inline/scoped on purpose — the Tailwind build is stale and this
    component must render correctly on every page it is dropped into.
--}}
@props(['evaluate' => true])

<div class="mtool" data-mathtool data-evaluate="{{ $evaluate ? '1' : '0' }}">
    <style>
        .mtool { position: fixed; right: 18px; bottom: 18px; z-index: 4000; font-family: system-ui, sans-serif; }
        .mtool * { box-sizing: border-box; }
        .mtool-fab { width: 44px; height: 44px; border-radius: 50%; border: none; cursor: pointer;
            background: #1e5fa8; color: #fff; box-shadow: 0 4px 14px rgba(15,23,42,.25);
            display: flex; align-items: center; justify-content: center; }
        .mtool-fab:hover { background: #276fbf; }
        .mtool-panel { position: fixed; right: 18px; bottom: 74px; width: 330px; max-width: calc(100vw - 24px);
            background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
            box-shadow: 0 12px 40px rgba(15,23,42,.22); display: none; }
        .mtool-panel.open { display: block; }
        .mtool-head { display: flex; align-items: center; gap: 8px; padding: 9px 12px; cursor: move;
            background: #1c477f; color: #fff; border-radius: 12px 12px 0 0; user-select: none; }
        .mtool-head .t { font-weight: 700; font-size: 13px; flex: 1; }
        .mtool-close { background: none; border: none; color: #fff; font-size: 16px; cursor: pointer; padding: 2px 6px; }
        .mtool-body { padding: 12px; }
        .mtool-body math-field { width: 100%; font-size: 20px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 8px; }
        .mtool-result { min-height: 22px; margin-top: 8px; font-size: 15px; font-weight: 600; color: #16a34a;
            font-variant-numeric: tabular-nums; }
        .mtool-hint { font-size: 11px; color: #64748b; margin-top: 6px; line-height: 1.4; }
        .mtool-actions { display: flex; gap: 8px; margin-top: 10px; }
        .mtool-btn { flex: 1; border: none; border-radius: 8px; padding: 8px 0; font-size: 13px; font-weight: 700; cursor: pointer; }
        .mtool-btn.primary { background: #1e5fa8; color: #fff; }
        .mtool-btn.primary:disabled { background: #94a3b8; cursor: default; }
        .mtool-btn.ghost { background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }
    </style>

    <div class="mtool-panel" data-mtool-panel>
        <div class="mtool-head" data-mtool-drag>
            <span class="t">Math tool</span>
            <button type="button" class="mtool-close" data-mtool-close title="Close">✕</button>
        </div>
        <div class="mtool-body">
            <math-field data-mtool-field></math-field>
            @if ($evaluate)
                <div class="mtool-result" data-mtool-result></div>
            @endif
            <div class="mtool-actions">
                <button type="button" class="mtool-btn primary" data-mtool-insert disabled>Insert</button>
                <button type="button" class="mtool-btn ghost" data-mtool-copy>Copy</button>
                <button type="button" class="mtool-btn ghost" data-mtool-clear>Clear</button>
            </div>
            <div class="mtool-hint" data-mtool-hint>Click into a text box first, then build your expression and press Insert.</div>
        </div>
    </div>

    <button type="button" class="mtool-fab" data-mtool-fab title="Math tool" aria-label="Open math tool">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="4" y="2" width="16" height="20" rx="2"></rect>
            <line x1="8" y1="6" x2="16" y2="6"></line>
            <line x1="8" y1="11" x2="8" y2="11.01"></line>
            <line x1="12" y1="11" x2="12" y2="11.01"></line>
            <line x1="16" y1="11" x2="16" y2="11.01"></line>
            <line x1="8" y1="15" x2="8" y2="15.01"></line>
            <line x1="12" y1="15" x2="12" y2="15.01"></line>
            <line x1="16" y1="15" x2="16" y2="15.01"></line>
            <line x1="8" y1="19" x2="16" y2="19"></line>
        </svg>
    </button>
</div>

<script type="module">
(async () => {
    const root = document.querySelector('[data-mathtool]');
    if (!root || root.dataset.mtoolBooted) return;
    root.dataset.mtoolBooted = '1';

    const { MathfieldElement } = await import(@json(asset('vendor/mathlive/mathlive.min.mjs')));
    MathfieldElement.fontsDirectory = @json(asset('vendor/mathlive/fonts'));
    MathfieldElement.soundsDirectory = null;

    const wantEval = root.dataset.evaluate === '1';
    let ce = null;
    if (wantEval) {
        try {
            const mod = await import(@json(asset('vendor/compute-engine/compute-engine.js')));
            ce = new mod.ComputeEngine();
        } catch (e) { /* evaluation is optional — the editor still works */ }
    }

    const panel = root.querySelector('[data-mtool-panel]');
    const fab = root.querySelector('[data-mtool-fab]');
    const field = root.querySelector('[data-mtool-field]');
    const result = root.querySelector('[data-mtool-result]');
    const insertBtn = root.querySelector('[data-mtool-insert]');
    const hint = root.querySelector('[data-mtool-hint]');

    fab.addEventListener('click', () => {
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) field.focus();
    });
    root.querySelector('[data-mtool-close]').addEventListener('click', () => panel.classList.remove('open'));

    // Remember the last text box the user was in (never our own field).
    let target = null;
    document.addEventListener('focusin', (e) => {
        const el = e.target;
        if (root.contains(el)) return;
        const isText = (el.tagName === 'TEXTAREA')
            || (el.tagName === 'INPUT' && (!el.type || el.type === 'text' || el.type === 'search'));
        if (isText) {
            target = el;
            insertBtn.disabled = false;
            hint.textContent = 'Insert goes into the box you last clicked.';
        }
    });

    // MathLive's ASCII-math output, tidied into classroom-friendly linear text.
    // Token spellings verified against MathLive 0.110 ("-:" division, "@"
    // composition, "root(3)(x)" nth roots, "oo" infinity).
    function linear() {
        let v = field.getValue('ascii-math') || '';
        v = v.replace(/root\(3\)/g, '∛').replace(/root\(4\)/g, '∜')
             .replace(/sqrt/g, '√')
             .replace(/\s*-:\s*/g, ' ÷ ')
             .replace(/\^2(?![0-9])/g, '²').replace(/\^3(?![0-9])/g, '³')
             .replace(/\^\((\d+)\)/g, '^$1')
             .replace(/\bpi\b/g, 'π').replace(/\boo\b/g, '∞')
             .replace(/!=/g, '≠').replace(/<=/g, '≤').replace(/>=/g, '≥')
             .replace(/\+-/g, '±')
             .replace(/@/g, '∘')
             .replace(/\bint\b/g, '∫').replace(/\bsum\b/g, 'Σ').replace(/\bprod\b/g, 'Π')
             .replace(/\*/g, '·')
             .replace(/_(\d)(?![0-9])/g, (m, d) => '₀₁₂₃₄₅₆₇₈₉'[+d]);
        // (x)/(y) with single tokens inside → x/y
        v = v.replace(/\(([0-9a-zA-Zπ²³]+)\)\/\(([0-9a-zA-Zπ²³]+)\)/g, '$1/$2');
        // tidy spacing: no gap before subscripts, single spaces only
        v = v.replace(/\s+(?=[₀-₉])/g, '').replace(/\s{2,}/g, ' ');
        return v.trim();
    }

    field.addEventListener('input', () => {
        if (!result || !ce) return;
        result.textContent = '';
        const latex = field.getValue().trim();
        if (latex === '') return;
        try {
            const n = ce.parse(latex).N().valueOf();
            if (typeof n === 'number' && isFinite(n)) {
                result.textContent = '= ' + (Number.isInteger(n) ? n : +n.toPrecision(10));
            }
        } catch (e) { /* not numerically evaluable — leave the line blank */ }
    });

    insertBtn.addEventListener('click', () => {
        if (!target || !document.body.contains(target)) { insertBtn.disabled = true; return; }
        const text = linear();
        if (text === '') return;
        const start = target.selectionStart ?? target.value.length;
        const end = target.selectionEnd ?? start;
        target.value = target.value.slice(0, start) + text + target.value.slice(end);
        target.dispatchEvent(new Event('input', { bubbles: true }));
        target.dispatchEvent(new Event('change', { bubbles: true }));
        target.focus();
        const pos = start + text.length;
        try { target.setSelectionRange(pos, pos); } catch (e) { /* number inputs etc. */ }
    });

    root.querySelector('[data-mtool-copy]').addEventListener('click', () => {
        const text = linear();
        if (text !== '' && navigator.clipboard) navigator.clipboard.writeText(text).catch(() => {});
    });

    root.querySelector('[data-mtool-clear]').addEventListener('click', () => {
        field.setValue('');
        if (result) result.textContent = '';
        field.focus();
    });

    // Drag the panel by its header.
    const head = root.querySelector('[data-mtool-drag]');
    let drag = null;
    head.addEventListener('pointerdown', (e) => {
        if (e.target.closest('[data-mtool-close]')) return;
        const r = panel.getBoundingClientRect();
        drag = { dx: e.clientX - r.left, dy: e.clientY - r.top };
        head.setPointerCapture(e.pointerId);
    });
    head.addEventListener('pointermove', (e) => {
        if (!drag) return;
        panel.style.left = Math.max(0, e.clientX - drag.dx) + 'px';
        panel.style.top = Math.max(0, e.clientY - drag.dy) + 'px';
        panel.style.right = 'auto';
        panel.style.bottom = 'auto';
    });
    head.addEventListener('pointerup', () => { drag = null; });
})();
</script>
