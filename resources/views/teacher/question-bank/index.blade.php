@extends('layouts.app')

@section('page-title', 'Question Bank')

@section('content')
<div class="w-full space-y-6">

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <h1 class="text-xl font-extrabold text-slate-800">Question Bank</h1>

    {{-- Education-level tabs — only the levels this teacher is assigned to.
         Hidden automatically when the teacher spans a single level. --}}
    <x-table.level-tabs route="teacher.question_bank.index"
                        :levels="$levels"
                        :activeLevelId="$activeLevelId"
                        :showAll="$showAll" />

    <x-table.table
        tableKey="teacher_question_bank"
        :columns="$columns"
        :data="$rows"
        :hideActions="true"
        perPage="10"
        createRoute="teacher.question.metadata"
        createLabel="Create Questions"
        emptyMessage="Your question bank is empty here — use Create Questions to author your first items."
    >
        <x-slot:afterFilter>
            <select onchange="qbApplyFilter('level_id', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All {{ $showAll ? 'Levels' : ($activeLevelIsBasic ? 'Grade Levels' : 'Year Levels') }}</option>
                @foreach($levelOptions as $lvlId => $lvlName)
                    <option value="{{ $lvlId }}" @selected((int) $levelId === (int) $lvlId)>{{ $lvlName }}</option>
                @endforeach
            </select>

            <select onchange="qbApplyFilter('subject_id', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All Subjects</option>
                @foreach($subjectOptions as $subId => $subName)
                    <option value="{{ $subId }}" @selected((int) $subjectId === (int) $subId)>{{ $subName }}</option>
                @endforeach
            </select>

            <select onchange="qbApplyFilter('topic_id', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All Topics</option>
                @foreach($topicOptions as $topId => $topName)
                    <option value="{{ $topId }}" @selected((int) $topicId === (int) $topId)>{{ $topName }}</option>
                @endforeach
            </select>

            <select onchange="qbApplyFilter('type', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All Types</option>
                @foreach($typeOptions as $typeValue => $typeName)
                    <option value="{{ $typeValue }}" @selected($type === $typeValue)>{{ $typeName }}</option>
                @endforeach
            </select>

            <select onchange="qbApplyFilter('difficulty', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All Difficulties</option>
                @foreach($difficultyOptions as $diffValue => $diffName)
                    <option value="{{ $diffValue }}" @selected($difficulty === $diffValue)>{{ $diffName }}</option>
                @endforeach
            </select>
        </x-slot:afterFilter>
    </x-table.table>

</div>

{{-- ------------------------------------------------------------------
     View modal — filled by qbView(id) from the detail JSON endpoint.
     Every value lands via textContent, so nothing is interpreted as HTML.
------------------------------------------------------------------ --}}
<div id="qbModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" onclick="qbCloseModal()"></div>

    <div class="relative w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Question Details</h2>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                    <span id="qbModalType" class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-0.5 font-semibold text-indigo-700"></span>
                    <span id="qbModalDifficulty" class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 font-semibold text-slate-600"></span>
                    <span id="qbModalPoints" class="hidden rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 font-semibold text-slate-600"></span>
                </div>
            </div>
            <button type="button" onclick="qbCloseModal()"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                ✕
            </button>
        </div>

        <div class="space-y-5 px-6 py-5">
            <div id="qbModalLoading" class="py-6 text-center text-sm text-slate-500">Loading…</div>

            <div id="qbModalBody" class="hidden space-y-5">
                <p id="qbModalQuestion" class="whitespace-pre-wrap text-sm font-medium text-slate-800"></p>

                <div id="qbModalChoicesWrap" class="hidden">
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Choices / Answer</h3>
                    <ul id="qbModalChoices" class="space-y-1.5"></ul>
                </div>

                <div id="qbModalKeywordWrap" class="hidden">
                    <h3 class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-500">Answer Keyword</h3>
                    <p id="qbModalKeyword" class="text-sm text-slate-700"></p>
                </div>

                <div id="qbModalExplanationWrap" class="hidden">
                    <h3 class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-500">Explanation</h3>
                    <p id="qbModalExplanation" class="whitespace-pre-wrap text-sm text-slate-700"></p>
                </div>

                <div class="grid grid-cols-2 gap-x-6 gap-y-2 rounded-xl bg-slate-50 p-4 text-sm md:grid-cols-3">
                    @foreach([
                        'qbModalLevel' => 'Level',
                        'qbModalSubject' => 'Subject',
                        'qbModalTopic' => 'Topic',
                        'qbModalLesson' => 'Lesson',
                        'qbModalCompetency' => 'Competency',
                        'qbModalUsedIn' => 'Used In',
                        'qbModalCreated' => 'Created',
                    ] as $qbFieldId => $qbFieldLabel)
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $qbFieldLabel }}</div>
                            <div id="{{ $qbFieldId }}" class="text-slate-700"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div id="qbModalError" class="hidden py-6 text-center text-sm text-rose-600"></div>
        </div>
    </div>
