@extends('layouts.app')

@section('content')
<div class="w-full max-w-4xl space-y-6">

    <a href="{{ route('finance.ledger.index') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-indigo-600">&larr; Back to ledgers</a>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @include('finance.invoices._detail', ['invoice' => $invoice])
</div>
@endsection
