<style>
    .action-column {
        width: 150px;
        min-width: 150px;
        max-width: 150px;
        white-space: nowrap;
    }

    /* Column resize grip — a visible handle on each header's right edge so it's
       obvious where to drag. Wide hit area; the line highlights on hover. */
    .col-resizer {
        position: absolute;
        top: 0;
        right: 0;
        height: 100%;
        width: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: col-resize;
        z-index: 10;
        user-select: none;
        touch-action: none;
    }
    .col-resizer-line {
        width: 2px;
        height: 55%;
        border-radius: 9999px;
        background: #cbd5e1; /* slate-300: faint divider, always visible */
        transition: background .12s ease, height .12s ease, width .12s ease;
    }
    .col-resizer:hover .col-resizer-line {
        width: 3px;
        height: 100%;
        background: #6366f1; /* indigo-500 on hover */
    }
    .col-resizer:active .col-resizer-line {
        width: 3px;
        height: 100%;
        background: #4f46e5; /* indigo-600 while dragging */
    }

    /* Columns fill the full table width (no right-hand gap) via % widths; this
       min-width (= the columns' px sum, set inline per table) lets a wide table
       scroll instead of squishing. */
    .tbl-scroll { min-width: var(--tbl-min, 0); }

    /* ================================================================
       Mobile responsiveness — written with real @media queries and
       custom classes (NOT Tailwind sm:/md: variants, which may be
       absent from the compiled Vite build). Breakpoint: < 768px = mobile.
       Desktop (>= 768px) renders exactly as before.
       ================================================================ */
    @media (max-width: 767.98px) {
        /* Row actions: hide the inline icon row — the kebab takes over. */
        .tbl-actions-inline { display: none !important; }

        /* Page-level filters collapse behind the "Filters" button. */
        .tbl-filters-toggle { display: inline-flex !important; }
        .tbl-filters-panel {
            display: none;
            position: absolute;
            left: 0;
            right: auto;
            top: calc(100% + 6px);
            z-index: 40;
            width: 15rem;
            max-width: 85vw;
            flex-direction: column;
            align-items: stretch;
            gap: .5rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: .75rem;
            box-shadow: 0 12px 28px -8px rgba(15, 23, 42, .25);
            padding: .625rem;
        }
        .tbl-filters-panel.is-open { display: flex; }
        .tbl-filters-panel select { width: 100%; }
    }
    @media (min-width: 768px) {
        /* Desktop: kebab/toggle hidden, filters stay inline — unchanged. */
        .tbl-actions-kebab  { display: none !important; }
        .tbl-filters-toggle { display: none !important; }
        .tbl-filters-panel  { display: flex !important; }
    }
</style>

@php
    // Column widths drive a proportional fill so the columns always span the
    // full table (no right-hand gap), while their px sum becomes the scroll
    // min-width so a wide table scrolls instead of squishing.
    $tblParseW = fn ($w) => (is_string($w) && preg_match('/(\d+(?:\.\d+)?)/', (string) $w, $m)) ? (float) $m[1] : 150;
    $tblDragW  = !empty($reorderable) ? 36 : 0;
    $tblNumW   = !empty($rowNumbers) ? 56 : 0;
    $tblActW   = empty($hideActions) ? 150 : 0;
    $tblTotalW = max(1, $tblDragW + $tblNumW + $tblActW
        + array_sum(array_map(fn ($c) => $tblParseW($c['width'] ?? null), $columns)));
    $tblPct    = fn ($px) => round($px / $tblTotalW * 100, 4).'%';
@endphp

