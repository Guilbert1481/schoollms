@php
    $embed = $embed ?? false;
@endphp

{{-- Embedded (?embed=1): bare fullscreen layout — no sidebar/header — for the
     catalog's distraction-free game overlay. Direct visits keep the app chrome. --}}
@extends($embed ? 'layouts.game' : 'layouts.app')

@section('title', $game['title'])

@section('content')
@php
    // Resolve the partial path for the active game. Each game has its own
    // partial at resources/views/tools/games/games/{slug}.blade.php so they
    // can be developed independently. If the partial is missing we fall back
    // to a generic placeholder.
    $partial = 'tools.games.games.' . str_replace('-', '_', $game['slug']);
    if (! view()->exists($partial)) {
        $partial = 'tools.games.games._placeholder';
    }
@endphp

@if($embed)
{{-- Distraction-free stage: the game sits vertically centered in the viewport
     and is width-capped (readable inputs) instead of stretching edge to edge. --}}
<div class="min-h-screen w-full flex items-center justify-center p-4 md:p-6">
    <div class="w-full max-w-3xl">
        @include($partial, ['game' => $game])
    </div>
</div>

@php $ctx = $ctx ?? ['role' => 'student', 'bank' => ['subjects'=>[],'topics'=>[],'lessons'=>[],'competencies'=>[]], 'levels' => [], 'autoLevelId' => null, 'termCount' => 4, 'quiz' => null, 'lock' => null]; @endphp

{{-- ================= ☰ In-game menu (Exit / Year Level / Questions / Quiz Mode) ================= --}}
<div id="gmMenuRoot">
    <button type="button" id="gmBurger" aria-label="Game menu" title="Game menu"
            class="fixed right-3 top-3 z-40 flex h-11 w-11 items-center justify-center rounded-full bg-slate-900/80 text-white shadow-lg ring-1 ring-white/20 hover:bg-slate-900">
        <i data-lucide="menu" class="h-5 w-5"></i>
    </button>

    <div id="gmPanel" class="hidden fixed right-3 top-16 z-40 w-72 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl space-y-3">
        <div id="gmLockBanner" class="hidden rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-800"></div>

        <div id="gmLevelWrap" class="hidden">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Year Level</label>
            <select id="gmLevel" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></select>
        </div>

        <button type="button" id="gmQuestionsBtn"
                class="w-full rounded-lg border border-fuchsia-200 bg-fuchsia-50 px-3 py-2 text-sm font-bold text-fuchsia-700 hover:bg-fuchsia-100">
            Questions — choose content
        </button>

        <div id="gmQuizWrap" class="hidden items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
            <span class="text-sm font-bold text-slate-700">Quiz Mode</span>
            <label class="relative inline-block cursor-pointer">
                <input type="checkbox" id="gmQuizToggle" class="peer sr-only">
                <span class="block h-5 w-9 rounded-full bg-slate-300 transition peer-checked:bg-indigo-600"></span>
                <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-4"></span>
            </label>
        </div>

        <div class="border-t border-slate-100 pt-3">
            <button type="button" id="gmExit"
                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-bold text-rose-700 hover:bg-rose-100">
                <i data-lucide="x" class="h-4 w-4"></i> Exit Game
            </button>
        </div>
    </div>

    {{-- Questions modal: cascading Subject → Topic → Lesson → Competency --}}
    <div id="gmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-800">Choose content to practice</h3>
                <button type="button" id="gmModalClose" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <p class="mt-1 text-xs text-slate-500">
                Pick a subject for broad practice, or drill down to one topic, lesson, or competency.
            </p>

            <div class="mt-4 space-y-3">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Subject</label>
                    <select id="gmSubject" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Topic</label>
                    <select id="gmTopic" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Lesson</label>
                    <select id="gmLesson" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Competency</label>
                    <select id="gmCompetency" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></select>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" id="gmModalCancel"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" id="gmModalApply"
                        class="rounded-lg bg-fuchsia-600 px-4 py-2 text-sm font-bold text-white hover:bg-fuchsia-700">Apply</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const CTX       = @json($ctx);
    const QUIZ_URL  = @json(route('tools.games.quiz-mode'));
    const CSRF      = @json(csrf_token());
    const CATALOG   = @json(route('tools.games.index'));

    const el = (id) => document.getElementById(id);
    const isTeacher = CTX.role === 'teacher';
    const lock = CTX.lock;
    const quiz = CTX.quiz;

    // ---------------- Global scope the games read at fetch time ----------------
    window.GameScope = {
        academic_level_id: lock?.academic_level_id ?? quiz?.academic_level_id ?? CTX.autoLevelId ?? null,
        subject_id:        lock?.subject_id ?? quiz?.subject_id ?? null,
        topic_id:          lock?.topic_id ?? quiz?.topic_id ?? null,
        lesson_id:         lock?.lesson_id ?? quiz?.lesson_id ?? null,
        competency_id:     lock?.competency_id ?? quiz?.competency_id ?? null,
        locked:            !!lock,
    };

    window.GameScope.summary = function () {
        const s = window.GameScope;
        const subj = (CTX.bank.subjects || []).find(x => x.id === s.subject_id);
        const top  = (CTX.bank.topics || []).find(x => x.id === s.topic_id);
        const les  = (CTX.bank.lessons || []).find(x => x.id === s.lesson_id);
        const com  = (CTX.bank.competencies || []).find(x => x.id === s.competency_id);
        const lvl  = (CTX.levels || []).find(x => x.id === s.academic_level_id);
        const parts = [];
        if (lvl)  parts.push(lvl.name);
        parts.push(subj ? subj.name : 'All subjects');
        if (top)  parts.push(top.name);
        if (les)  parts.push(les.name);
        if (com)  parts.push(com.name);
        return parts.join(' · ');
    };

    function changed() {
        document.dispatchEvent(new CustomEvent('gamescope:changed'));
    }

    // ---------------- Hamburger open/close ----------------
    el('gmBurger').addEventListener('click', () => el('gmPanel').classList.toggle('hidden'));
    document.addEventListener('click', (e) => {
        if (!el('gmMenuRoot').contains(e.target)) el('gmPanel').classList.add('hidden');
    });

    // ---------------- Exit ----------------
    el('gmExit').addEventListener('click', () => {
        if (window.parent !== window) {
            window.parent.postMessage({ type: 'schoollms:game-exit' }, '*');
        } else {
            window.location.href = CATALOG;
        }
    });

    // ---------------- Year Level (teachers only; students auto-captured) ----------------
    if (isTeacher) {
        el('gmLevelWrap').classList.remove('hidden');
        const lvlSel = el('gmLevel');
        lvlSel.insertAdjacentHTML('beforeend', '<option value="">All levels</option>');
        (CTX.levels || []).forEach(l => {
            lvlSel.insertAdjacentHTML('beforeend', '<option value="' + l.id + '">' + l.name + '</option>');
        });
        lvlSel.value = window.GameScope.academic_level_id ? String(window.GameScope.academic_level_id) : '';
        lvlSel.addEventListener('change', () => {
            window.GameScope.academic_level_id = lvlSel.value ? parseInt(lvlSel.value, 10) : null;
            changed();
            saveQuizIfTeacher();
        });
    }

    // ---------------- Quiz Mode (teachers only) ----------------
    if (isTeacher) {
        el('gmQuizWrap').classList.remove('hidden');
        el('gmQuizWrap').classList.add('flex');
        el('gmQuizToggle').checked = !!(quiz && Number(quiz.is_on));
        el('gmQuizToggle').addEventListener('change', saveQuizIfTeacher);
    }

    function saveQuizIfTeacher() {
        if (!isTeacher) return;
        const s = window.GameScope;
        fetch(QUIZ_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({
                is_on: el('gmQuizToggle').checked,
                academic_level_id: s.academic_level_id,
                subject_id: s.subject_id,
                topic_id: s.topic_id,
                lesson_id: s.lesson_id,
                competency_id: s.competency_id,
            }),
        }).catch(() => {});
    }

    // ---------------- Student lock ----------------
    if (lock) {
        el('gmLockBanner').textContent = 'Quiz Mode is ON — content set by ' + lock.teacher_name + '.';
        el('gmLockBanner').classList.remove('hidden');
        el('gmQuestionsBtn').classList.add('hidden');
    }

    // ---------------- Questions modal (cascading selects) ----------------
    const bank = CTX.bank;
    const fill = (sel, items, placeholder, selected) => {
        sel.innerHTML = '<option value="">' + placeholder + '</option>'
            + items.map(i => '<option value="' + i.id + '"' + (selected === i.id ? ' selected' : '') + '>' + escapeHtml(i.name) + '</option>').join('');
    };
    const val = (sel) => sel.value ? parseInt(sel.value, 10) : null;

    function cascade(preserve = false) {
        const sid = val(el('gmSubject'));
        const topics = (bank.topics || []).filter(t => !sid || t.subject_id === sid);
        fill(el('gmTopic'), topics, 'All topics', preserve ? window.GameScope.topic_id : null);

        const tid = val(el('gmTopic'));
        const lessons = (bank.lessons || []).filter(l => (!sid || l.subject_id === sid) && (!tid || l.topic_id === tid));
        fill(el('gmLesson'), lessons, 'All lessons', preserve ? window.GameScope.lesson_id : null);

        const lid = val(el('gmLesson'));
        const comps = (bank.competencies || []).filter(c =>
            (!sid || c.subject_id === sid) && (!tid || c.topic_id === tid) && (!lid || c.lesson_id === lid));
        fill(el('gmCompetency'), comps, 'All competencies', preserve ? window.GameScope.competency_id : null);
    }

    el('gmQuestionsBtn').addEventListener('click', () => {
        el('gmPanel').classList.add('hidden');
        fill(el('gmSubject'), bank.subjects || [], 'All subjects', window.GameScope.subject_id);
        cascade(true);
        el('gmModal').classList.remove('hidden');
    });
    el('gmSubject').addEventListener('change', () => cascade(false));
    el('gmTopic').addEventListener('change', () => { const k = val(el('gmTopic')); cascadeFromTopic(k); });
    el('gmLesson').addEventListener('change', () => cascadeFromLesson());

    function cascadeFromTopic() {
        const sid = val(el('gmSubject'));
        const tid = val(el('gmTopic'));
        const lessons = (bank.lessons || []).filter(l => (!sid || l.subject_id === sid) && (!tid || l.topic_id === tid));
        fill(el('gmLesson'), lessons, 'All lessons', null);
        cascadeFromLesson();
    }
    function cascadeFromLesson() {
        const sid = val(el('gmSubject'));
        const tid = val(el('gmTopic'));
        const lid = val(el('gmLesson'));
        const comps = (bank.competencies || []).filter(c =>
            (!sid || c.subject_id === sid) && (!tid || c.topic_id === tid) && (!lid || c.lesson_id === lid));
        fill(el('gmCompetency'), comps, 'All competencies', null);
    }

    const closeModal = () => el('gmModal').classList.add('hidden');
    el('gmModalClose').addEventListener('click', closeModal);
    el('gmModalCancel').addEventListener('click', closeModal);
    el('gmModal').addEventListener('click', (e) => { if (e.target === el('gmModal')) closeModal(); });

    el('gmModalApply').addEventListener('click', () => {
        window.GameScope.subject_id    = val(el('gmSubject'));
        window.GameScope.topic_id      = val(el('gmTopic'));
        window.GameScope.lesson_id     = val(el('gmLesson'));
        window.GameScope.competency_id = val(el('gmCompetency'));
        closeModal();
        changed();
        saveQuizIfTeacher();
    });

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    // Let the game partials render the initial scope line.
    document.addEventListener('DOMContentLoaded', changed);
})();
</script>
@else
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <a href="{{ route('tools.games.index') }}" class="hover:text-fuchsia-700">Gamified Quiz</a>
                <i data-lucide="chevron-right" class="w-3 h-3"></i>
                <span class="truncate">{{ $game['title'] }}</span>
            </div>
            <h1 class="mt-1 text-2xl md:text-3xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="{{ $game['icon'] }}" class="w-7 h-7 text-fuchsia-600"></i>
                <span class="truncate">{{ $game['title'] }}</span>
            </h1>
            <p class="text-sm text-slate-600 mt-1">{{ $game['description'] }}</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('tools.games.index') }}"
               class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Back to Games
            </a>
        </div>
    </div>

    {{-- Hero banner --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br {{ $game['color'] }} p-6 md:p-8 text-white shadow-lg">
        <div class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-white/20 blur-2xl"></div>
        <div class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-black/20 blur-2xl"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-3">
            <div>
                <div class="inline-flex items-center gap-2 rounded-lg border border-white/30 bg-white/15 px-2 py-1 text-xs font-medium backdrop-blur-sm">
                    <i data-lucide="sparkles" class="w-4 h-4"></i> Game Template
                </div>
                <h2 class="mt-3 text-2xl md:text-3xl font-extrabold drop-shadow">
                    {{ $game['title'] }}
                </h2>
            </div>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border border-white/40 bg-white/15">
                Slug: {{ $game['slug'] }}
            </span>
        </div>
    </div>

    {{-- Game body (modular partial) --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-4 md:p-6 shadow-sm">
        @include($partial, ['game' => $game])
    </section>
</div>
@endif

@push('scripts')
    <script src="{{ asset('js/tools/games/play.js') }}" defer></script>
@endpush
@endsection
