@extends('layouts.app')

@section('content')
@php
    $currency = \App\Helpers\CurrencyHelper::forCurrentSchool();
    $currencyCode = $setting->currency ?: $currency['code'];

    $categories = $program['categories'] ?? ['all'];
    $subjects   = $program['subjects'] ?? [];
@endphp

<div class="w-full space-y-5">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <nav class="text-xs text-slate-500 mb-2">
                <a href="{{ route('training.trainee.available-courses') }}" class="hover:underline">Available Courses</a>
                <span class="mx-1">/</span>
                <a href="{{ route('training.trainee.available-courses.programs', ['session' => $setting->id]) }}"
                   class="hover:underline">
                    {{ $setting->title ?: $setting->name }}
                </a>
                <span class="mx-1">/</span>
                <span class="text-slate-700 font-semibold">{{ $program['name'] }}</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800">{{ $program['name'] }}</h1>
            <p class="text-sm text-slate-500">
                {{ $program['description'] ?? '' }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            {{-- Columns --}}
            <div class="relative" id="columnsWrap">
                <button type="button" id="columnsBtn"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    Columns
                </button>
                <div id="columnsMenu"
                     class="hidden absolute right-0 top-full mt-2 w-56 rounded-xl border border-slate-200 bg-white p-3 shadow-lg z-20">
                    <p class="text-xs font-semibold text-slate-500 mb-2">Show columns</p>
                    <label class="flex items-center gap-2 text-sm py-1"><input type="checkbox" data-col="select"   checked class="col-toggle"> Select</label>
                    <label class="flex items-center gap-2 text-sm py-1"><input type="checkbox" data-col="name"     checked class="col-toggle"> Subject Name</label>
                    <label class="flex items-center gap-2 text-sm py-1"><input type="checkbox" data-col="topics"   checked class="col-toggle"> Topics</label>
                    <label class="flex items-center gap-2 text-sm py-1"><input type="checkbox" data-col="questions" checked class="col-toggle"> Questions</label>
                    <label class="flex items-center gap-2 text-sm py-1"><input type="checkbox" data-col="amount"   checked class="col-toggle"> Amount</label>
                </div>
            </div>

            {{-- Display toggle --}}
            <div class="inline-flex rounded-lg border border-slate-300 overflow-hidden bg-white">
                <button type="button" id="displayTableBtn"
                        class="px-3 py-2 text-sm font-semibold text-white bg-indigo-600 display-btn"
                        data-mode="table">
                    Table
                </button>
                <button type="button" id="displayCardBtn"
                        class="px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 display-btn"
                        data-mode="card">
                    Cards
                </button>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex flex-wrap gap-2" aria-label="Tabs">
            @foreach($categories as $cat)
                <button type="button"
                        class="category-tab whitespace-nowrap border-b-2 px-4 py-2 text-sm font-semibold
                               {{ $cat === 'all' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                        data-category="{{ $cat }}">
                    {{ $labels[$cat] ?? ucfirst(str_replace('_', ' ', $cat)) }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Search + bulk --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <input type="text" id="subjectSearch"
                   placeholder="Search subjects…"
                   class="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <span class="text-xs text-slate-500">
                <span id="resultCount">0</span> subjects
            </span>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-sm text-slate-600">
                Selected: <span id="selectedCount" class="font-bold text-indigo-700">0</span>
                · Total:
                <span id="selectedTotal" class="font-bold text-indigo-700">
                    {{ $currencyCode }} 0.00
                </span>
            </span>
            <button type="button" id="reviewSelectionBtn"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                    disabled>
                Review & Enroll
            </button>
        </div>
    </div>

    {{-- Table View --}}
    <div id="tableView" class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="w-10 px-3 py-3 text-left col-select">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th class="px-3 py-3 text-left col-name">Subject</th>
                        <th class="px-3 py-3 text-left col-topics">Topics</th>
                        <th class="px-3 py-3 text-left col-questions">Questions</th>
                        <th class="px-3 py-3 text-right col-amount">Amount</th>
                    </tr>
                </thead>
                <tbody id="subjectTableBody" class="divide-y divide-slate-100">
                    @foreach($subjects as $i => $sub)
                        <tr class="subject-row hover:bg-slate-50 transition"
                            data-category="{{ $sub['category'] }}"
                            data-name="{{ strtolower($sub['name']) }}"
                            data-amount="{{ (float) $sub['amount'] }}"
                            data-code="{{ $sub['code'] }}">
                            <td class="px-3 py-3 col-select">
                                <input type="checkbox" class="subject-check"
                                       data-amount="{{ (float) $sub['amount'] }}"
                                       data-name="{{ $sub['name'] }}"
                                       data-code="{{ $sub['code'] }}">
                            </td>
                            <td class="px-3 py-3 col-name">
                                <div class="font-semibold text-slate-800">{{ $sub['name'] }}</div>
                                <div class="text-xs text-slate-500">{{ $sub['code'] }}</div>
                            </td>
                            <td class="px-3 py-3 col-topics">{{ $sub['topics'] }}</td>
                            <td class="px-3 py-3 col-questions">{{ $sub['questions'] }}</td>
                            <td class="px-3 py-3 text-right col-amount font-semibold">
                                {{ $currencyCode }} {{ number_format((float) $sub['amount'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex items-center justify-between px-4 py-3 border-t border-slate-100 text-sm text-slate-600">
            <div>
                Page <span id="currentPage">1</span> of <span id="totalPages">1</span>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs">Per page</label>
                <select id="perPage" class="rounded-lg border border-slate-300 px-2 py-1 text-xs">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <button type="button" id="prevPage" class="rounded-lg border border-slate-300 px-3 py-1 text-xs hover:bg-slate-50">Prev</button>
                <button type="button" id="nextPage" class="rounded-lg border border-slate-300 px-3 py-1 text-xs hover:bg-slate-50">Next</button>
            </div>
        </div>
    </div>

    {{-- Card View --}}
    <div id="cardView" class="hidden grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($subjects as $sub)
            <article class="subject-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-lg transition-all"
                     data-category="{{ $sub['category'] }}"
                     data-name="{{ strtolower($sub['name']) }}"
                     data-code="{{ $sub['code'] }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs text-indigo-600 font-semibold">{{ $sub['code'] }}</p>
                        <h3 class="text-base font-bold text-slate-800 mt-1">{{ $sub['name'] }}</h3>
                    </div>
                    <input type="checkbox" class="subject-check-card h-5 w-5"
                           data-amount="{{ (float) $sub['amount'] }}"
                           data-name="{{ $sub['name'] }}"
                           data-code="{{ $sub['code'] }}">
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2 text-xs text-slate-600">
                    <div class="rounded-lg bg-slate-50 px-2 py-1">Topics: <span class="font-semibold text-slate-800">{{ $sub['topics'] }}</span></div>
                    <div class="rounded-lg bg-slate-50 px-2 py-1">Questions: <span class="font-semibold text-slate-800">{{ $sub['questions'] }}</span></div>
                </div>
                <div class="mt-4 text-lg font-bold text-slate-900">
                    {{ $currencyCode }} {{ number_format((float) $sub['amount'], 2) }}
                </div>
            </article>
        @endforeach
    </div>

</div>

{{-- Review Selection Modal (uses global draggable) --}}
<div id="reviewModal" class="fixed inset-0 bg-black/40 hidden z-50">
    <div id="reviewModalDraggable"
         class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 absolute max-h-[85vh] overflow-y-auto"
         style="top:90px; left:420px;">

        <div id="reviewModalHeader" class="flex justify-between mb-4 cursor-move">
            <h2 class="font-extrabold">Review Your Selection</h2>
            <button type="button" onclick="closeReviewModal()">✕</button>
        </div>

        <div id="reviewList" class="divide-y divide-slate-100 text-sm">
            <div class="py-3 text-center text-slate-500">No subjects selected.</div>
        </div>

        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-sm">
            <span class="text-slate-600">Total</span>
            <span id="reviewTotal" class="text-lg font-extrabold text-indigo-700">{{ $currencyCode }} 0.00</span>
        </div>

        <div class="flex justify-end gap-2 mt-4">
            <button type="button" onclick="closeReviewModal()"
                    class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">
                Close
            </button>
            <button type="button"
                    class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold">
                Proceed to Enroll
            </button>
        </div>
    </div>
</div>

<script src="{{ asset('js/modules/draggable.js') }}"></script>
<script>
(function () {
    const currencyCode = @json($currencyCode);
    const perPageSelect = document.getElementById('perPage');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    const currentPageEl = document.getElementById('currentPage');
    const totalPagesEl  = document.getElementById('totalPages');
    const resultCountEl = document.getElementById('resultCount');
    const selectedCountEl = document.getElementById('selectedCount');
    const selectedTotalEl = document.getElementById('selectedTotal');
    const reviewBtn = document.getElementById('reviewSelectionBtn');
    const searchInput = document.getElementById('subjectSearch');
    const selectAll = document.getElementById('selectAll');

    const tableView = document.getElementById('tableView');
    const cardView  = document.getElementById('cardView');
    const displayBtns = document.querySelectorAll('.display-btn');

    const rows  = Array.from(document.querySelectorAll('.subject-row'));
    const cards = Array.from(document.querySelectorAll('.subject-card'));
    const tabs  = document.querySelectorAll('.category-tab');

    let activeCategory = 'all';
    let page = 1;

    function fmt(n) {
        return currencyCode + ' ' + Number(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function applyFilter() {
        const q = (searchInput.value || '').trim().toLowerCase();

        // filter both rows and cards
        const match = (el) => {
            const catOk = activeCategory === 'all' || el.dataset.category === activeCategory;
            const textOk = !q || (el.dataset.name || '').includes(q) || (el.dataset.code || '').toLowerCase().includes(q);
            return catOk && textOk;
        };

        cards.forEach(c => c.classList.toggle('hidden', !match(c)));

        const visibleRows = rows.filter(match);
        const hidden = rows.filter(r => !match(r));
        hidden.forEach(r => r.classList.add('hidden'));

        // pagination only applies to table view
        const perPage = parseInt(perPageSelect.value, 10) || 10;
        const totalPages = Math.max(1, Math.ceil(visibleRows.length / perPage));
        if (page > totalPages) page = totalPages;
        const start = (page - 1) * perPage;
        const end   = start + perPage;

        visibleRows.forEach((r, i) => {
            r.classList.toggle('hidden', i < start || i >= end);
        });

        currentPageEl.textContent = page;
        totalPagesEl.textContent = totalPages;
        resultCountEl.textContent = visibleRows.length;
    }

    function updateSelectionSummary() {
        const allChecks = document.querySelectorAll('.subject-check, .subject-check-card');
        const seen = new Map();
        allChecks.forEach(cb => {
            if (cb.checked) {
                seen.set(cb.dataset.code, parseFloat(cb.dataset.amount || '0'));
            }
        });
        const count = seen.size;
        let total = 0;
        seen.forEach(v => total += v);

        selectedCountEl.textContent = count;
        selectedTotalEl.textContent = fmt(total);
        reviewBtn.disabled = count === 0;

        // Mirror state between table and card checkboxes by code
        const codes = new Set(Array.from(seen.keys()));
        document.querySelectorAll('.subject-check, .subject-check-card').forEach(cb => {
            cb.checked = codes.has(cb.dataset.code);
        });
    }

    // Tab clicks
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => {
                t.classList.remove('border-indigo-600', 'text-indigo-600');
                t.classList.add('border-transparent', 'text-slate-500');
            });
            tab.classList.remove('border-transparent', 'text-slate-500');
            tab.classList.add('border-indigo-600', 'text-indigo-600');
            activeCategory = tab.dataset.category;
            page = 1;
            applyFilter();
        });
    });

    // Search
    searchInput.addEventListener('input', () => { page = 1; applyFilter(); });

    // Pagination
    prevBtn.addEventListener('click', () => { if (page > 1) { page--; applyFilter(); } });
    nextBtn.addEventListener('click', () => {
        const total = parseInt(totalPagesEl.textContent, 10) || 1;
        if (page < total) { page++; applyFilter(); }
    });
    perPageSelect.addEventListener('change', () => { page = 1; applyFilter(); });

    // Select all (current visible table page)
    selectAll.addEventListener('change', () => {
        rows.forEach(r => {
            if (!r.classList.contains('hidden')) {
                const cb = r.querySelector('.subject-check');
                if (cb) cb.checked = selectAll.checked;
            }
        });
        updateSelectionSummary();
    });

    // Listen to any checkbox change
    document.addEventListener('change', (e) => {
        if (e.target.classList && (e.target.classList.contains('subject-check') || e.target.classList.contains('subject-check-card'))) {
            updateSelectionSummary();
        }
    });

    // Display toggle
    displayBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            displayBtns.forEach(b => {
                b.classList.remove('bg-indigo-600', 'text-white');
                b.classList.add('text-slate-700');
            });
            btn.classList.remove('text-slate-700');
            btn.classList.add('bg-indigo-600', 'text-white');

            if (mode === 'table') {
                tableView.classList.remove('hidden');
                cardView.classList.add('hidden');
            } else {
                tableView.classList.add('hidden');
                cardView.classList.remove('hidden');
            }
        });
    });

    // Columns dropdown
    const columnsBtn  = document.getElementById('columnsBtn');
    const columnsMenu = document.getElementById('columnsMenu');
    columnsBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        columnsMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', (e) => {
        if (!document.getElementById('columnsWrap').contains(e.target)) {
            columnsMenu.classList.add('hidden');
        }
    });
    document.querySelectorAll('.col-toggle').forEach(cb => {
        cb.addEventListener('change', () => {
            const col = cb.dataset.col;
            const show = cb.checked;
            document.querySelectorAll('.col-' + col).forEach(el => {
                el.classList.toggle('hidden', !show);
            });
        });
    });

    // Review modal
    const reviewModal = document.getElementById('reviewModal');
    reviewBtn.addEventListener('click', () => {
        const list = document.getElementById('reviewList');
        const checks = Array.from(document.querySelectorAll('.subject-check'))
            .filter(cb => cb.checked);
        if (checks.length === 0) {
            list.innerHTML = '<div class="py-3 text-center text-slate-500">No subjects selected.</div>';
        } else {
            list.innerHTML = checks.map(cb => {
                const amt = parseFloat(cb.dataset.amount || '0');
                return '<div class="flex items-center justify-between py-2">' +
                       '<div><div class="font-semibold text-slate-800">' + cb.dataset.name + '</div>' +
                       '<div class="text-xs text-slate-500">' + cb.dataset.code + '</div></div>' +
                       '<div class="font-semibold">' + fmt(amt) + '</div>' +
                       '</div>';
            }).join('');
        }
        document.getElementById('reviewTotal').textContent = selectedTotalEl.textContent;
        reviewModal.classList.remove('hidden');
    });

    window.closeReviewModal = function () {
        reviewModal.classList.add('hidden');
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (window.makeDraggable) {
            makeDraggable('reviewModalDraggable', 'reviewModalHeader');
        }
    });

    // Initial render
    applyFilter();
    updateSelectionSummary();
})();
</script>
@endsection
