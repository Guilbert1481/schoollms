@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Scientific Calculator</h1>
            <p class="text-sm text-slate-600">Evaluate expressions with scientific functions, constants, and memory controls.</p>
        </div>
        <a href="{{ route('tools.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Back to Tools Hub
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_440px]">
    <section class="w-full rounded-3xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
        <div class="mb-4 rounded-2xl border border-slate-200 bg-slate-900 p-4 md:p-5">
            <div id="calc-expression" class="min-h-[22px] break-all text-right text-xs text-slate-300"></div>
            <div id="calc-display" class="mt-2 min-h-[52px] break-all text-right text-4xl font-semibold tracking-tight text-white">0</div>
            <div class="mt-3 flex justify-end">
                <button id="frac-dec-toggle" type="button" class="rounded-md border border-slate-500 px-2.5 py-1 text-xs font-medium text-slate-200 hover:bg-slate-700">Fr&#8646;Dec</button>
            </div>
        </div>

        <div class="grid grid-cols-5 gap-2 text-sm md:gap-2.5">
            <button id="shift-btn" class="calc-btn calc-soft" data-action="shift">Shift</button>
            <button class="calc-btn calc-soft" data-action="memory-clear">MC</button>
            <button class="calc-btn calc-soft" data-action="memory-recall">MR</button>
            <button class="calc-btn calc-soft" data-action="memory-add">M+</button>
            <button class="calc-btn calc-fn" data-action="insert-factorial">x!</button>

            <button class="calc-btn calc-fn trig-btn" data-normal="sin(" data-shift="asin(">sin</button>
            <button class="calc-btn calc-fn trig-btn" data-normal="cos(" data-shift="acos(">cos</button>
            <button class="calc-btn calc-fn trig-btn" data-normal="tan(" data-shift="atan(">tan</button>
            <button class="calc-btn calc-fn" data-value="ln(">ln</button>
            <button class="calc-btn calc-fn" data-value="log(">log</button>

            <button class="calc-btn calc-fn shift-toggle-btn" data-normal-value="^" data-shift-value="root(" data-normal-label="x<sup>y</sup>" data-shift-label="n&radic;">x<sup>y</sup></button>
            <button class="calc-btn calc-fn shift-toggle-btn" data-normal-value="^3" data-shift-value="cbrt(" data-normal-label="x<sup>3</sup>" data-shift-label="3&radic;">x<sup>3</sup></button>
            <button class="calc-btn calc-fn shift-toggle-btn" data-normal-value="^2" data-shift-value="sqrt(" data-normal-label="x<sup>2</sup>" data-shift-label="&radic;">x<sup>2</sup></button>
            <button class="calc-btn calc-fn" data-value="pi">&pi;</button>
            <button class="calc-btn calc-fn" data-value="sum(">&Sigma;</button>

            <button class="calc-btn calc-fn shift-toggle-btn" data-normal-value="frac(" data-shift-value="mixed(" data-normal-label="<span class='math-frac-btn'><span class='math-frac-num'>a</span><span class='math-frac-bar'></span><span class='math-frac-den'>b</span></span>" data-shift-label="<span class='mixed-frac-btn'>a<span class='math-frac-btn'><span class='math-frac-num'>b</span><span class='math-frac-bar'></span><span class='math-frac-den'>c</span></span></span>"><span class='math-frac-btn'><span class='math-frac-num'>a</span><span class='math-frac-bar'></span><span class='math-frac-den'>b</span></span></button>
            <button class="calc-btn calc-fn" data-value="perm(">nPr</button>
            <button class="calc-btn calc-fn" data-value="comb(">nCr</button>
            <button class="calc-btn calc-op" data-value="(">(</button>
            <button class="calc-btn calc-op" data-value=")">)</button>

            <button class="calc-btn" data-value="7">7</button>
            <button class="calc-btn" data-value="8">8</button>
            <button class="calc-btn" data-value="9">9</button>
            <button class="calc-btn calc-soft" data-action="backspace">DEL</button>
            <button class="calc-btn calc-soft" data-action="clear">AC</button>

            <button class="calc-btn" data-value="4">4</button>
            <button class="calc-btn" data-value="5">5</button>
            <button class="calc-btn" data-value="6">6</button>
            <button class="calc-btn calc-op" data-value="*">&times;</button>
            <button class="calc-btn calc-op" data-value="/">&divide;</button>

            <button class="calc-btn" data-value="1">1</button>
            <button class="calc-btn" data-value="2">2</button>
            <button class="calc-btn" data-value="3">3</button>
            <button class="calc-btn calc-op" data-value="+">+</button>
            <button class="calc-btn calc-op" data-value="-">-</button>

            <button class="calc-btn" data-value="0">0</button>
            <button class="calc-btn" data-value=".">.</button>
            <button class="calc-btn calc-fn" data-action="insert-ee">x10<sup>n</sup></button>
            <button class="calc-btn calc-soft" data-action="ans">Ans</button>
            <button class="calc-btn calc-eq" data-action="equals">=</button>
        </div>
    </section>

    <aside class="w-full rounded-3xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">History</h2>
            <button id="clear-history" type="button" class="rounded-md border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">Clear All</button>
        </div>
        <ul id="calc-history" class="max-h-[520px] space-y-2 overflow-auto pr-1 text-sm"></ul>
    </aside>
    </div>
