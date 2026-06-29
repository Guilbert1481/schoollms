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
        :hideActions="true"
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

{{-- Resizable student drawer (reusable component); content loaded on row click. --}}
<x-drawer.right-drawer id="financeStudentDrawer" :width="480" :min="380" :max="760" />

{{-- ===== Drawer action modals (reusable draggable + resizable modal) ===== --}}
{{-- They live on the MAIN page so their drag/resize JS initialises on load;
     the drawer's buttons just call openModal() with the current student. --}}

<x-modal.form id="viewLedgerModal" title="Student Ledger" widthClass="w-full max-w-4xl" :hideFooter="true">
    <div id="viewLedgerBody">
        <div class="py-8 text-center text-sm text-slate-400">Loading…</div>
    </div>
</x-modal.form>

<x-modal.form id="generateSoaModal" title="Generate Statement of Account" widthClass="w-full max-w-lg">
    <form method="POST" action="{{ route('finance.statements.generate') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="student_id" id="soaStudentId" value="">
        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700">Frequency</label>
            <select name="frequency" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Use school default</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="semi_annual">Semi-Annual</option>
                <option value="annual">Annual</option>
                <option value="per_term">Per Term</option>
                <option value="on_demand">On Demand</option>
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Period Start</label>
                <input type="date" name="period_start" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Period End</label>
                <input type="date" name="period_end" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="force" value="1"> Force regeneration (even with no new activity)
        </label>
        <p class="text-xs text-slate-400">Leave the period blank to use the frequency's current window.</p>
    </form>
</x-modal.form>

<x-modal.form id="recordPaymentModal" title="Record Payment" widthClass="w-full max-w-lg">
    <form method="POST" action="{{ route('finance.ledger.record-payment') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="student_id" id="payStudentId" value="">
        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700">Amount</label>
            <input type="number" step="0.01" min="0.01" name="amount" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="0.00">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Payment Method</label>
                <select name="payment_method" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="gcash">GCash</option>
                    <option value="card">Card</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Payment Date</label>
                <input type="date" name="paid_at" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700">Reference Number</label>
            <input type="text" name="reference_number" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Optional">
        </div>
    </form>
</x-modal.form>

<x-modal.form id="sendReminderModal" title="Send Payment Reminder" widthClass="w-full max-w-lg">
    <form method="POST" action="{{ route('finance.ledger.send-reminder') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="student_id" id="reminderStudentId" value="">
        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700">Channel</label>
            <select name="channel" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="email">Email</option>
                <option value="sms">SMS</option>
                <option value="portal">Portal Notification</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700">Message</label>
            <textarea name="message" rows="4" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">This is a friendly reminder regarding your outstanding balance. Please settle it at your earliest convenience. Thank you.</textarea>
        </div>
    </form>
</x-modal.form>

<x-modal.form id="importLedgerModal" title="Import Ledger Transactions" widthClass="w-full max-w-lg">
    <form method="POST" action="{{ route('finance.ledger.import-entries') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="hidden" name="student_id" id="importStudentId" value="">
        <p class="text-xs text-slate-500">
            CSV columns: <code class="rounded bg-slate-100 px-1">date, type, description, reference, debit, credit</code>.
            Type is one of charge / payment / discount / adjustment / refund.
        </p>
        <input type="file" name="file" accept=".csv,text/csv,text/plain" required
               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700">
    </form>
</x-modal.form>

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

    // ===== Drawer action buttons → reusable modals =====
    const LEDGER_BASE = @json(url('/finance/ledger'));

    function drawerViewLedger(id) {
        const body = document.getElementById('viewLedgerBody');
        body.innerHTML = '<div class="py-8 text-center text-sm text-slate-400">Loading…</div>';
        openModal('viewLedgerModal');
        fetch(LEDGER_BASE + '/' + id + '/entries', { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then((r) => r.text())
            .then((html) => { body.innerHTML = html; })
            .catch(() => { body.innerHTML = '<div class="py-8 text-center text-sm text-slate-400">Couldn\'t load the ledger.</div>'; });
    }
    function drawerGenerateSoa(id)   { document.getElementById('soaStudentId').value = id;      openModal('generateSoaModal'); }
    function drawerRecordPayment(id) { document.getElementById('payStudentId').value = id;      openModal('recordPaymentModal'); }
    function drawerSendReminder(id)  { document.getElementById('reminderStudentId').value = id; openModal('sendReminderModal'); }
    function drawerImportLedger(id)  { document.getElementById('importStudentId').value = id;   openModal('importLedgerModal'); }
    function drawerExportLedger(id)  { window.location = LEDGER_BASE + '/' + id + '/export?format=csv'; }

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
                // Open the resizable drawer with this student's ledger (no page nav).
                window.RightDrawer.load('financeStudentDrawer', base + '/' + tr.dataset.rowId + '/drawer');
            });
        }
    });
</script>
@endsection
