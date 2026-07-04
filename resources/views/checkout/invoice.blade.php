@extends('layouts.app')

@php
    $cur = 'PHP';
    $student = $invoice->student;
    $studentName = $student ? trim($student->first_name.' '.$student->last_name) : '—';
    $backUrl = auth()->user()->isStudent()
        ? route('student.finance.invoices')
        : route('finance.invoices.index');
    // e-wallet / bank methods show a QR + reference; cash is over-the-counter.
    $qrMethods = ['gcash', 'maya', 'bank_transfer'];
@endphp

@section('content')
<div class="w-full max-w-3xl space-y-6">

    <a href="{{ $backUrl }}" class="inline-flex items-center text-sm text-slate-500 hover:text-indigo-600">&larr; Back to invoices</a>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700">{{ session('info') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Amount summary --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Pay Invoice {{ $invoice->invoice_number }}</h1>
                <p class="text-sm text-slate-500">{{ $studentName }}</p>
                <p class="text-xs text-slate-400">
                    {{ $invoice->enrollment?->program?->code ?? '' }}
                    {{ $invoice->enrollment?->term?->name ?? '' }}
                </p>
            </div>
            <div class="text-right text-sm text-slate-500">
                <div>Due: {{ optional($invoice->due_date)->format('M d, Y') ?? '—' }}</div>
            </div>
        </div>

        <div class="mt-4 space-y-2 text-sm">
            <div style="display:flex; justify-content:space-between;">
                <span class="text-slate-500">Invoice balance</span>
                <span class="font-medium text-slate-800">{{ $cur }} {{ number_format($balance, 2) }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span class="text-slate-500">System fee</span>
                <span class="font-medium text-slate-800">{{ $cur }} {{ number_format($systemFee, 2) }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;" class="border-t border-slate-100 pt-2 text-base font-bold text-slate-900">
                <span>Total to pay</span>
                <span>{{ $cur }} {{ number_format($total, 2) }}</span>
            </div>
        </div>
    </div>

    @if($pending)
        {{-- A proof is already in the queue — don't let them submit another. --}}
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-amber-800">Payment awaiting verification</h2>
            <p class="mt-1 text-sm text-amber-700">
                You submitted a payment of {{ $cur }} {{ number_format((float) $pending->amount + (float) $pending->system_fee, 2) }}
                via {{ $methods[$pending->payment_method] ?? ucfirst($pending->payment_method) }}
                on {{ optional($pending->submitted_at)->format('M d, Y g:i A') ?? '—' }}.
                The finance office will confirm it shortly.
            </p>
            @if($pending->proofUrl())
                <a href="{{ $pending->proofUrl() }}" target="_blank" rel="noopener"
                   class="mt-3 inline-flex items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100">
                    View submitted proof
                </a>
            @endif
        </div>
    @else
        {{-- Checkout form --}}
        <form method="POST" action="{{ route('checkout.invoice.submit', $invoice) }}" enctype="multipart/form-data"
              class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
            @csrf

            {{-- Payment method --}}
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Payment method</label>
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                    @foreach($methods as $key => $label)
                        <label class="cursor-pointer rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                            <input type="radio" name="payment_method" value="{{ $key }}" class="sr-only"
                                   data-qr="{{ in_array($key, $qrMethods, true) ? '1' : '0' }}"
                                   @checked(old('payment_method') === $key || (! old('payment_method') && $loop->first))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- QR panel (e-wallet / bank) --}}
            <div id="qrPanel" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-center">
                @if($qr)
                    <img src="{{ $qr }}" alt="Payment QR" class="mx-auto h-48 w-48 rounded-lg bg-white object-contain p-2 shadow-sm">
                @else
                    <div class="mx-auto flex h-48 w-48 items-center justify-center rounded-lg border-2 border-dashed border-slate-300 bg-white text-xs text-slate-400">
                        QR code will appear here
                    </div>
                @endif
                <p class="mt-2 text-xs text-slate-500">Scan the QR to pay, then upload your proof of payment below.</p>
            </div>

            {{-- Over-the-counter note (cash) --}}
            <div id="cashPanel" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                Pay in cash at the school finance office, then upload the official receipt below.
            </div>

            {{-- Reference number --}}
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Reference number <span class="font-normal text-slate-400">(optional)</span></label>
                <input type="text" name="reference_number" value="{{ old('reference_number') }}" maxlength="120"
                       placeholder="e.g. GCash reference / OR number"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </div>

            {{-- Proof of payment (mandatory) --}}
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Proof of payment <span class="text-rose-500">*</span></label>
                <input type="file" name="proof" accept="image/*,application/pdf" required
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-indigo-700">
                <p class="mt-1 text-xs text-slate-400">JPG, PNG, WebP, or PDF · up to 5 MB.</p>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Submit payment
                </button>
            </div>
        </form>
    @endif
</div>

@if(! $pending)
<script>
    // Show the QR panel for e-wallet / bank methods; show the cash note for cash.
    (function () {
        var radios = document.querySelectorAll('input[name="payment_method"]');
        var qrPanel = document.getElementById('qrPanel');
        var cashPanel = document.getElementById('cashPanel');
        function sync() {
            var checked = document.querySelector('input[name="payment_method"]:checked');
            var isQr = checked && checked.getAttribute('data-qr') === '1';
            if (qrPanel) qrPanel.classList.toggle('hidden', !isQr);
            if (cashPanel) cashPanel.classList.toggle('hidden', !!isQr);
        }
        radios.forEach(function (r) { r.addEventListener('change', sync); });
        sync();
    })();
</script>
@endif
@endsection
