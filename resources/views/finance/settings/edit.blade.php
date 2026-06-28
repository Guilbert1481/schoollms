@extends('layouts.app')

@section('content')
<div class="w-full max-w-2xl space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Finance Settings</h1>
        <p class="text-sm text-slate-500">Control how and when Statements of Account are generated.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('finance.settings.update') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">SOA Generation Frequency</label>
            <select name="soa_frequency" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                @foreach($frequencies as $value => $label)
                    <option value="{{ $value }}" @selected($setting->soa_frequency === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-400">How often statements are auto-generated. "Per Term" generates when a term ends; "On Demand" disables automatic generation.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Generation Day (of month)</label>
                <input type="number" name="soa_generation_day" min="1" max="28" value="{{ old('soa_generation_day', $setting->soa_generation_day) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <p class="mt-1 text-xs text-slate-400">For monthly / quarterly / annual cadences.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Invoice / SOA Due (days)</label>
                <input type="number" name="invoice_due_days" min="0" max="365" value="{{ old('invoice_due_days', $setting->invoice_due_days) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Currency</label>
            <input type="text" name="currency" maxlength="10" value="{{ old('currency', $setting->currency) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
        </div>

        <div class="space-y-3">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="auto_generate_soa" value="0">
                <input type="checkbox" name="auto_generate_soa" value="1" @checked($setting->auto_generate_soa) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-200">
                Automatically generate Statements of Account on the configured cadence
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="auto_invoice_on_billing" value="0">
                <input type="checkbox" name="auto_invoice_on_billing" value="1" @checked($setting->auto_invoice_on_billing) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-200">
                Automatically create an invoice when the registrar sends an enrollment to billing
            </label>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">SOA Footer Note (optional)</label>
            <textarea name="soa_footer_note" rows="3" maxlength="2000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" placeholder="Payment instructions, bank details, reminders…">{{ old('soa_footer_note', $setting->soa_footer_note) }}</textarea>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save Settings</button>
        </div>
    </form>
</div>
@endsection