<div class="bg-white border border-gray-200 rounded-xl p-4">

    {{-- Top Bar (filter + columns + create). Hidden for read-only views
         like the student transcript via :hideToolbar="true". --}}
    @if(empty($hideToolbar ?? false))
    <div class="flex flex-wrap justify-between items-center mb-3 gap-3">
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <input type="text"
                   id="{{ $tableKey }}Filter"
                   placeholder="Filter..."
                   class="border border-gray-300 rounded px-3 py-2 w-64 max-w-full text-sm">

            {{-- Optional content rendered immediately to the right of the
                 filter input (e.g. term pills / filter selects). Inline on
                 desktop; collapses behind a "Filters" button on mobile. --}}
            @isset($afterFilter)
                <div class="relative" x-data="{ filtersOpen: false }" @click.outside="filtersOpen = false">
                    {{-- Mobile-only trigger (hidden on desktop via scoped CSS). --}}
                    <button type="button" @click="filtersOpen = ! filtersOpen"
                            class="tbl-filters-toggle items-center gap-2 rounded border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
                        <i data-lucide="filter" class="h-4 w-4 text-indigo-600"></i>
                        Filters
                        <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-slate-400"></i>
                    </button>
                    {{-- Inline on desktop; dropdown panel on mobile. --}}
                    <div class="tbl-filters-panel flex flex-wrap items-center gap-2 min-w-0"
                         :class="{ 'is-open': filtersOpen }">
                        {{ $afterFilter }}
                    </div>
                </div>
            @endisset
        </div>

        <div class="flex gap-2">
            {{-- Add Button --}}
            @if(isset($createRoute))
                <a href="{{ route($createRoute) }}"
                   class="px-4 py-2 rounded text-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    {{ $createLabel ?? 'Add New' }}
                </a>
            @elseif(isset($createModal))
                <button type="button"
                        onclick="openModal('{{ $createModal }}')"
                        class="px-4 py-2 rounded text-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    {{ $createLabel ?? 'Add New' }}
                </button>
            @endif

            {{-- Extra header buttons (slot) --}}
            @isset($slot)
                {{ $slot }}
            @endisset
        </div>
    </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table id="{{ $tableKey }}Table" class="w-full border table-fixed tbl-scroll"
               style="--tbl-min: {{ $tblTotalW }}px; width: 100%; table-layout: fixed;">

            {{-- LOCK COLUMN WIDTHS --}}
            <colgroup id="{{ $tableKey }}ColGroup">
                @if(!empty($reorderable))
                    <col data-table="{{ $tableKey }}" data-col-key="__drag" style="width: {{ $tblPct($tblDragW) }};">
                @endif
                @if(!empty($rowNumbers))
                    <col data-table="{{ $tableKey }}" data-col-key="__num" style="width: {{ $tblPct($tblNumW) }};">
                @endif
                @foreach($columns as $index => $col)
                    <col data-index="{{ $index }}"
                         data-table="{{ $tableKey }}"
                         data-col-key="{{ $col['key'] }}"
                         style="width: {{ $tblPct($tblParseW($col['width'] ?? null)) }};">
                @endforeach
                @if(empty($hideActions))
                    <col data-table="{{ $tableKey }}"
                         data-col-key="actions"
                         style="width: {{ $tblPct($tblActW) }};">
                @endif
            </colgroup>

            {{-- Header --}}
            <x-table.table-header 
                :columns="$columns"
                :tableKey="$tableKey"
                :hideActions="!empty($hideActions)"
                :rowNumbers="!empty($rowNumbers)"
                :reorderable="!empty($reorderable)"
            />

            {{-- Body --}}
            <x-table.table-body
                :columns="$columns"
                :data="$data"
                :tableKey="$tableKey"
                :actions="$actions ?? []"
                :editRoute="$editRoute ?? null"
                :editModal="$editModal ?? null"
                :deleteRoute="$deleteRoute ?? null"
                :hideActions="!empty($hideActions)"
                :rowNumbers="!empty($rowNumbers)"
                :reorderable="!empty($reorderable)"
                :emptyMessage="$emptyMessage ?? null"
            />

        </table>
    </div>

    {{-- Pagination (rendered by JS when perPage prop is provided) --}}
    @isset($perPage)
        <div id="{{ $tableKey }}Pagination"
             class="mt-3 flex items-center justify-between gap-2 text-sm"
             data-per-page="{{ (int) $perPage }}"></div>
    @endisset


</div>

