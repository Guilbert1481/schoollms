@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" defer></script>
<div class="w-full p-6 min-h-screen flex flex-col gap-6"
     style="background:
        radial-gradient(1200px 600px at 10% -10%, rgba(99,102,241,0.10), transparent 60%),
        radial-gradient(900px 500px at 100% 0%, rgba(16,185,129,0.08), transparent 55%),
        linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="folder-tree" class="w-6 h-6 text-indigo-600"></i>
                Lesson Studio
            </h1>
            <p class="text-sm text-slate-500 mt-1">Browse Subjects → Topics → Lessons → Competencies.</p>
        </div>

        {{-- Action buttons (contextual) --}}
        @php
            // Bulk-add vocabulary per level: tick a parent row, add children.
            $bulkParent = ['Subject', 'Topic', 'Lesson'][$level] ?? null;
            $bulkChild  = ['Topic', 'Lesson', 'Competency'][$level] ?? null;
        @endphp
        <div class="flex items-center gap-2">
            {{-- Add Topic/Lesson lives next to the view toggle (see _bulk-add-button). --}}
            @if($level === 3)
                <button type="button"
                        onclick="openModal('newLessonModal')"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-indigo-700">
                    <i data-lucide="plus" class="w-4 h-4"></i> New Lesson
                </button>
            @endif
        </div>
    </div>

    {{-- Stage-group tabs (education-level tabs pattern, basic ed only) --}}
    @if($level === 0)
        <x-table.level-tabs route="course-architect.lesson-studio.index"
                            :levels="$gradeLevels"
                            :activeLevelId="$activeLevelId"
                            :showAll="$showAll" />
    @endif

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-emerald-50/80 backdrop-blur border border-emerald-200 text-emerald-800 px-4 py-2 rounded-xl">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-rose-50/80 backdrop-blur border border-rose-200 text-rose-800 px-4 py-2 rounded-xl">
            <ul class="list-disc pl-5 text-sm">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- Breadcrumb: Subjects → Topic → Lesson → Competency trail, kept just above the browser --}}
    <nav class="flex items-center gap-1 text-sm flex-wrap">
        @foreach($breadcrumbs as $i => $crumb)
            @if($i > 0)
                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400"></i>
            @endif

            @if($i === count($breadcrumbs) - 1)
                <span class="px-2 py-1 rounded-lg bg-white/70 backdrop-blur ring-1 ring-slate-200 text-slate-900 font-semibold truncate max-w-[280px]">
                    {{ $crumb['label'] }}
                </span>
            @else
                <a href="{{ $crumb['url'] }}"
                   class="px-2 py-1 rounded-lg text-slate-600 hover:text-indigo-700 hover:bg-white/60 transition truncate max-w-[280px]">
                    {{ $crumb['label'] }}
                </a>
            @endif
        @endforeach
    </nav>

    {{-- LEVEL 0/1/2 — Folder browser --}}
    @if($level < 3)
        @php
            $childLabel  = $level === 0 ? 'topic' : ($level === 1 ? 'lesson' : 'resource');
            // Reorder is allowed for Topics (L1) and Lessons (L2). Subjects (L0) are not reorderable here.
            $reorderType = $level === 1 ? 'topic' : ($level === 2 ? 'lesson' : null);
        @endphp

        @if(count($folders) === 0)
            <div class="rounded-2xl bg-white/60 backdrop-blur-xl ring-1 ring-white/60 shadow-sm p-12 text-center">
                <i data-lucide="folder-open" class="w-10 h-10 text-slate-300 mx-auto"></i>
                <p class="mt-3 text-slate-500 text-sm">
                    @if($level === 0 && ! $showAll) No subjects mapped to this level yet.
                    @elseif($level === 0) No subjects yet.
                    @elseif($level === 1) No topics in this subject yet.
                    @else No lessons in this topic yet.
                    @endif
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    @if($level === 0) Ask your Principal to add it.
                    @else Ask your Program Head to add it.
                    @endif
                </p>
            </div>
        @else
            {{-- CARD VIEW (with its own display toggle, top-right) --}}
            <div id="folderCardWrap">
                <div class="flex items-center justify-end gap-2 mb-2">
                    @include('course-architect.construction.partials._grade-filter')
                    @include('course-architect.construction.partials._bulk-add-button')
                    @include('course-architect.construction.partials._view-toggle', ['idSuffix' => 'Card'])
                </div>

                <div id="folderCardView" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($folders as $i => $f)
                        <div class="folder-item relative" data-id="{{ $f['id'] }}"
                             data-name="{{ \Illuminate\Support\Str::lower($f['name'] . ' ' . ($f['subtitle'] ?? '')) }}">
                            <input type="checkbox"
                                   class="ls-pick absolute top-2 z-10 w-4 h-4 rounded border-slate-300 text-indigo-600"
                                   style="right: {{ $reorderType ? '2.9rem' : '0.5rem' }};"
                                   data-id="{{ $f['id'] }}" data-name="{{ $f['name'] }}"
                                   title="Tick to add {{ strtolower($bulkChild) }}s under this {{ strtolower($bulkParent) }}">
                            @if($reorderType)
                                <button type="button"
                                        class="drag-handle absolute top-2 right-2 z-10 w-7 h-7 rounded-lg bg-white/80 ring-1 ring-slate-200 text-slate-400 hover:text-indigo-600 hover:bg-white flex items-center justify-center cursor-grab active:cursor-grabbing"
                                        title="Drag to reorder"
                                        onclick="event.preventDefault(); event.stopPropagation();">
                                    <i data-lucide="grip-vertical" class="w-4 h-4 pointer-events-none"></i>
                                </button>
                                <span class="card-seq absolute top-2 left-2 z-10 inline-flex items-center justify-center min-w-[1.5rem] h-6 px-1.5 rounded-md bg-indigo-50 ring-1 ring-indigo-100 text-[11px] font-semibold text-indigo-700">
                                    {{ $i + 1 }}
                                </span>
                            @endif
                            <a href="{{ $f['url'] }}" draggable="false"
                               class="group relative block overflow-hidden rounded-2xl bg-white/70 backdrop-blur-xl ring-1 ring-white/60 shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 p-5">
                                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500/0 via-indigo-500/60 to-indigo-500/0 opacity-0 group-hover:opacity-100 transition"></div>
                                <div class="flex items-start gap-3">
                                    <div class="shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center shadow-md">
                                        <i data-lucide="folder" class="w-6 h-6 text-white"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold text-slate-900 truncate">{{ $f['name'] }}</div>
                                        @if(!empty($f['subtitle']))
                                            <div class="text-xs text-slate-500 mt-0.5 truncate">{{ $f['subtitle'] }}</div>
                                        @endif
                                        <div class="text-xs text-slate-500 mt-2">
                                            {{ $f['count'] }} {{ \Illuminate\Support\Str::plural($childLabel, $f['count']) }}
                                        </div>
                                    </div>
                                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 group-hover:text-indigo-500 transition"></i>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- LIST VIEW (shareable table) --}}
            <div id="folderListView" class="hidden">
                <x-table.table
                    :tableKey="$listTableKey"
                    :columns="$listColumns"
                    :data="$listData"
                    :hideActions="true"
                    :rowNumbers="(bool) $reorderType"
                    :reorderable="(bool) $reorderType"
                >
                    <x-slot:afterFilter>
                        @include('course-architect.construction.partials._grade-filter')
                    </x-slot:afterFilter>
                    @include('course-architect.construction.partials._bulk-add-button')
                    @include('course-architect.construction.partials._view-toggle', ['idSuffix' => 'List'])
                </x-table.table>
            </div>

            {{-- Empty filter state --}}
            <div id="folderFilterEmpty" class="hidden rounded-2xl bg-white/60 backdrop-blur-xl ring-1 ring-white/60 shadow-sm p-8 text-center text-sm text-slate-500">
                No folders match your filter.
            </div>
        @endif
    @endif

    {{-- LEVEL 3 — Competencies / lesson resources table --}}
    @if($level === 3)
        <div class="rounded-2xl bg-white/70 backdrop-blur-xl ring-1 ring-white/60 shadow-sm p-2">
            <x-table.table
                tableKey="lesson_resources"
                :columns="$columns"
                :data="$resources"
                :actions="[
                    [
                        'name'    => 'create_test',
                        'label'   => 'Create Test',
                        'class'   => 'bg-emerald-600 text-white',
                        'type'    => 'js',
                        'handler' => 'createTestFromResource',
                    ],
                    [
                        'name'    => 'edit',
                        'label'   => 'Edit',
                        'class'   => 'bg-amber-500 text-white',
                        'type'    => 'js',
                        'handler' => 'openLessonEdit',
                    ],
                    [
                        'name'  => 'delete',
                        'label' => 'Remove',
                        'class' => 'bg-red-500 text-white',
                        'type'  => 'delete',
                    ],
                ]"
                :deleteRoute="'course-architect.lesson-studio.destroy'"
            />
        </div>
    @endif

    {{-- Modals --}}
    @if($level === 3)
        @include('course-architect.construction.partials.new-lesson-modal')
        @include('course-architect.construction.partials.edit-lesson-modal')
        @include('course-architect.construction.partials.preview-lesson-modal')
    @endif

    {{-- Bulk-add modal: tick one {{ strtolower($bulkParent ?? 'row') }}, add several {{ strtolower($bulkChild ?? 'item') }}s at once --}}
    @if($level < 3 && count($folders) > 0)
        <x-modal.form id="bulkAddModal" :title="'Add '.$bulkChild.'s'" widthClass="w-[440px]">
            <form method="POST" action="{{ route('course-architect.lesson-studio.folder.store') }}">
                @csrf
                <input type="hidden"
                       name="{{ ['subject_id', 'topic_id', 'lesson_id'][$level] }}"
                       id="bulkPickedId">
                <div class="space-y-3">
                    <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-3 text-sm text-slate-600">
                        <span class="font-semibold text-slate-700">{{ $bulkParent }}:</span>
                        <span id="bulkPickedName"></span>
                    </div>
                    <div id="bulkNameRows" class="space-y-2">
                        <input type="text" name="names[]" required maxlength="255"
                               placeholder="{{ $bulkChild }} name"
                               class="w-full px-3 py-2 border border-gray-300 rounded text-sm">
                    </div>
                    <button type="button" onclick="lsAddBulkRow()"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        <i data-lucide="plus" class="w-4 h-4"></i> Add another {{ strtolower($bulkChild) }}
                    </button>
                </div>
            </form>
        </x-modal.form>
    @endif
