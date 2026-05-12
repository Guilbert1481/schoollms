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
                Program Subjects
            </h1>
            <p class="text-sm text-slate-500 mt-1">Manage Subjects → Topics → Lessons → Competencies.</p>
        </div>
    </div>

    {{-- Breadcrumb --}}
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

    @php
        // Topics (L1), Lessons (L2), and Competencies (L3) are reorderable.
        // Subjects (L0) order is alphabetical (no sort_order column).
        $reorderType = match ($level) {
            1 => 'topic',
            2 => 'lesson',
            3 => 'competency',
            default => null,
        };
        $createLabel = [
            0 => '+ Add Subject',
            1 => '+ Add Topic',
            2 => '+ Add Lesson Folder',
            3 => '+ Add Competency',
        ][$level] ?? '+ Add';
        $deleteType = ['subject', 'topic', 'lesson', 'competency'][$level] ?? null;
    @endphp

    @if($level === 0)
        @php
            // Convert the Status column header into a clickable dropdown filter.
            // The shareable table-header component supports `filter` of type dropdown natively.
            foreach ($listColumns as &$col) {
                if (($col['key'] ?? null) === 'status') {
                    $col['filter'] = [
                        'type'    => 'dropdown',
                        'param'   => 'status',
                        'default' => 'all',
                        'options' => [
                            'all'      => 'All',
                            'active'   => 'Active',
                            'inactive' => 'Inactive',
                        ],
                    ];
                }
            }
            unset($col);
        @endphp
    @endif

    @if(count($folders) === 0)
        <div class="rounded-2xl bg-white/60 backdrop-blur-xl ring-1 ring-white/60 shadow-sm p-12 text-center">
            <i data-lucide="folder-open" class="w-10 h-10 text-slate-300 mx-auto"></i>
            <p class="mt-3 text-slate-500 text-sm">
                @if($level === 0) No subjects @if(($status ?? 'all') !== 'all') with status "{{ $status }}" @endif yet.
                @elseif($level === 1) No topics in this subject yet.
                @elseif($level === 2) No lessons in this topic yet.
                @else No competencies in this lesson yet.
                @endif
            </p>
            <button type="button" onclick="openModal('phNewFolderModal')"
                    class="mt-3 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-md hover:bg-indigo-700">
                {{ $createLabel }}
            </button>
        </div>
    @else
        {{-- CARD VIEW --}}
        <div id="folderCardWrap">
            {{-- Toolbar: view toggle + Add button (right side) --}}
            <div class="flex items-center justify-end gap-2 mb-2">
                @include('program_head.lesson-studio.partials._view-toggle', ['idSuffix' => 'Card'])
                <button type="button" onclick="openModal('phNewFolderModal')"
                        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                    {{ $createLabel }}
                </button>
            </div>

            <div id="folderCardView" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($folders as $i => $f)
                    <div class="folder-item relative" data-id="{{ $f['id'] }}"
                         data-name="{{ \Illuminate\Support\Str::lower($f['name'] . ' ' . ($f['subtitle'] ?? '')) }}">
                        @if($reorderType)
                            <button type="button"
                                    class="drag-handle absolute top-2 right-[4.5rem] z-10 w-7 h-7 rounded-lg bg-white/80 ring-1 ring-slate-200 text-slate-400 hover:text-indigo-600 hover:bg-white flex items-center justify-center cursor-grab active:cursor-grabbing"
                                    title="Drag to reorder"
                                    onclick="event.preventDefault(); event.stopPropagation();">
                                <i data-lucide="grip-vertical" class="w-4 h-4 pointer-events-none"></i>
                            </button>
                            <span class="card-seq absolute top-2 left-2 z-10 inline-flex items-center justify-center min-w-[1.5rem] h-6 px-1.5 rounded-md bg-indigo-50 ring-1 ring-indigo-100 text-[11px] font-semibold text-indigo-700">
                                {{ $i + 1 }}
                            </span>
                        @endif

                        {{-- Edit --}}
                        <button type="button"
                                class="absolute top-2 right-10 z-10 w-7 h-7 rounded-lg bg-white/80 ring-1 ring-blue-200 text-blue-500 hover:text-blue-700 hover:bg-blue-50 flex items-center justify-center"
                                title="Edit"
                                onclick="event.preventDefault(); event.stopPropagation(); phLessonStudioEdit({{ $f['id'] }})">
                            <i data-lucide="pencil" class="w-4 h-4 pointer-events-none"></i>
                        </button>

                        {{-- Delete --}}
                        <button type="button"
                                class="absolute top-2 right-2 z-10 w-7 h-7 rounded-lg bg-white/80 ring-1 ring-rose-200 text-rose-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center"
                                title="Delete"
                                onclick="event.preventDefault(); event.stopPropagation(); phLessonStudioDelete({{ $f['id'] }})">
                            <i data-lucide="trash-2" class="w-4 h-4 pointer-events-none"></i>
                        </button>

                        @if($f['url'] !== '#')
                            <a href="{{ $f['url'] }}" draggable="false"
                               class="group relative block overflow-hidden rounded-2xl bg-white/70 backdrop-blur-xl ring-1 ring-white/60 shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 p-5">
                        @else
                            <div class="relative block overflow-hidden rounded-2xl bg-white/70 backdrop-blur-xl ring-1 ring-white/60 shadow-sm p-5">
                        @endif
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center shadow-md">
                                    <i data-lucide="folder" class="w-6 h-6 text-white"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-slate-900 truncate">{{ $f['name'] }}</div>
                                    @if(!empty($f['subtitle']))
                                        <div class="text-xs text-slate-500 mt-0.5 truncate">{{ $f['subtitle'] }}</div>
                                    @endif
                                    @if($level === 0)
                                        <div class="mt-2">
                                            @if(! empty($f['is_active']))
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">Active</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-rose-50 text-rose-700 ring-1 ring-rose-200">Inactive</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if($level < 3)
                                        <div class="text-xs text-slate-500 mt-2">
                                            {{ $f['count'] }} {{ \Illuminate\Support\Str::plural($childLabel, $f['count']) }}
                                        </div>
                                    @endif
                                </div>
                                @if($f['url'] !== '#')
                                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300"></i>
                                @endif
                            </div>
                        @if($f['url'] !== '#')</a>@else</div>@endif
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
                :rowNumbers="true"
                :reorderable="(bool) $reorderType"
                :actions="config('tables.table-actions.lesson_studio_folder')"
            >
                @include('program_head.lesson-studio.partials._view-toggle', ['idSuffix' => 'List'])
                <button type="button" onclick="openModal('phNewFolderModal')"
                        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                    {{ $createLabel }}
                </button>
            </x-table.table>
        </div>
    @endif

    {{-- New + Edit modals --}}
    @include('program_head.lesson-studio.partials.new-folder-modal')
    @include('program_head.lesson-studio.partials.edit-folder-modal')

