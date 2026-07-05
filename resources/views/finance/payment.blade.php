@extends('layouts.app')

@php $cur = 'PHP'; @endphp

@section('content')
<div class="w-full space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Finance Payments</h1>
        <p class="text-sm text-slate-500">Record payments and verify online proof-of-payment submissions.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sky-700">{{ session('info') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Shared dropdown filters (Student Ledgers pattern) — used by both panes. --}}
    @php
        $sharedFilters = [
            ['key' => 'academic_year_id', 'options' => $academicYears,    'selected' => $academicYearId, 'all' => 'All Academic Years'],
            ['key' => 'year_level',       'options' => $yearLevelOptions, 'selected' => $yearLevel,      'all' => $activeLevelIsBasic ? 'All Grade Levels' : 'All Year Levels'],
        ];
        if ($activeLevelIsBasic) {
            $sharedFilters[] = ['key' => 'section_id', 'options' => $sectionOptions, 'selected' => $sectionId, 'all' => 'All Sections'];
        }
        if ($showProgramFilter) {
            $sharedFilters[] = ['key' => 'program_id', 'options' => $programOptions, 'selected' => $programId, 'all' => 'All Programs'];
        }
    @endphp

    {{-- ===== Tabs: Payments (recent + record) | Verification (proof queue) ===== --}}
    <div x-data="{ tab: @js($activeTab) }">
        <nav class="flex gap-6 border-b border-slate-200 text-sm">
            @foreach(['payments' => 'Payments', 'verification' => 'Verification'] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'; syncTabParam('{{ $key }}')"
                        :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="-mb-px border-b-2 py-2 font-semibold">
                    {{ $label }}
                    @if($key === 'verification' && $pendingRows->isNotEmpty())
                        <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">{{ $pendingRows->count() }}</span>
                    @endif
                </button>
            @endforeach
        </nav>

        {{-- ===== Payments tab: level tabs + filters + recent payments ===== --}}
        <div x-show="tab === 'payments'" class="space-y-4 pt-5">
            <x-table.level-tabs route="finance.payments.index"
                                :levels="$levels"
                                :activeLevelId="$activeLevelId"
                                :showAll="$showAll"
                                :params="['tab' => 'payments']" />

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <x-table.filter-toolbar
                    searchId="paymentsFilter" searchPlaceholder="Filter..."
                    :filters="array_merge([['key' => 'status', 'options' => $statusOptions, 'selected' => $statusFilter, 'all' => 'All Statuses']], $sharedFilters)">
                    <button type="button" onclick="openModal('recordPaymentModal')"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        + Record Payment
                    </button>
                </x-table.filter-toolbar>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm" id="recentPaymentsTable">
                        <thead class="border-b border-slate-200 text-slate-500">
                            <tr>
                                <th class="px-3 py-2 font-medium">Date</th>
                                <th class="px-3 py-2 font-medium">Student</th>
                                <th class="px-3 py-2 font-medium">Invoice #</th>
                                <th class="px-3 py-2 font-medium">Type</th>
                                <th class="px-3 py-2 font-medium">Method</th>
                                <th class="px-3 py-2 font-medium">Reference</th>
                                <th class="px-3 py-2 font-medium text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr class="border-b border-slate-100">
                                    <td class="px-3 py-2 text-slate-600">{{ optional($payment->date)->format('M d, Y h:i A') ?? '—' }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $payment->student_name }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $payment->invoice_number }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ strtoupper($payment->payment_method) }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $payment->reference_number ?: '-' }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-slate-800">{{ number_format($payment->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr data-empty-row>
                                    <td colspan="7" class="px-3 py-6 text-center text-slate-500">No payments recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div id="recentPaymentsTableNoMatch" class="hidden px-3 py-6 text-center text-sm text-slate-400">No rows match your search.</div>
                </div>
            </div>
        </div>

        {{-- ===== Verification tab: level tabs + filters + pending proof queue ===== --}}
        <div x-show="tab === 'verification'" x-cloak class="space-y-4 pt-5">
            <x-table.level-tabs route="finance.payments.index"
                                :levels="$levels"
                                :activeLevelId="$activeLevelId"
                                :showAll="$showAll"
                                :params="['tab' => 'verification']" />

            @php
                // Base columns: ID, Name, Grade/Year, Invoice#, Due, Proof, Payment date, Action.
                $colspan = 8 + ($showLevel ? 1 : 0) + ($showProgram ? 1 : 0);
            @endphp
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <x-table.filter-toolbar
                    searchId="verificationFilter" searchPlaceholder="Filter..."
                    :filters="$sharedFilters">
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-700">{{ $pendingRows->count() }} awaiting</span>
                </x-table.filter-toolbar>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm" id="verificationTable">
                        <thead class="border-b border-slate-200 text-[11px] uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-3 py-2 font-semibold">Student ID</th>
                                <th class="px-3 py-2 font-semibold">Name</th>
                                @if($showLevel)<th class="px-3 py-2 font-semibold">Level</th>@endif
                                @if($showProgram)<th class="px-3 py-2 font-semibold">Program</th>@endif
                                <th class="px-3 py-2 font-semibold">Grade / Year</th>
                                <th class="px-3 py-2 font-semibold">Invoice #</th>
                                <th class="px-3 py-2 font-semibold">Due Date</th>
                                <th class="px-3 py-2 font-semibold">Proof</th>
                                <th class="px-3 py-2 font-semibold">Payment Date</th>
                                <th class="px-3 py-2 font-semibold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingRows as $row)
                                <tr class="border-b border-slate-100 align-middle">
                                    <td class="px-3 py-2 text-slate-500">{{ $row->student_id }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $row->name }}</td>
                                    @if($showLevel)<td class="px-3 py-2 text-slate-600">{{ $row->level }}</td>@endif
                                    @if($showProgram)<td class="px-3 py-2 text-slate-600">{{ $row->program }}</td>@endif
                                    <td class="px-3 py-2 text-slate-600">{{ $row->grade_year }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $row->invoice_number }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ optional($row->due_date)->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-3 py-2">
                                        @if($row->proof_url)
                                            <a href="{{ $row->proof_url }}" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-indigo-600 hover:bg-slate-200">View proof</a>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">{{ optional($row->payment_date)->format('M d, Y g:i A') ?? '—' }}</td>
                                    <td class="px-3 py-2">
                                        <div style="display:inline-flex; gap:0.375rem; align-items:center; justify-content:flex-end; width:100%;">
                                            <button type="button" onclick="openStudentLedger({{ $row->student_id }})"
                                                    class="rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">View</button>
                                            <form method="POST" action="{{ route('finance.payments.submissions.verify', $row->submission_id) }}"
                                                  onsubmit="return confirm('Verify {{ $cur }} {{ number_format($row->amount, 2) }} for {{ $row->invoice_number }} and post it to the ledger?');"
                                                  style="display:inline;">
                                                @csrf
                                                <button type="submit" class="rounded bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700">Verify</button>
                                            </form>
                                            <button type="button" onclick="rejectSubmission({{ $row->submission_id }})"
                                                    class="rounded bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-200">Reject</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $colspan }}" class="px-3 py-8 text-center text-slate-400">No payments are awaiting verification.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div id="verificationTableNoMatch" class="hidden px-3 py-6 text-center text-sm text-slate-400">No rows match your search.</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Record Payment modal (opened from the Payments tab toolbar). --}}
