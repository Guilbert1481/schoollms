@extends('layouts.app')

@php
    $cur = $setting->currency ?? 'PHP';
    $fullName = trim(($student->first_name ?? '').' '.($student->last_name ?? '')) ?: ($student->email ?? 'Student');
    $balanceColor = $balance > 0.005 ? 'text-rose-600' : ($balance < -0.005 ? 'text-sky-600' : 'text-emerald-600');
    $typeColors = [
        'charge'     => 'bg-rose-50 text-rose-700',
        'payment'    => 'bg-emerald-50 text-emerald-700',
        'discount'   => 'bg-amber-50 text-amber-700',
        'adjustment' => 'bg-slate-100 text-slate-600',
        'refund'     => 'bg-sky-50 text-sky-700',
    ];
@endphp

@section('content')
<div class="w-full space-y-6">

    <a href="{{ route('finance.ledger.index') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-indigo-600">&larr; Back to Student Ledgers</a>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Account summary --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">{{ $fullName }}</h1>
                <p class="text-sm text-slate-500">
                    Student No. {{ $profile->student_number ?? '—' }} &middot; {{ $student->email }}
                </p>
            </div>
            <div class="text-right">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Current Balance</div>
                <div class="text-3xl font-extrabold {{ $balanceColor }}">{{ $cur }} {{ number_format($balance, 2) }}</div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <form method="POST" action="{{ route('finance.statements.generate') }}">
                @csrf
                <input type="hidden" name="student_id" value="{{ $student->id }}">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Generate SOA ({{ $setting->frequencyLabel() }})
                </button>
            </form>
            <button type="button" onclick="openModal('ledgerAdjustModal')" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Add Adjustment
            </button>
        </div>
    </div>

    {{-- Enrollments awaiting an invoice --}}
    @php $uninvoiced = $enrollments->where('has_invoice', false); @endphp
    @if($uninvoiced->isNotEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <h2 class="mb-3 text-sm font-bold text-amber-800">Enrollments without an invoice</h2>
            <div class="space-y-2">
                @foreach($uninvoiced as $e)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white px-3 py-2">
                        <div class="text-sm text-slate-700">
                            {{ $e->program?->code ?? $e->program?->name ?? 'Program' }}
                            &middot; {{ $e->term?->name ?? '—' }} {{ $e->academicYear?->name ?? '' }}
                            <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">{{ $e->status }}</span>
                        </div>
                        <form method="POST" action="{{ route('finance.invoices.generate') }}">
                            @csrf
                            <input type="hidden" name="enrollment_id" value="{{ $e->id }}">
                            <button type="submit" class="rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Generate Invoice</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Invoices --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-lg font-semibold text-slate-800">Invoices</h2>
        @if($invoices->isEmpty())
            <p class="text-sm text-slate-500">No invoices yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-xs uppercase text-slate-400">
                            <th class="px-3 py-2">Invoice #</th>
                            <th class="px-3 py-2">Issued</th>
                            <th class="px-3 py-2">Due</th>
                            <th class="px-3 py-2 text-right">Total</th>
                            <th class="px-3 py-2 text-right">Paid</th>
                            <th class="px-3 py-2 text-right">Balance</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $inv)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2 font-medium text-slate-700">{{ $inv->invoice_number }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ optional($inv->issue_date)->format('M d, Y') ?? '—' }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ optional($inv->due_date)->format('M d, Y') ?? '—' }}</td>
                                <td class="px-3 py-2 text-right">{{ $cur }} {{ number_format((float) $inv->total_amount, 2) }}</td>
                                <td class="px-3 py-2 text-right">{{ $cur }} {{ number_format((float) $inv->paid_amount, 2) }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ $cur }} {{ number_format((float) $inv->balance, 2) }}</td>
                                <td class="px-3 py-2">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold
                                        {{ $inv->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($inv->status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                                        {{ ucfirst($inv->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('finance.invoices.show', $inv->id) }}" class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">View</a>
                                        <a href="{{ route('finance.invoices.pdf', $inv->id) }}" class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">PDF</a>
                                        @if((float) $inv->balance > 0.005)
                                            <button type="button"
                                                onclick="openInvoicePay({{ $inv->id }}, '{{ $inv->invoice_number }}', '{{ number_format((float) $inv->balance, 2, '.', '') }}')"
                                                class="rounded bg-indigo-600 px-2 py-1 text-xs font-semibold text-white hover:bg-indigo-700">Record Payment</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Ledger entries --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-lg font-semibold text-slate-800">Ledger</h2>
        @if($entries->isEmpty())
            <p class="text-sm text-slate-500">No ledger activity yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-xs uppercase text-slate-400">
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Description</th>
                            <th class="px-3 py-2">Reference</th>
                            <th class="px-3 py-2 text-right">Charge</th>
                            <th class="px-3 py-2 text-right">Payment</th>
                            <th class="px-3 py-2 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $e)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2 text-slate-500">{{ optional($e->entry_date)->format('M d, Y') }}</td>
                                <td class="px-3 py-2">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $typeColors[$e->type] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($e->type) }}</span>
                                </td>
                                <td class="px-3 py-2 text-slate-700">{{ $e->description }}</td>
                                <td class="px-3 py-2 text-slate-400">{{ $e->reference ?? '—' }}</td>
                                <td class="px-3 py-2 text-right text-rose-600">{{ (float) $e->debit > 0 ? $cur.' '.number_format((float) $e->debit, 2) : '' }}</td>
                                <td class="px-3 py-2 text-right text-emerald-600">{{ (float) $e->credit > 0 ? $cur.' '.number_format((float) $e->credit, 2) : '' }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-slate-800">{{ $cur }} {{ number_format((float) $e->balance_after, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Statements of Account --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-lg font-semibold text-slate-800">Statements of Account</h2>
        @if($statements->isEmpty())
            <p class="text-sm text-slate-500">No statements generated yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-xs uppercase text-slate-400">
                            <th class="px-3 py-2">SOA #</th>
                            <th class="px-3 py-2">Period</th>
                            <th class="px-3 py-2 text-right">Opening</th>
                            <th class="px-3 py-2 text-right">Charges</th>
                            <th class="px-3 py-2 text-right">Payments</th>
                            <th class="px-3 py-2 text-right">Closing</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($statements as $s)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2 font-medium text-slate-700">{{ $s->soa_number }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ $s->period_label }}</td>
                                <td class="px-3 py-2 text-right">{{ $cur }} {{ number_format((float) $s->opening_balance, 2) }}</td>
                                <td class="px-3 py-2 text-right">{{ $cur }} {{ number_format((float) $s->total_charges, 2) }}</td>
                                <td class="px-3 py-2 text-right">{{ $cur }} {{ number_format((float) $s->total_credits, 2) }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ $cur }} {{ number_format((float) $s->closing_balance, 2) }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('finance.statements.show', $s->id) }}" class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">View</a>
                                        <a href="{{ route('finance.statements.pdf', $s->id) }}" class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">PDF</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Adjustment modal --}}
<x-modal.form id="ledgerAdjustModal" title="Post Ledger Adjustment" widthClass="w-full max-w-md">
    <form method="POST" action="{{ route('finance.ledger.adjust', $student->id) }}" class="space-y-4">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Direction</label>
            <select name="direction" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <option value="debit">Charge (increases balance)</option>
                <option value="credit">Credit (decreases balance)</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Amount ({{ $cur }})</label>
            <input type="number" name="amount" step="0.01" min="0.01" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
            <input type="text" name="description" maxlength="191" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" placeholder="e.g. Scholarship credit, late fee, correction">
        </div>
    </form>
</x-modal.form>

{{-- Invoice payment modal --}}
<x-modal.form id="invoicePayModal" title="Record Payment" widthClass="w-full max-w-md">
    <form id="invoicePayForm" method="POST" action="#" class="space-y-4">
        @csrf
        <p class="text-sm text-slate-500">Invoice <span id="invoicePayNumber" class="font-semibold text-slate-700"></span></p>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Amount ({{ $cur }})</label>
            <input id="pay_amount" type="number" name="amount" step="0.01" min="0.01" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Payment Method</label>
            <select name="payment_method" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="gcash">GCash</option>
                <option value="card">Card</option>
                <option value="cheque">Cheque</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Reference No. (optional)</label>
            <input type="text" name="reference_number" maxlength="120" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
        </div>
    </form>
</x-modal.form>

<script>
    const payUrlTemplate = @json(route('finance.invoices.pay', ['invoice' => '__ID__']));
    function openInvoicePay(id, number, balance) {
        document.getElementById('invoicePayForm').action = payUrlTemplate.replace('__ID__', id);
        document.getElementById('invoicePayNumber').textContent = number;
        document.getElementById('pay_amount').value = balance;
        openModal('invoicePayModal');
    }
</script>
@endsection
