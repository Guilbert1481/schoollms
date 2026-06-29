@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

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

    {{-- Header --}}
    <h1 class="text-xl font-extrabold text-slate-800">Student Ledgers</h1>

    {{-- KPI cards (reusable x-kpi-row.kpi-shell). Responsive grid via scoped CSS
         so it works regardless of the compiled Tailwind build. --}}
    <style>
        .fin-kpis { display: grid; gap: 1rem; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        @media (max-width: 1023.98px) { .fin-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 575.98px)  { .fin-kpis { grid-template-columns: minmax(0, 1fr); } }
    </style>
    <div class="fin-kpis">
        <x-kpi-row.kpi-shell title="Total Students" icon="users" accent="violet" subtitle="Active Students">
            {{ number_format($kpi['students']) }}
        </x-kpi-row.kpi-shell>
        <x-kpi-row.kpi-shell title="Total Outstanding" icon="wallet" accent="sky" subtitle="Total Balance Due">
            {{ $currency }}{{ number_format($kpi['outstanding'], 2) }}
        </x-kpi-row.kpi-shell>
        <x-kpi-row.kpi-shell title="Current Month Collection" icon="banknote" accent="amber" :subtitle="$kpi['month_label']">
            {{ $currency }}{{ number_format($kpi['collection'], 2) }}
        </x-kpi-row.kpi-shell>
        <x-kpi-row.kpi-shell title="Overdue Accounts" icon="alert-circle" accent="rose" subtitle="Students with Overdue">
            {{ number_format($kpi['overdue']) }}
        </x-kpi-row.kpi-shell>
    </div>

    {{-- Education-level tabs (hidden when only one level is offered) --}}
    @if ($showTabs)
        @php
            $ledgerTabs = [[
                'label'  => 'All Levels',
                'url'    => route('finance.ledger.index', ['level' => 'all']),
                'active' => $showAll,
            ]];
            foreach ($levels as $lvl) {
                $ledgerTabs[] = [
                    'label'  => $lvl->name,
                    'url'    => route('finance.ledger.index', ['level' => $lvl->id]),
                    'active' => ! $showAll && $activeLevelId === $lvl->id,
                ];
            }
        @endphp
        <x-tabs.count-tabs :tabs="$ledgerTabs" />
    @endif

    {{-- Master list --}}
    <x-table.table
        tableKey="finance_ledgers"
        :columns="$columns"
        :data="$rows->values()"
        :actions="$actions"
        perPage="20"
        :emptyMessage="$tableEmptyMessage"
    >
        <x-slot:afterFilter>
            <select onchange="ledgerApplyFilter('status', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="all" @selected($statusFilter === 'all')>All Statuses</option>
                @foreach($statusOptions as $sKey => $sLabel)
                    <option value="{{ $sKey }}" @selected($statusFilter === $sKey)>{{ $sLabel }}</option>
                @endforeach
            </select>

            <select onchange="ledgerApplyFilter('academic_year_id', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All Academic Years</option>
                @foreach($academicYears as $ayId => $ayName)
                    <option value="{{ $ayId }}" @selected((string) $academicYearId === (string) $ayId)>{{ $ayName }}</option>
                @endforeach
            </select>

            <select onchange="ledgerApplyFilter('year_level', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All {{ $activeLevelIsBasic ? 'Grade Levels' : 'Year Levels' }}</option>
                @foreach($yearLevelOptions as $ylValue => $ylLabel)
                    <option value="{{ $ylValue }}" @selected((string) $yearLevel === (string) $ylValue)>{{ $ylLabel }}</option>
                @endforeach
            </select>

            @if($activeLevelIsBasic)
                <select onchange="ledgerApplyFilter('section_id', this.value)"
                        class="rounded border border-gray-300 px-2 py-2 text-sm">
                    <option value="">All Sections</option>
                    @foreach($sectionOptions as $secId => $secName)
                        <option value="{{ $secId }}" @selected((int) $sectionId === (int) $secId)>{{ $secName }}</option>
                    @endforeach
                </select>
            @endif

            @if($showProgramFilter)
                <select onchange="ledgerApplyFilter('program_id', this.value)"
                        class="rounded border border-gray-300 px-2 py-2 text-sm">
                    <option value="">All Programs</option>
                    @foreach($programOptions as $pid => $pname)
                        <option value="{{ $pid }}" @selected((int) $programId === (int) $pid)>{{ $pname }}</option>
                    @endforeach
                </select>
            @endif
        </x-slot:afterFilter>

        {{-- Export only (CSV / Excel / both). Import is registrar-only. --}}
        <div class="flex items-center gap-2">
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = ! open"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="download" class="h-4 w-4 text-indigo-600"></i>
                    Export
                    <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-slate-400"></i>
                </button>
                <div x-show="open" x-cloak x-transition
                     class="absolute right-0 z-30 mt-1 w-48 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                     style="display:none;">
                    <button type="button" onclick="ledgerExport('csv'); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="file-text" class="h-4 w-4 text-slate-500"></i> Export as CSV
                    </button>
                    <button type="button" onclick="ledgerExport('xlsx'); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="file-spreadsheet" class="h-4 w-4 text-emerald-600"></i> Export as Excel
                    </button>
                    <button type="button" onclick="ledgerExport('both'); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="files" class="h-4 w-4 text-indigo-600"></i> Export both
                    </button>
                </div>
            </div>
        </div>
    </x-table.table>
</div>

<script>
    const LEDGER_EXPORT_URL = @json(route('finance.ledger.export'));

    function ledgerExport(format) {
        const current = new URLSearchParams(window.location.search);
        const buildUrl = (fmt) => {
            const p = new URLSearchParams(current);
            p.set('format', fmt);
            return LEDGER_EXPORT_URL + '?' + p.toString();
        };
        const download = (url) => {
            const a = document.createElement('a');
            a.href = url;
            document.body.appendChild(a);
            a.click();
            a.remove();
        };
        if (format === 'both') {
            download(buildUrl('csv'));
            setTimeout(() => download(buildUrl('xlsx')), 500);
        } else {
            download(buildUrl(format));
        }
    }

    function ledgerApplyFilter(key, value) {
        const url = new URL(window.location.href);
        if (value === '' || value === null) {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, value);
        }
        window.location = url.toString();
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();

        const table = document.getElementById('finance_ledgersTable');
        if (table) {
            const base = @json(url('/finance/ledger'));
            table.querySelectorAll('tbody tr[data-row-id]').forEach((tr) => {
                if (tr.dataset.rowId) tr.classList.add('cursor-pointer', 'hover:bg-slate-50');
            });
            table.addEventListener('click', (e) => {
                if (e.target.closest('button, a, input, select, label, form, [data-action], .action-column')) return;
                const tr = e.target.closest('tr[data-row-id]');
                if (!tr || !tr.dataset.rowId) return;
                window.location = base + '/' + tr.dataset.rowId;
            });
        }
    });
</script>
@endsection
