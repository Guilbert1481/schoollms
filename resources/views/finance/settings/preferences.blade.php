@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Finance Preferences</h1>
        <p class="text-sm text-slate-500">General finance preferences and current configuration.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @php
        $freqLabel = $frequencies[$setting->soa_frequency] ?? ucwords(str_replace('_', ' ', (string) $setting->soa_frequency));
        $yesNo = fn ($v) => $v ? 'Yes' : 'No';
    @endphp

    <div x-data="{ tab: 'preferences' }">
        {{-- Tabs --}}
        <nav class="flex gap-6 border-b border-slate-200 text-sm">
            @foreach(['preferences' => 'Preferences', 'info' => 'Info'] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="-mb-px border-b-2 px-1 py-3 font-semibold">{{ $label }}</button>
            @endforeach
        </nav>

        {{-- ===== Preferences tab (editable) ===== --}}
        <div x-show="tab === 'preferences'" class="pt-5">
            <form method="POST" action="{{ route('finance.settings.preferences.update') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
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
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save Preferences</button>
                </div>
            </form>
        </div>

        {{-- ===== Info tab (read-only summary) ===== --}}
        <div x-show="tab === 'info'" x-cloak class="pt-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-bold text-slate-800">Current Finance Configuration</h2>
                <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
                    @php
                        $rows = [
                            ['School', $schoolName],
                            ['Currency', $setting->currency ?: '—'],
                            ['SOA Frequency', $freqLabel],
                            ['Generation Day', $setting->soa_generation_day],
                            ['Invoice / SOA Due (days)', $setting->invoice_due_days],
                            ['Auto-generate SOA', $yesNo($setting->auto_generate_soa)],
                            ['Auto-invoice on billing', $yesNo($setting->auto_invoice_on_billing)],
                            ['Last SOA Run', $setting->last_soa_run_at ? \Illuminate\Support\Carbon::parse($setting->last_soa_run_at)->format('M d, Y g:i A') : 'Never'],
                        ];
                    @endphp
                    @foreach($rows as [$label, $value])
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <dt class="text-sm text-slate-500">{{ $label }}</dt>
                            <dd class="text-sm font-semibold text-slate-800">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-4">
                    <dt class="text-sm text-slate-500">SOA Footer Note</dt>
                    <dd class="mt-1 whitespace-pre-line rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">{{ $setting->soa_footer_note ?: '—' }}</dd>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