{{-- Filter + (optional) Pagination Script --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableKey    = @json($tableKey);
    const filterInput = document.getElementById(tableKey + 'Filter');
    const table       = document.getElementById(tableKey + 'Table');
    const pagerEl     = document.getElementById(tableKey + 'Pagination');

    if (!table) return;

    const tbody    = table.tBodies[0];
    // The "no records" placeholder row is never paginated/filtered.
    const allRows  = tbody ? Array.from(tbody.rows).filter(r => !r.classList.contains('js-empty-row')) : [];

    const ROWS_KEY    = 'tblperpage:' + tableKey;
    const basePerPage = pagerEl ? parseInt(pagerEl.dataset.perPage, 10) || 0 : 0;
    let perPage     = basePerPage;
    let currentPage = 1;
    let filterText  = '';

    // Build the pager shell once: left = "Rows" input + info, right = buttons.
    // The user-chosen rows-per-page is remembered per table.
    let infoEl = null, buttonsEl = null;
    if (pagerEl && basePerPage > 0) {
        const saved = parseInt(localStorage.getItem(ROWS_KEY), 10);
        if (saved > 0) perPage = saved;

        pagerEl.innerHTML =
            '<div class="flex items-center gap-2">' +
                '<label class="text-slate-500">Rows</label>' +
                '<input type="number" min="1" id="' + tableKey + 'PerPage" value="' + perPage + '" ' +
                    'class="w-16 rounded border border-slate-300 px-2 py-1 text-sm">' +
                '<span class="text-slate-500" id="' + tableKey + 'PagerInfo"></span>' +
            '</div>' +
            '<div class="flex items-center gap-1" id="' + tableKey + 'PagerButtons"></div>';

        infoEl    = document.getElementById(tableKey + 'PagerInfo');
        buttonsEl = document.getElementById(tableKey + 'PagerButtons');

        document.getElementById(tableKey + 'PerPage').addEventListener('change', function () {
            const v = parseInt(this.value, 10);
            perPage = (v > 0) ? v : basePerPage;
            this.value = perPage;
            localStorage.setItem(ROWS_KEY, perPage);
            currentPage = 1;
            render();
        });
    }

    function visibleRows() {
        if (!filterText) return allRows.slice();
        return allRows.filter(r => r.innerText.toLowerCase().includes(filterText));
    }

    function render() {
        const visible  = visibleRows();
        const total    = visible.length;
        const pages    = perPage > 0 ? Math.max(1, Math.ceil(total / perPage)) : 1;
        if (currentPage > pages) currentPage = pages;
        if (currentPage < 1)     currentPage = 1;

        // Hide everything first
        allRows.forEach(r => r.style.display = 'none');

        if (perPage > 0) {
            const start = (currentPage - 1) * perPage;
            visible.slice(start, start + perPage).forEach(r => r.style.display = '');
        } else {
            visible.forEach(r => r.style.display = '');
        }

        if (infoEl && buttonsEl) renderPager(total, pages);
    }

    function renderPager(total, pages) {
        if (total === 0) {
            infoEl.textContent = 'No results.';
            buttonsEl.innerHTML = '';
            return;
        }
        const from = (currentPage - 1) * perPage + 1;
        const to   = Math.min(currentPage * perPage, total);
        infoEl.innerHTML = 'Showing <span class="font-semibold">' + from + '–' + to + '</span> of <span class="font-semibold">' + total + '</span>';

        const btn = (label, page, opts = {}) => {
            const disabled = opts.disabled ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100';
            const active   = opts.active ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-white text-slate-700';
            return '<button type="button" data-page="' + page + '" ' + (opts.disabled ? 'disabled' : '') +
                   ' class="min-w-[2rem] h-8 px-2 rounded border border-slate-200 ' + active + ' ' + disabled + ' text-xs">' + label + '</button>';
        };

        // Compact page list with ellipses
        const pageBtns = [];
        const push = (p) => pageBtns.push(btn(p, p, { active: p === currentPage }));
        const ell  = () => pageBtns.push('<span class="px-1 text-slate-400">…</span>');

        if (pages <= 7) {
            for (let p = 1; p <= pages; p++) push(p);
        } else {
            push(1);
            if (currentPage > 4) ell();
            const s = Math.max(2, currentPage - 1);
            const e = Math.min(pages - 1, currentPage + 1);
            for (let p = s; p <= e; p++) push(p);
            if (currentPage < pages - 3) ell();
            push(pages);
        }

        buttonsEl.innerHTML =
            btn('‹ Prev', currentPage - 1, { disabled: currentPage === 1 }) +
            pageBtns.join('') +
            btn('Next ›', currentPage + 1, { disabled: currentPage === pages });

        buttonsEl.querySelectorAll('button[data-page]').forEach(b => {
            b.addEventListener('click', () => {
                const p = parseInt(b.dataset.page, 10);
                if (!isNaN(p)) { currentPage = p; render(); }
            });
        });
    }

    if (filterInput) {
        filterInput.addEventListener('keyup', function () {
            filterText = this.value.toLowerCase();
            currentPage = 1;
            render();
        });
    }

    render();
});
</script>