</div>

<script>
    // Context for prefilled forms (auto-fill mechanism).
    window.__LESSON_STUDIO_CTX__ = {
        level:      {{ $level }},
        subject_id: {{ $subjectModel?->id ?? 'null' }},
        topic_id:   {{ $topicModel?->id   ?? 'null' }},
        lesson_id:  {{ $lessonModel?->id  ?? 'null' }},
    };

    function createTestFromResource(id) {
        window.location.href = "{{ route('course-architect.assessment-lab.index') }}?from_resource=" + id;
    }

    // ── Bulk add: single-select checkboxes (synced across card/list views) ──
    document.addEventListener('change', function (e) {
        const cb = e.target;
        if (!cb.classList || !cb.classList.contains('ls-pick')) return;
        document.querySelectorAll('.ls-pick').forEach(other => {
            if (other === cb) return;
            other.checked = cb.checked && other.dataset.id === cb.dataset.id;
        });
    });

    function lsAddBulkRow() {
        const rows = document.getElementById('bulkNameRows');
        if (!rows) return;
        const input = rows.querySelector('input').cloneNode();
        input.value = '';
        input.required = false;
        rows.appendChild(input);
        input.focus();
    }

    function lsOpenBulkAdd() {
        const picked = document.querySelector('.ls-pick:checked');
        if (!picked) {
            alert(@json('Tick a '.strtolower($bulkParent ?? 'row').' first, then click Add '.($bulkChild ?? '').'.'));
            return;
        }
        document.getElementById('bulkPickedId').value = picked.dataset.id;
        document.getElementById('bulkPickedName').textContent = picked.dataset.name;
        const rows = document.getElementById('bulkNameRows');
        rows.querySelectorAll('input').forEach((inp, i) => {
            if (i === 0) inp.value = '';
            else inp.remove();
        });
        openModal('bulkAddModal');
    }

    // ── Drag-to-reorder (Topics @ L1, Lessons @ L2) ─────────────────────────
    (function () {
        const reorderType = @json($reorderType ?? null); // 'topic' | 'lesson' | null
        if (!reorderType) return;

        const url   = "{{ route('course-architect.lesson-studio.folder.reorder') }}";
        const token = "{{ csrf_token() }}";
        const listTableKey = @json($listTableKey ?? null);

        const persist = async (ids) => {
            try {
                await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ type: reorderType, ids }),
                });
            } catch (e) { console.error('Reorder failed', e); }
        };

        const renumberCards = () => {
            document.querySelectorAll('#folderCardView .folder-item .card-seq').forEach((el, i) => {
                el.textContent = i + 1;
            });
        };
        const renumberRows = (tbody) => {
            tbody.querySelectorAll('tr.shareable-row .row-seq').forEach((el, i) => {
                el.textContent = i + 1;
            });
        };

        const init = () => {
            if (typeof Sortable === 'undefined') return setTimeout(init, 80);

            const card  = document.getElementById('folderCardView');
            const tbody = listTableKey ? document.getElementById(listTableKey + 'SortBody') : null;

            const sync = (ids, target) => {
                if (!target) return;
                ids.forEach(id => {
                    const el = target.querySelector(`[data-id="${id}"]`);
                    if (el) target.appendChild(el);
                });
            };

            if (card) {
                Sortable.create(card, {
                    animation: 180,
                    handle: '.drag-handle',
                    draggable: '.folder-item',
                    ghostClass: 'opacity-40',
                    onEnd: () => {
                        const ids = Array.from(card.querySelectorAll(':scope > .folder-item'))
                            .map(el => parseInt(el.dataset.id, 10))
                            .filter(Boolean);
                        renumberCards();
                        if (tbody) { sync(ids, tbody); renumberRows(tbody); }
                        persist(ids);
                    },
                });
            }

            if (tbody) {
                Sortable.create(tbody, {
                    animation: 180,
                    handle: '.drag-handle',
                    draggable: 'tr.shareable-row',
                    ghostClass: 'opacity-40',
                    onEnd: () => {
                        const ids = Array.from(tbody.querySelectorAll('tr.shareable-row'))
                            .map(el => parseInt(el.dataset.id, 10))
                            .filter(Boolean);
                        renumberRows(tbody);
                        if (card) { sync(ids, card); renumberCards(); }
                        persist(ids);
                    },
                });
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();

    // ── View toggle (Card ↔ List) — binds every [data-view-toggle] instance ───
    (function () {
        const cardView = document.getElementById('folderCardWrap');
        const listView = document.getElementById('folderListView');
        if (!cardView || !listView) return;

        const STORAGE_KEY = 'lessonStudio.folderView';

        const apply = (mode) => {
            const isList = mode === 'list';
            cardView.classList.toggle('hidden', isList);
            listView.classList.toggle('hidden', !isList);

            document.querySelectorAll('[data-view-toggle]').forEach(wrap => {
                const icon  = wrap.querySelector('[data-role="icon"]');
                const label = wrap.querySelector('[data-role="label"]');
                if (icon)  icon.setAttribute('data-lucide', isList ? 'list' : 'layout-grid');
                if (label) label.textContent = isList ? 'List' : 'Card';
            });
            if (window.lucide?.createIcons) lucide.createIcons();

            try { localStorage.setItem(STORAGE_KEY, mode); } catch (e) {}
        };

        const bind = (wrap) => {
            const btn  = wrap.querySelector('[data-role="btn"]');
            const menu = wrap.querySelector('[data-role="menu"]');
            if (!btn || !menu) return;

            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('[data-view-toggle] [data-role="menu"]').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                menu.classList.toggle('hidden');
            });
            menu.querySelectorAll('button[data-view]').forEach(b => {
                b.addEventListener('click', () => {
                    apply(b.dataset.view);
                    menu.classList.add('hidden');
                });
            });
        };

        document.querySelectorAll('[data-view-toggle]').forEach(bind);
        document.addEventListener('click', () => {
            document.querySelectorAll('[data-view-toggle] [data-role="menu"]').forEach(m => m.classList.add('hidden'));
        });

        let saved = 'card';
        try { saved = localStorage.getItem(STORAGE_KEY) || 'card'; } catch (e) {}
        apply(saved);
    })();

    if (window.lucide?.createIcons) lucide.createIcons();
</script>
@endsection