</div>

<script>
    // Server-side filters: swap one query param and reload, keeping the active
    // level tab and the other filters intact (same pattern as Test Management).
    function qbApplyFilter(key, value) {
        const url = new URL(window.location);
        if (value) {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
        window.location = url.toString();
    }

    const QB_SHOW_BASE = @json(route('teacher.question_bank.index'));

    function qbSet(id, text) {
        document.getElementById(id).textContent = text;
    }

    function qbToggle(id, show) {
        document.getElementById(id).classList.toggle('hidden', ! show);
    }

    function qbCloseModal() {
        const modal = document.getElementById('qbModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') qbCloseModal();
    });

    async function qbView(id) {
        const modal = document.getElementById('qbModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        qbToggle('qbModalLoading', true);
        qbToggle('qbModalBody', false);
        qbToggle('qbModalError', false);

        try {
            const res = await fetch(QB_SHOW_BASE + '/' + id, {
                headers: { 'Accept': 'application/json' },
            });
            if (! res.ok) throw new Error('HTTP ' + res.status);
            const q = await res.json();

            qbSet('qbModalType', q.type_label);
            qbSet('qbModalDifficulty', q.difficulty);
            qbSet('qbModalPoints', q.points !== null ? q.points + ' pt' + (q.points === 1 ? '' : 's') : '');
            qbToggle('qbModalPoints', q.points !== null);

            qbSet('qbModalQuestion', q.question_text);

            // Choices — the correct one(s) highlighted. All content lands via
            // textContent so nothing user-authored is parsed as HTML.
            const list = document.getElementById('qbModalChoices');
            list.innerHTML = '';
            (q.choices || []).forEach((c) => {
                const li = document.createElement('li');
                li.className = c.correct
                    ? 'flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800'
                    : 'flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700';

                const mark = document.createElement('span');
                mark.className = 'mt-0.5 text-xs font-bold ' + (c.correct ? 'text-emerald-600' : 'text-slate-300');
                mark.textContent = c.correct ? '✓' : '•';

                const text = document.createElement('span');
                text.textContent = c.text;

                li.append(mark, text);
                list.appendChild(li);
            });
            qbToggle('qbModalChoicesWrap', (q.choices || []).length > 0);

            qbSet('qbModalKeyword', q.keyword);
            qbToggle('qbModalKeywordWrap', !! q.keyword);

            qbSet('qbModalExplanation', q.explanation);
            qbToggle('qbModalExplanationWrap', !! q.explanation);

            qbSet('qbModalLevel', q.level);
            qbSet('qbModalSubject', q.subject);
            qbSet('qbModalTopic', q.topic);
            qbSet('qbModalLesson', q.lesson);
            qbSet('qbModalCompetency', q.competency);
            qbSet('qbModalUsedIn', q.used_in > 0 ? q.used_in + ' test' + (q.used_in === 1 ? '' : 's') : 'Not used yet');
            qbSet('qbModalCreated', q.created);

            qbToggle('qbModalLoading', false);
            qbToggle('qbModalBody', true);
        } catch (err) {
            qbToggle('qbModalLoading', false);
            const box = document.getElementById('qbModalError');
            box.textContent = 'Could not load this question. Please try again.';
            box.classList.remove('hidden');
        }
    }
</script>
@endsection