<x-modal.base id="recordPaymentModal" title="Record Payment" width="lg">
    <form method="POST" action="{{ route('finance.payments.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="student_id" class="mb-1 block text-sm font-medium text-slate-700">Student</label>
            <select id="student_id" name="student_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <option value="">Select student</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                        {{ $student->full_name }} ({{ $student->email }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="amount" class="mb-1 block text-sm font-medium text-slate-700">Amount</label>
            <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
        </div>

        <div>
            <label for="payment_method" class="mb-1 block text-sm font-medium text-slate-700">Payment Method</label>
            <select id="payment_method" name="payment_method" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <option value="">Select method</option>
                <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                <option value="gcash" {{ old('payment_method') === 'gcash' ? 'selected' : '' }}>GCash</option>
                <option value="card" {{ old('payment_method') === 'card' ? 'selected' : '' }}>Card</option>
            </select>
        </div>

        <div>
            <label for="payment_type" class="mb-1 block text-sm font-medium text-slate-700">Payment Type</label>
            <select id="payment_type" name="payment_type" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <option value="">Select type</option>
                @foreach($paymentTypes as $type)
                    <option value="{{ $type }}" {{ old('payment_type') === $type ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', $type)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="reference_number" class="mb-1 block text-sm font-medium text-slate-700">Reference Number (optional)</label>
            <input id="reference_number" name="reference_number" type="text" value="{{ old('reference_number') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
            <button type="button" onclick="closeModal('recordPaymentModal')" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Cancel
            </button>
            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                Save Payment
            </button>
        </div>
    </form>
</x-modal.base>

{{-- Student ledger drawer (reused from the Ledger page) — opened by the row "View" action. --}}
<x-drawer.right-drawer id="financeStudentDrawer" :width="480" :min="380" :max="760" />

{{-- Hidden reject form — submitted (with an optional reason) by rejectSubmission(). --}}
<form id="rejectSubmissionForm" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="review_note" id="rejectSubmissionNote">
</form>

<script>
    // Keep the tab query param in sync so level tabs / filters reload into the
    // pane the user is looking at.
    function syncTabParam(tab) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url.toString());
    }

    // Client-side search for both tables (filters the currently loaded rows).
    ['recentPaymentsTable:paymentsFilter', 'verificationTable:verificationFilter'].forEach(function (pair) {
        var tableId = pair.split(':')[0], inputId = pair.split(':')[1];
        var input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            var rows = document.querySelectorAll('#' + tableId + ' tbody tr:not([data-empty-row])');
            var visible = 0;
            rows.forEach(function (tr) {
                var hit = q === '' || tr.textContent.toLowerCase().indexOf(q) !== -1;
                tr.style.display = hit ? '' : 'none';
                if (hit) visible++;
            });
            var none = document.getElementById(tableId + 'NoMatch');
            if (none) none.classList.toggle('hidden', visible > 0 || rows.length === 0);
        });
    });

    // Re-open the Record Payment modal when its submit failed validation.
    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->any())
            if (typeof openModal === 'function') openModal('recordPaymentModal');
        @endif
    });

    // Row "View" → open the same student-ledger drawer used on the Ledger page.
    function openStudentLedger(studentId) {
        if (window.RightDrawer && window.RightDrawer.load) {
            window.RightDrawer.load('financeStudentDrawer', @json(url('/finance/ledger')) + '/' + studentId + '/drawer');
        }
    }

    // Reject → capture an optional reason, then POST to the reject route.
    function rejectSubmission(id) {
        var note = prompt('Reason for rejecting this payment (optional):', '');
        if (note === null) return; // cancelled
        var form = document.getElementById('rejectSubmissionForm');
        form.action = @json(route('finance.payments.submissions.reject', ['submission' => '__ID__'])).replace('__ID__', id);
        document.getElementById('rejectSubmissionNote').value = note;
        form.submit();
    }
</script>
@endsection