</div>

<script>
    // Bridge data used by per-row delete/edit buttons.
    @php $deleteTypeJs = $deleteType; @endphp
    window.__PH_LS__ = {
        level:        {{ (int) $level }},
        deleteType:   @json($deleteTypeJs),
        deleteUrl:    "{{ url('staff/program-head/lesson-studio/folder') }}",
        updateUrl:    "{{ url('staff/program-head/lesson-studio/folder') }}",
        csrf:         "{{ csrf_token() }}",
        listTableKey: @json($listTableKey),
    };

    // Pre-loaded item data, keyed by id, used to populate the edit modal.
    @php
        $itemsForJs = collect($folders ?? [])->mapWithKeys(fn ($f) => [$f['id'] => [
            'id'          => $f['id'],
            'name'        => $f['name'],
            'code'        => $f['code']        ?? null,
            'category'    => $f['category']    ?? null,
            'description' => $f['description'] ?? null,
            'is_active'   => array_key_exists('is_active', $f) ? (bool) $f['is_active'] : null,
        ]])->all();
    @endphp
    window.__PH_LS_ITEMS__ = @json((object) $itemsForJs);

    function phLessonStudioEdit(id) {
        const item = window.__PH_LS_ITEMS__[id];
        const type = window.__PH_LS__.deleteType;
        if (!item || !type) return;

        const form = document.getElementById('phEditFolderForm');
        if (!form) return;
        form.action = `${window.__PH_LS__.updateUrl}/${type}/${id}`;

        const set = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            if (el.type === 'checkbox') el.checked = !!value;
            else el.value = value ?? '';
        };
        set('name',        item.name);
        set('code',        item.code);
        set('category',    item.category || 'major');
        set('description', item.description);
        set('is_active',   item.is_active === null ? true : item.is_active);

        openModal('phEditFolderModal');
    }

    function phLessonStudioDelete(id) {
        const type = window.__PH_LS__.deleteType;
        const item = window.__PH_LS_ITEMS__[id] || { name: 'this item' };
        if (!type) return;
        const labels = { subject: 'subject', topic: 'topic', lesson: 'lesson', competency: 'competency' };
        const noun = labels[type] || 'item';
        const warn = type === 'subject'
            ? `Delete the ${noun} "${item.name}"? All topics, lessons, competencies, and uploaded files will be permanently removed.`
            : type === 'topic'
                ? `Delete the ${noun} "${item.name}"? All lessons, competencies, and uploaded files will be removed.`
                : type === 'lesson'
                    ? `Delete the ${noun} "${item.name}"? All competencies and uploaded files will be removed.`
                    : `Delete the ${noun} "${item.name}"?`;
        if (!confirm(warn)) return;

        const f = document.createElement('form');
        f.method = 'POST';
        f.action = `${window.__PH_LS__.deleteUrl}/${type}/${id}`;
        f.innerHTML =
            `<input type="hidden" name="_token" value="${window.__PH_LS__.csrf}">` +
            `<input type="hidden" name="_method" value="DELETE">`;
        document.body.appendChild(f);
        f.submit();
    }

    // ── Drag-to-reorder (Topics @ L1, Lessons @ L2, Competencies @ L3) ──
    (function () {
        const reorderType = @json($reorderType);
        if (!reorderType) return;

        const url   = "{{ route('program_head.lesson-studio.folder.reorder') }}";
        const token = "{{ csrf_token() }}";
        const listTableKey = window.__PH_LS__.listTableKey;

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

    // ── View toggle (Card ↔ List) ─────────────────────────
    (function () {
        const cardView = document.getElementById('folderCardWrap');
        const listView = document.getElementById('folderListView');
        if (!cardView || !listView) return;

        const STORAGE_KEY = 'phLessonStudio.folderView';
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