</div>

<style>
    .calc-btn {
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #0f172a;
        border-radius: 0.75rem;
        min-height: 3rem;
        padding: 0.6rem 0.5rem;
        font-weight: 600;
        transition: background-color .15s ease, border-color .15s ease;
    }
    .calc-btn:hover { background: #f8fafc; }
    .calc-soft { background: #f8fafc; }
    .calc-fn { background: #ecfeff; border-color: #bae6fd; }
    .calc-op { background: #eef2ff; border-color: #c7d2fe; }
    .calc-eq { background: #2563eb; border-color: #1d4ed8; color: #fff; }
    .calc-eq:hover { background: #1d4ed8; }
    .calc-shift-on { background: #f59e0b; border-color: #d97706; color: #fff; }
    #calc-expression sup { font-size: 0.72em; vertical-align: super; }
    #calc-display sup { font-size: 0.55em; vertical-align: super; }
    #calc-expression sub { font-size: 0.72em; vertical-align: sub; }
    #calc-display sub { font-size: 0.55em; vertical-align: sub; }
    .math-frac { display: inline-flex; flex-direction: column; align-items: center; line-height: 1; vertical-align: middle; margin: 0 0.1rem; }
    .math-frac-num { font-size: 0.8em; padding: 0 0.15rem; }
    .math-frac-den { font-size: 0.8em; padding: 0 0.15rem; }
    .math-frac-bar { width: 100%; border-top: 1px solid currentColor; margin: 0.08em 0; }
    .math-frac-btn { display: inline-flex; flex-direction: column; align-items: center; line-height: 1; font-size: 0.92em; }
    .math-frac-active {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        border: 1px solid #ffffff;
        border-radius: 0.25rem;
        animation: calcFlashBox 1s ease-in-out infinite;
    }
    .math-frac-box {
        min-width: 1.8ch;
        text-align: center;
        padding: 0 0.2rem;
        border-radius: 0.25rem;
        border: 1px solid rgba(255, 255, 255, 0.45);
        background: rgba(255, 255, 255, 0.08);
    }
    .mixed-frac-btn { display: inline-flex; align-items: center; gap: 0.12rem; }
    .mixed-whole-box {
        min-width: 1.5ch;
        text-align: center;
        padding: 0 0.2rem;
        border-radius: 0.25rem;
        border: 1px solid rgba(255, 255, 255, 0.45);
        background: rgba(255, 255, 255, 0.08);
    }
    .mixed-num { display: inline-flex; align-items: center; gap: 0.14rem; vertical-align: middle; }
    .mixed-whole { font-size: 0.95em; }
    @keyframes calcFlashBox {
        0% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.9); }
        50% { box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.15); }
        100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.9); }
    }
    .history-item { border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 0.75rem; padding: 0.55rem 0.6rem; }
    .history-tag { display: inline-block; border: 1px solid #cbd5e1; background: #fff; color: #334155; border-radius: 9999px; padding: 0.1rem 0.45rem; font-size: 0.68rem; font-weight: 600; }
</style>

<script>
(function () {
    const expressionEl = document.getElementById('calc-expression');
    const displayEl = document.getElementById('calc-display');
    const historyEl = document.getElementById('calc-history');
    const clearHistoryBtn = document.getElementById('clear-history');
    const fracDecToggleBtn = document.getElementById('frac-dec-toggle');
    const HISTORY_KEY = 'admin-tools-scientific-calculator-history-v1';

    let expression = '';
    let lastAnswer = '0';
    let memory = 0;
    let shiftMode = false;
    let justEvaluated = false;
    let fractionEditor = null;
    let historyItems = loadHistory();

    const shiftBtn = document.getElementById('shift-btn');

    function updateShiftState() {
        document.querySelectorAll('.trig-btn').forEach((btn) => {
            const label = shiftMode ? btn.getAttribute('data-shift') : btn.getAttribute('data-normal');
            btn.setAttribute('data-value', label);
            btn.textContent = label.replace('(', '');
        });

        document.querySelectorAll('.shift-toggle-btn').forEach((btn) => {
            const value = shiftMode ? btn.getAttribute('data-shift-value') : btn.getAttribute('data-normal-value');
            const label = shiftMode ? btn.getAttribute('data-shift-label') : btn.getAttribute('data-normal-label');
            btn.setAttribute('data-value', value);
            btn.innerHTML = label;
        });

        if (shiftBtn) {
            shiftBtn.classList.toggle('calc-shift-on', shiftMode);
        }
    }

    function render() {
        expressionEl.innerHTML = formatExpressionForDisplay(expression || '');

        if (fractionEditor) {
            const expressionPrefix = expression ? formatExpressionForDisplay(expression) + ' ' : '';
            displayEl.innerHTML = expressionPrefix + getFractionEditorHtml();
        } else {
            displayEl.innerHTML = formatExpressionForDisplay(expression || '0');
        }
    }

    function getFractionEditorHtml() {
        if (!fractionEditor) {
            return '';
        }

        const numerator = escapeHtml(fractionEditor.numerator || ' ');
        const denominator = escapeHtml(fractionEditor.denominator || ' ');

        if (fractionEditor.kind === 'mixed') {
            const whole = escapeHtml(fractionEditor.whole || ' ');
            const wholeClass = fractionEditor.active === 'whole'
                ? 'mixed-whole-box math-frac-active'
                : 'mixed-whole-box';
            const numClass = fractionEditor.active === 'numerator'
                ? 'math-frac-num math-frac-box math-frac-active'
                : 'math-frac-num math-frac-box';
            const denClass = fractionEditor.active === 'denominator'
                ? 'math-frac-den math-frac-box math-frac-active'
                : 'math-frac-den math-frac-box';

            return '<span class="mixed-num">'
                + '<span class="' + wholeClass + '">' + whole + '</span>'
                + '<span class="math-frac">'
                + '<span class="' + numClass + '">' + numerator + '</span>'
                + '<span class="math-frac-bar"></span>'
                + '<span class="' + denClass + '">' + denominator + '</span>'
                + '</span>'
                + '</span>';
        }

        const numClass = fractionEditor.active === 'numerator'
            ? 'math-frac-num math-frac-box math-frac-active'
            : 'math-frac-num math-frac-box';
        const denClass = fractionEditor.active === 'denominator'
            ? 'math-frac-den math-frac-box math-frac-active'
            : 'math-frac-den math-frac-box';

        return '<span class="math-frac">'
            + '<span class="' + numClass + '">' + numerator + '</span>'
            + '<span class="math-frac-bar"></span>'
            + '<span class="' + denClass + '">' + denominator + '</span>'
            + '</span>';
    }

    function startFractionEditor(kind = 'fraction') {
        if (justEvaluated) {
            expression = '';
        }

        fractionEditor = {
            kind,
            whole: '',
            numerator: '',
            denominator: '',
            active: 'denominator',
        };

        justEvaluated = false;
        render();
    }

    function setFractionActive(part) {
        if (!fractionEditor) {
            return;
        }

        if (part === 'whole' || part === 'numerator' || part === 'denominator') {
            fractionEditor.active = part;
            render();
        }
    }

    function appendToFraction(value) {
        if (!fractionEditor) {
            return;
        }

        const key = fractionEditor.active;
        if (value === '.' && fractionEditor[key].includes('.')) {
            return;
        }

        if (value === '-' && fractionEditor[key].length > 0) {
            return;
        }

        fractionEditor[key] += value;
        render();
    }

    function backspaceFraction() {
        if (!fractionEditor) {
            return;
        }

        const key = fractionEditor.active;
        fractionEditor[key] = fractionEditor[key].slice(0, -1);
        render();
    }

    function finalizeFractionEditor() {
        if (!fractionEditor) {
            return;
        }

        const numerator = fractionEditor.numerator.trim() || '0';
        const denominator = fractionEditor.denominator.trim() || '1';

        if (fractionEditor.kind === 'mixed') {
            const whole = fractionEditor.whole.trim() || '0';
            expression += 'mixed(' + whole + ',' + numerator + ',' + denominator + ')';
        } else {
            expression += 'frac(' + numerator + ',' + denominator + ')';
        }

        fractionEditor = null;
        render();
    }

    function formatExpressionForDisplay(expr) {
        if (!expr) {
            return '';
        }

        let pretty = expr;

        // Constants
        pretty = pretty.replace(/\bpi\b/g, '&pi;');
        pretty = pretty.replace(/\*/g, '&times;');
        pretty = pretty.replace(/\//g, '&divide;');

        // Roots and powers
        pretty = pretty.replace(/cbrt\(([^)]+)\)/g, '<sup>3</sup>&radic;($1)');
        pretty = pretty.replace(/sqrt\(([^)]+)\)/g, '&radic;($1)');
        pretty = pretty.replace(/root\(([^,]+),([^)]+)\)/g, '<sup>$1</sup>&radic;($2)');
        pretty = pretty.replace(/([0-9.)]+)pow\(([^)]+)\)/g, '$1^$2');
        pretty = pretty.replace(/pow\(([^,]+),([^)]+)\)/g, '$1^$2');
        pretty = pretty.replace(/\^(-?[A-Za-z0-9.()]+)/g, '<sup>$1</sup>');

        // Trigonometric functions
        pretty = pretty.replace(/asin\(/g, 'sin<sup>-1</sup>(');
        pretty = pretty.replace(/acos\(/g, 'cos<sup>-1</sup>(');
        pretty = pretty.replace(/atan\(/g, 'tan<sup>-1</sup>(');

        // Combinatorics
        pretty = pretty.replace(/perm\(([^,]+),([^)]+)\)/g, '<sub>$1</sub>P<sub>$2</sub>');
        pretty = pretty.replace(/comb\(([^,]+),([^)]+)\)/g, '<sub>$1</sub>C<sub>$2</sub>');
        pretty = pretty.replace(/sum\(([^,]+),([^)]+)\)/g, '&Sigma;($1..$2)');
        pretty = pretty.replace(/frac\(([^,]+),([^)]+)\)/g, '<span class="math-frac"><span class="math-frac-num">$1</span><span class="math-frac-bar"></span><span class="math-frac-den">$2</span></span>');
        pretty = pretty.replace(/mixed\(([^,]+),([^,]+),([^)]+)\)/g, '<span class="mixed-num"><span class="mixed-whole">$1</span><span class="math-frac"><span class="math-frac-num">$2</span><span class="math-frac-bar"></span><span class="math-frac-den">$3</span></span></span>');

        // Display technical helper forms in familiar notation
        pretty = pretty.replace(/FACT\(([^)]+)\)/g, '$1!');

        return pretty;
    }

    function convertFactorials(input) {
        let out = input;
        const factorialPattern = /(\([^()]+\)|[A-Za-z0-9_.]+)!/;

        while (factorialPattern.test(out)) {
            out = out.replace(factorialPattern, 'FACT($1)');
        }

        return out;
    }

    function convertCaretPowers(input) {
        let out = input;
        const token = '(?:\\([^()]+\\)|[A-Za-z0-9_.]+)';
        const caretPattern = new RegExp('(' + token + ')\\^(' + token + ')');

        while (caretPattern.test(out)) {
            out = out.replace(caretPattern, 'Math.pow($1,$2)');
        }

        return out;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function loadHistory() {
        try {
            const raw = localStorage.getItem(HISTORY_KEY);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    }

    function saveHistory() {
        localStorage.setItem(HISTORY_KEY, JSON.stringify(historyItems));
    }

    function renderHistory() {
        if (!historyItems.length) {
            historyEl.innerHTML = '<li class="history-item text-xs text-slate-500">No computations yet.</li>';
            return;
        }

        historyEl.innerHTML = historyItems.map((item) => {
            const labelHtml = item.label
                ? '<span class="history-tag">' + escapeHtml(item.label) + '</span>'
                : '';

            const exprHtml = formatExpressionForDisplay(escapeHtml(item.expression));
            const resultHtml = formatExpressionForDisplay(escapeHtml(item.result));

            return '<li class="history-item w-max min-w-full" data-id="' + item.id + '">'
                + '<div class="flex items-center justify-between gap-3">'
                + '<div class="flex items-center gap-2 whitespace-nowrap">'
                + labelHtml
                + '<div class="text-sm text-slate-700"><span class="font-medium">' + exprHtml + '</span> = <span class="font-semibold text-slate-900">' + resultHtml + '</span></div>'
                + '</div>'
                + '<div class="flex items-center gap-2">'
                + '<button type="button" data-history-action="label" data-id="' + item.id + '" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs text-slate-600 hover:bg-slate-100">Label</button>'
                + '<button type="button" data-history-action="delete" data-id="' + item.id + '" class="rounded-md border border-slate-300 bg-white px-2 py-0.5 text-xs text-slate-500 hover:bg-slate-100">x</button>'
                + '</div>'
                + '</li>';
        }).join('');
    }

    function appendHistory(expr, result) {
        historyItems.unshift({
            id: Date.now().toString() + Math.random().toString(16).slice(2, 8),
            expression: expr,
            result: result,
            label: '',
        });

        saveHistory();
        renderHistory();
    }

    function normalize(expr) {
        let out = expr;
        // Allow shorthand such as 6pow(3) by converting it to pow(6,3)
        out = out.replace(/([0-9.)]+)\s*pow\(/g, 'pow($1,');
        // Convert standalone pow( to Math.pow( without touching existing Math.pow(
        out = out.replace(/(^|[^.\w])pow\(/g, '$1Math.pow(');
        out = convertFactorials(out);
        out = convertCaretPowers(out);
        out = out.replace(/\bpi\b/g, 'Math.PI');
        out = out.replace(/\be\b/g, 'Math.E');
        out = out.replace(/sin\(/g, 'Math.sin(');
        out = out.replace(/cos\(/g, 'Math.cos(');
        out = out.replace(/tan\(/g, 'Math.tan(');
        out = out.replace(/asin\(/g, 'Math.asin(');
        out = out.replace(/acos\(/g, 'Math.acos(');
        out = out.replace(/atan\(/g, 'Math.atan(');
        out = out.replace(/sqrt\(/g, 'Math.sqrt(');
        out = out.replace(/cbrt\(/g, 'Math.cbrt(');
        out = out.replace(/ln\(/g, 'Math.log(');
        out = out.replace(/log\(/g, 'Math.log10(');
        out = out.replace(/perm\(/g, 'PERM(');
        out = out.replace(/comb\(/g, 'COMB(');
        out = out.replace(/sum\(/g, 'SUM(');
        out = out.replace(/frac\(/g, 'FRAC(');
        out = out.replace(/mixed\(/g, 'MIXED(');
        out = out.replace(/root\(/g, 'ROOT(');
        return out;
    }

    function gcd(a, b) {
        let x = Math.abs(a);
        let y = Math.abs(b);
        while (y !== 0) {
            const temp = y;
            y = x % y;
            x = temp;
        }
        return x || 1;
    }

    function formatDecimal(value) {
        const raw = Number(value);
        if (!Number.isFinite(raw)) {
            throw new Error('Invalid numeric value');
        }

        const normalized = raw.toFixed(12).replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1');
        return normalized === '-0' ? '0' : normalized;
    }

    function toFractionExpression(value) {
        const numeric = Number(value);
        if (!Number.isFinite(numeric)) {
            throw new Error('Invalid numeric value');
        }

        const rounded = Math.round(numeric);
        if (Math.abs(numeric - rounded) < 1e-12) {
            return String(rounded);
        }

        const sign = numeric < 0 ? -1 : 1;
        const absValue = Math.abs(numeric);

        // Use continued fractions for cleaner rational approximation (e.g. 0.666666... -> 2/3).
        const maxDenominator = 1000000;
        const tolerance = 1e-10;

        let hPrev = 0;
        let hCurr = 1;
        let kPrev = 1;
        let kCurr = 0;
        let b = absValue;

        let numerator = Math.round(absValue);
        let denominator = 1;

        for (let i = 0; i < 30; i++) {
            const a = Math.floor(b);
            const hNext = a * hCurr + hPrev;
            const kNext = a * kCurr + kPrev;

            if (kNext > maxDenominator) {
                break;
            }

            numerator = hNext;
            denominator = kNext;

            if (Math.abs((hNext / kNext) - absValue) < tolerance) {
                break;
            }

            const fractional = b - a;
            if (fractional === 0) {
                break;
            }

            hPrev = hCurr;
            hCurr = hNext;
            kPrev = kCurr;
            kCurr = kNext;
            b = 1 / fractional;
        }

        const divisor = gcd(numerator, denominator);
        numerator = (numerator / divisor) * sign;
        denominator = denominator / divisor;

        return 'frac(' + numerator + ',' + denominator + ')';
    }

    function evaluateExpression(expr) {
        const normalized = normalize(expr);
        const rootFn = (n, x) => Math.pow(x, 1 / n);
        const factorialFn = (n) => {
            const value = Number(n);
            if (!Number.isFinite(value) || value < 0 || !Number.isInteger(value)) {
                throw new Error('Factorial only supports non-negative integers');
            }

            let result = 1;
            for (let i = 2; i <= value; i++) {
                result *= i;
            }
            return result;
        };

        const permutationFn = (n, r) => {
            const nn = Number(n);
            const rr = Number(r);
            if (!Number.isInteger(nn) || !Number.isInteger(rr) || nn < 0 || rr < 0 || rr > nn) {
                throw new Error('Invalid permutation arguments');
            }
            return factorialFn(nn) / factorialFn(nn - rr);
        };

        const combinationFn = (n, r) => {
            const nn = Number(n);
            const rr = Number(r);
            if (!Number.isInteger(nn) || !Number.isInteger(rr) || nn < 0 || rr < 0 || rr > nn) {
                throw new Error('Invalid combination arguments');
            }
            return factorialFn(nn) / (factorialFn(rr) * factorialFn(nn - rr));
        };

        const summationFn = (start, end) => {
            const s = Number(start);
            const e = Number(end);

            if (!Number.isInteger(s) || !Number.isInteger(e)) {
                throw new Error('Summation requires integer bounds');
            }

            const step = s <= e ? 1 : -1;
            let total = 0;
            for (let i = s; step > 0 ? i <= e : i >= e; i += step) {
                total += i;
            }
            return total;
        };

        const fractionFn = (numerator, denominator) => {
            const n = Number(numerator);
            const d = Number(denominator);

            if (!Number.isFinite(n) || !Number.isFinite(d) || d === 0) {
                throw new Error('Invalid fraction arguments');
            }

            return n / d;
        };

        const mixedFn = (whole, numerator, denominator) => {
            const w = Number(whole);
            const n = Number(numerator);
            const d = Number(denominator);

            if (!Number.isFinite(w) || !Number.isFinite(n) || !Number.isFinite(d) || d === 0) {
                throw new Error('Invalid mixed-fraction arguments');
            }

            const sign = w < 0 ? -1 : 1;
            return w + sign * (n / d);
        };

        return Function('ROOT', 'FACT', 'PERM', 'COMB', 'SUM', 'FRAC', 'MIXED', '"use strict"; return (' + normalized + ');')(
            rootFn,
            factorialFn,
            permutationFn,
            combinationFn,
            summationFn,
            fractionFn,
            mixedFn
        );
    }

    function safeEvaluate() {
        if (!expression.trim()) return;
        const allowed = /^[0-9+\-*/().,!\sA-Za-z_^]*$/;
        if (!allowed.test(expression)) {
            displayEl.textContent = 'Error';
            return;
        }

        try {
            const sourceExpression = expression;
            const result = evaluateExpression(sourceExpression);
            if (!Number.isFinite(result)) {
                throw new Error('Invalid result');
            }

            const decimalOutput = formatDecimal(result);
            const output = /(frac\(|mixed\()/i.test(sourceExpression)
                ? toFractionExpression(result)
                : decimalOutput;

            appendHistory(sourceExpression, output);
            expression = output;
            lastAnswer = decimalOutput;
            justEvaluated = true;
            render();
        } catch (e) {
            displayEl.textContent = 'Error';
            justEvaluated = false;
        }
    }

    function applyValue(value) {
        if (value === 'frac(') {
            startFractionEditor('fraction');
            return;
        }

        if (value === 'mixed(') {
            startFractionEditor('mixed');
            return;
        }

        if (fractionEditor) {
            if (/^[0-9.]$/.test(value) || value === '-') {
                appendToFraction(value);
                return;
            }

            finalizeFractionEditor();
        }

        const continuationOperators = ['+', '-', '*', '/', '^', '!'];
        if (justEvaluated && !continuationOperators.includes(value)) {
            expression = '';
        }

        expression += value;
        justEvaluated = false;
        render();
    }

    function applyAction(action) {
        switch (action) {
            case 'clear':
                expression = '';
                fractionEditor = null;
                justEvaluated = false;
                render();
                break;
            case 'backspace':
                if (fractionEditor) {
                    backspaceFraction();
                    break;
                }
                expression = expression.slice(0, -1);
                justEvaluated = false;
                render();
                break;
            case 'ans':
                if (justEvaluated) {
                    expression = '';
                }
                expression += lastAnswer;
                justEvaluated = false;
                render();
                break;
            case 'memory-clear':
                memory = 0;
                break;
            case 'memory-recall':
                if (justEvaluated) {
                    expression = '';
                }
                expression += String(memory);
                justEvaluated = false;
                render();
                break;
            case 'memory-add':
                try {
                    const valueToAdd = evaluateExpression(expression || '0');
                    if (Number.isFinite(valueToAdd)) {
                        memory += Number(valueToAdd);
                    }
                } catch (e) {
                    displayEl.textContent = 'Error';
                }
                break;
            case 'insert-power':
                if (justEvaluated) {
                    expression = '';
                }
                expression += '^';
                justEvaluated = false;
                render();
                break;
            case 'insert-factorial':
                if (justEvaluated) {
                    expression = '';
                }
                expression += '!';
                justEvaluated = false;
                render();
                break;
            case 'insert-root':
                if (fractionEditor) {
                    finalizeFractionEditor();
                }
                if (justEvaluated) {
                    expression = '';
                }
                expression += 'root(';
                justEvaluated = false;
                render();
                break;
            case 'insert-ee':
                if (fractionEditor) {
                    finalizeFractionEditor();
                }

                if (justEvaluated) {
                    expression = '';
                }

                if (!expression || /[+\-*/^(,]$/.test(expression)) {
                    expression += '10^';
                } else {
                    expression += '*10^';
                }

                justEvaluated = false;
                render();
                break;
            case 'fraction-den':
                setFractionActive('denominator');
                break;
            case 'fraction-num':
                setFractionActive('numerator');
                break;
            case 'fraction-done':
                finalizeFractionEditor();
                break;
            case 'toggle-frac-dec':
                if (fractionEditor) {
                    finalizeFractionEditor();
                }

                if (!expression.trim()) {
                    break;
                }

                try {
                    const numericValue = evaluateExpression(expression);
                    const isFractionLike = /(frac\(|mixed\()/i.test(expression);
                    expression = isFractionLike
                        ? formatDecimal(numericValue)
                        : toFractionExpression(numericValue);
                    justEvaluated = false;
                    render();
                } catch (e) {
                    displayEl.textContent = 'Error';
                }
                break;
            case 'shift':
                shiftMode = !shiftMode;
                updateShiftState();
                break;
            case 'equals':
                if (fractionEditor) {
                    finalizeFractionEditor();
                }
                safeEvaluate();
                break;
            default:
                break;
        }
    }

    document.querySelectorAll('.calc-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const action = btn.getAttribute('data-action');
            const value = btn.getAttribute('data-value');

            if (value) {
                applyValue(value);
                return;
            }

            applyAction(action);
        });
    });

    window.addEventListener('keydown', (event) => {
        const target = event.target;
        const isTypingField = target && (
            target.tagName === 'INPUT' ||
            target.tagName === 'TEXTAREA' ||
            target.isContentEditable
        );

        if (isTypingField) {
            return;
        }

        const key = event.key;

        if (fractionEditor) {
            if (/^[0-9]$/.test(key) || key === '.' || key === '-') {
                event.preventDefault();
                appendToFraction(key);
                return;
            }

            if (key === 'ArrowUp') {
                event.preventDefault();
                setFractionActive('numerator');
                return;
            }

            if (key === 'ArrowDown') {
                event.preventDefault();
                setFractionActive('denominator');
                return;
            }

            if (key === 'ArrowLeft') {
                if (fractionEditor.kind === 'mixed') {
                    event.preventDefault();
                    setFractionActive('whole');
                    return;
                }
            }

            if (key === 'ArrowRight' || key === 'Tab') {
                event.preventDefault();
                if (fractionEditor.kind === 'mixed' && fractionEditor.active === 'whole') {
                    setFractionActive('numerator');
                } else {
                    finalizeFractionEditor();
                }
                return;
            }

            if (key === 'Backspace') {
                event.preventDefault();
                backspaceFraction();
                return;
            }
        }

        if (/^[0-9]$/.test(key)) {
            event.preventDefault();
            applyValue(key);
            return;
        }

        if (['+', '-', '*', '/', '(', ')', '.', ',', '^', '!'].includes(key)) {
            event.preventDefault();
            applyValue(key);
            return;
        }

        if (key === 'Enter' || key === '=') {
            event.preventDefault();
            applyAction('equals');
            return;
        }

        if (key === 'Backspace') {
            event.preventDefault();
            applyAction('backspace');
            return;
        }

        if (key === 'Delete' || key.toLowerCase() === 'escape') {
            event.preventDefault();
            applyAction('clear');
        }
    });

    historyEl.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const action = target.getAttribute('data-history-action');
        const id = target.getAttribute('data-id');

        if (!action || !id) {
            return;
        }

        if (action === 'delete') {
            historyItems = historyItems.filter((item) => item.id !== id);
            saveHistory();
            renderHistory();
            return;
        }

        if (action === 'label') {
            const item = historyItems.find((entry) => entry.id === id);
            if (!item) {
                return;
            }

            const newLabel = prompt('Add label for this computation:', item.label || '');
            if (newLabel === null) {
                return;
            }

            item.label = newLabel.trim();
            saveHistory();
            renderHistory();
        }
    });

    clearHistoryBtn.addEventListener('click', () => {
        historyItems = [];
        saveHistory();
        renderHistory();
    });

    fracDecToggleBtn.addEventListener('click', () => {
        applyAction('toggle-frac-dec');
    });

    updateShiftState();
    renderHistory();
    render();
})();
</script>
@endsection
