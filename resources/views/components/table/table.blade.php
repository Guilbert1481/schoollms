<style>
    .action-column {
        width: 150px;
        min-width: 150px;
        max-width: 150px;
        white-space: nowrap;
    }
</style>

<div class="bg-white border border-gray-200 rounded-xl p-4">

    {{-- Top Bar (filter + columns + create). Hidden for read-only views
         like the student transcript via :hideToolbar="true". --}}
    @if(empty($hideToolbar ?? false))
    <div class="flex justify-between items-center mb-3 gap-3">
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <input type="text"
                   id="{{ $tableKey }}Filter"
                   placeholder="Filter..."
                   class="border border-gray-300 rounded px-3 py-2 w-64 text-sm">

            {{-- Optional content rendered immediately to the right of the
                 filter input (e.g. term pills on the Admissions list). --}}
            @isset($afterFilter)
                <div class="flex flex-wrap items-center gap-2 min-w-0">
                    {{ $afterFilter }}
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
        <table id="{{ $tableKey }}Table" class="w-full border table-fixed">

            {{-- LOCK COLUMN WIDTHS --}}
            <colgroup id="{{ $tableKey }}ColGroup">
                @if(!empty($reorderable))
                    <col data-table="{{ $tableKey }}" data-col-key="__drag" style="width: 36px;">
                @endif
                @if(!empty($rowNumbers))
                    <col data-table="{{ $tableKey }}" data-col-key="__num" style="width: 56px;">
                @endif
                @foreach($columns as $index => $col)
                    <col data-index="{{ $index }}"
                         data-table="{{ $tableKey }}"
                         data-col-key="{{ $col['key'] }}">
                @endforeach
                @if(empty($hideActions))
                    <col data-table="{{ $tableKey }}"
                         data-col-key="actions"
                         style="width: 150px;">
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
    const allRows  = tbody ? Array.from(tbody.rows) : [];
    const perPage  = pagerEl ? parseInt(pagerEl.dataset.perPage, 10) || 0 : 0;
    let currentPage = 1;
    let filterText  = '';

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

        if (pagerEl) renderPager(total, pages);
    }

    function renderPager(total, pages) {
        if (total === 0) {
            pagerEl.innerHTML =
                `<div class="text-slate-500">No results.</div>`;
            return;
        }
        const from = (currentPage - 1) * perPage + 1;
        const to   = Math.min(currentPage * perPage, total);

        const btn = (label, page, opts = {}) => {
            const disabled = opts.disabled ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100';
            const active   = opts.active ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-white text-slate-700';
            return `<button type="button"
                            data-page="${page}"
                            ${opts.disabled ? 'disabled' : ''}
                            class="min-w-[2rem] h-8 px-2 rounded border border-slate-200 ${active} ${disabled} text-xs">${label}</button>`;
        };

        // Compact page list with ellipses
        const pageBtns = [];
        const push = (p) => pageBtns.push(btn(p, p, { active: p === currentPage }));
        const ell  = () => pageBtns.push(`<span class="px-1 text-slate-400">…</span>`);

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

        pagerEl.innerHTML = `
            <div class="text-slate-500">Showing <span class="font-semibold">${from}–${to}</span> of <span class="font-semibold">${total}</span></div>
            <div class="flex items-center gap-1">
                ${btn('‹ Prev', currentPage - 1, { disabled: currentPage === 1 })}
                ${pageBtns.join('')}
                ${btn('Next ›', currentPage + 1, { disabled: currentPage === pages })}
            </div>`;

        pagerEl.querySelectorAll('button[data-page]').forEach(b => {
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