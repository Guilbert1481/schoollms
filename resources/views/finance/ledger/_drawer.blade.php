{{--
    Finance → Student ledger drawer (AJAX partial, rendered inside
    <x-drawer.right-drawer id="financeStudentDrawer" />).

    Layout copied from the design: header, account summary, quick actions,
    tabbed tables (Ledger populated), aging summary, and quick info. Colours
    use inline hex so they render regardless of the compiled Tailwind build.
--}}
@php
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $num   = fn ($v) => (float) $v > 0 ? number_format((float) $v, 2) : '-';
    $fmt   = fn ($d) => \Illuminate\Support\Carbon::parse((string) $d)->format('M d, Y');
@endphp

{{-- Responsive grids + tab spacing. Real @media queries so it adapts on mobile
     (where the drawer goes full-width); a <style> injected via innerHTML applies. --}}
<style>
    .fd-summary { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    .fd-actions { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.5rem; }
    .fd-aging   { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.5rem; }
    .fd-tabs    { display:flex; flex-wrap:wrap; gap:1.75rem; }
    @media (max-width: 560px) {
        .fd-summary { grid-template-columns:1fr; }
        .fd-aging   { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .fd-tabs    { gap:1.1rem; }
    }
    @media (max-width: 400px) {
        .fd-actions { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
</style>

<div class="space-y-4 p-5">

    {{-- ===== Header ===== --}}
    <div class="flex items-start gap-3 pr-8">
        @if($header['photo'])
            <img src="{{ asset('storage/'.$header['photo']) }}" alt=""
                 class="h-14 w-14 flex-none rounded-full object-cover ring-1 ring-slate-200">
        @else
            <div class="flex h-14 w-14 flex-none items-center justify-center rounded-full text-sm font-black ring-1 ring-slate-200"
                 style="background-color:#e0e7ff;color:#4f46e5;">{{ $header['initials'] }}</div>
        @endif
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-base font-extrabold text-slate-900">{{ $header['name'] }}</h2>
                <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-bold"
                      style="{{ $header['status_active'] ? 'background-color:#d1fae5;color:#047857;' : 'background-color:#f1f5f9;color:#475569;' }}">
                    {{ $header['status_label'] }}
                </span>
            </div>
            <p class="mt-0.5 text-xs text-slate-500">
                {{ $header['student_id'] }}@if(!empty($header['lrn'])) &middot; LRN: {{ $header['lrn'] }}@endif
            </p>
            <p class="text-xs text-slate-500">{{ $header['grade_section'] }}</p>
        </div>
    </div>

    {{-- ===== Account summary ===== --}}
    <div class="rounded-xl border border-slate-200 p-4">
        <div class="fd-summary">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Total Balance</div>
                <div class="mt-1 text-2xl font-black text-slate-800">{{ $money($summary['balance']) }}</div>
                <div class="mt-1 text-[11px] text-slate-400">As of {{ $summary['as_of'] }}</div>
            </div>
            <div class="space-y-1.5 border-l border-slate-100 pl-4 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Current Due</span>
                    <span class="font-semibold text-slate-800">{{ $money($summary['current_due']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Overdue</span>
                    <span class="font-semibold" style="color:#e11d48;">{{ $money($summary['overdue']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Future Charges</span>
                    <span class="font-semibold text-slate-800">{{ $money($summary['future']) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Quick actions ===== --}}
    @php
        $drawerActions = [
            ['label' => 'View Ledger',    'icon' => 'file-text',   'bg' => '#eff6ff', 'fg' => '#2563eb', 'fn' => 'drawerViewLedger'],
            ['label' => 'Generate SOA',   'icon' => 'file-plus',   'bg' => '#ecfdf5', 'fg' => '#059669', 'fn' => 'drawerGenerateSoa'],
            ['label' => 'Record Payment', 'icon' => 'credit-card', 'bg' => '#f5f3ff', 'fg' => '#7c3aed', 'fn' => 'drawerRecordPayment'],
            ['label' => 'Send Reminder',  'icon' => 'bell',        'bg' => '#fff7ed', 'fg' => '#ea580c', 'fn' => 'drawerSendReminder'],
        ];
    @endphp
    <div class="fd-actions">
        @foreach($drawerActions as $a)
            <button type="button" onclick="{{ $a['fn'] }}({{ $studentId }})"
                    class="flex flex-col items-center gap-1.5 rounded-xl px-2 py-3 text-center text-[11px] font-semibold leading-tight"
                    style="background-color:{{ $a['bg'] }};color:{{ $a['fg'] }};">
                <i data-lucide="{{ $a['icon'] }}" class="h-5 w-5"></i>
                <span>{{ $a['label'] }}</span>
            </button>
        @endforeach
    </div>

    {{-- ===== Tabs ===== --}}
    <div x-data="{ tab: 'ledger' }">
        <nav class="fd-tabs border-b border-slate-200 text-sm">
            @foreach(['ledger'=>'Ledger','billings'=>'Billings','payments'=>'Payments','invoices'=>'Invoices','documents'=>'Documents','notes'=>'Notes'] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="-mb-px border-b-2 py-2 font-semibold">{{ $label }}</button>
            @endforeach
        </nav>

        <div class="pt-3">
            {{-- Ledger --}}
            <div x-show="tab === 'ledger'">
                <div class="mb-2 flex items-center justify-end gap-2">
                    <button type="button" onclick="drawerExportLedger({{ $studentId }})"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        <i data-lucide="download" class="h-3.5 w-3.5 text-indigo-600"></i> Export
                    </button>
                    <button type="button" onclick="drawerImportLedger({{ $studentId }})"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        <i data-lucide="upload" class="h-3.5 w-3.5 text-emerald-600"></i> Import
                    </button>
                </div>

                @include('finance.ledger._ledger_table', ['entries' => $entries, 'currency' => $currency])

                @if($entries->isNotEmpty())
                    <div class="pt-3 text-center">
                        <button type="button" onclick="drawerViewLedger({{ $studentId }})"
                                class="text-xs font-semibold text-indigo-600 hover:underline">View Full Ledger</button>
                    </div>
                @endif
            </div>

            {{-- Billings (Statements of Account) --}}
            <div x-show="tab === 'billings'" x-cloak>
                @forelse($statements as $s)
                    <div class="flex items-center justify-between border-t border-slate-100 py-2 text-sm">
                        <div>
                            <div class="font-semibold text-slate-800">{{ $s->soa_number ?? 'SOA' }}</div>
                            <div class="text-xs text-slate-500">{{ $s->period_label ?? '—' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-slate-800">{{ $money($s->closing_balance ?? 0) }}</div>
                            <div class="text-xs text-slate-400">{{ ucfirst((string) ($s->status ?? '')) }}</div>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-slate-400">No statements generated yet.</p>
                @endforelse
            </div>

            {{-- Payments --}}
            <div x-show="tab === 'payments'" x-cloak>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">
                            <th class="py-2 pr-2">Date</th>
                            <th class="py-2 pr-2">Method</th>
                            <th class="py-2 pr-2">Reference</th>
                            <th class="py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $p)
                            <tr class="border-t border-slate-100">
                                <td class="whitespace-nowrap py-2 pr-2 text-slate-600">{{ $p->paid_at ? $fmt($p->paid_at) : '—' }}</td>
                                <td class="py-2 pr-2 text-slate-700">{{ ucfirst(str_replace('_', ' ', (string) ($p->payment_method ?? '—'))) }}</td>
                                <td class="py-2 pr-2 text-slate-500">{{ $p->reference_number ?: '—' }}</td>
                                <td class="py-2 text-right font-semibold text-slate-800">{{ $money($p->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-xs text-slate-400">No payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Invoices --}}
            <div x-show="tab === 'invoices'" x-cloak>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">
                            <th class="py-2 pr-2">Invoice</th>
                            <th class="py-2 pr-2">Due</th>
                            <th class="py-2 pr-2 text-right">Total</th>
                            <th class="py-2 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $inv)
                            <tr class="border-t border-slate-100">
                                <td class="py-2 pr-2 text-slate-700">{{ $inv->invoice_number ?? ('#'.$inv->id) }}</td>
                                <td class="whitespace-nowrap py-2 pr-2 text-slate-600">{{ $inv->due_date ? $fmt($inv->due_date) : '—' }}</td>
                                <td class="py-2 pr-2 text-right text-slate-700">{{ $money($inv->total_amount) }}</td>
                                <td class="py-2 text-right font-semibold {{ (float) $inv->balance > 0.005 ? '' : 'text-slate-800' }}"
                                    @if((float) $inv->balance > 0.005) style="color:#e11d48;" @endif>{{ $money($inv->balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-xs text-slate-400">No invoices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Documents --}}
            <div x-show="tab === 'documents'" x-cloak>
                <p class="py-6 text-center text-xs text-slate-400">No documents attached.</p>
            </div>

            {{-- Notes --}}
            <div x-show="tab === 'notes'" x-cloak>
                <p class="py-6 text-center text-xs text-slate-400">No notes yet.</p>
            </div>
        </div>
    </div>

    {{-- ===== Aging summary ===== --}}
    <div class="rounded-xl border border-slate-200 p-4">
        <div class="mb-3 text-sm font-bold text-slate-800">Aging Summary</div>
        @php
            $agingMap = [
                '0 - 30 Days'  => $aging['0-30'],
                '31 - 60 Days' => $aging['31-60'],
                '61 - 90 Days' => $aging['61-90'],
                '90+ Days'     => $aging['90+'],
            ];
        @endphp
        <div class="fd-aging">
            @foreach($agingMap as $label => $amt)
                @php $isOldest = str_starts_with($label, '90'); @endphp
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ $label }}</div>
                    <div class="mt-1 text-sm font-bold {{ ($isOldest && $amt > 0) ? '' : 'text-slate-800' }}"
                         @if($isOldest && $amt > 0) style="color:#e11d48;" @endif>{{ $money($amt) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ===== Quick info ===== --}}
    <div class="rounded-xl border border-slate-200 p-4">
        <div class="mb-3 text-sm font-bold text-slate-800">Quick Info</div>
        <dl class="space-y-2 text-sm">
            <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500">Last Payment</dt>
                <dd class="text-right font-semibold text-slate-800">{{ $quick['last_payment'] }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500">Last SOA Generated</dt>
                <dd class="text-right font-semibold text-slate-800">{{ $quick['last_soa'] }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500">Account Status</dt>
                <dd class="text-right font-semibold {{ $quick['status_active'] ? '' : 'text-slate-800' }}"
                    @if($quick['status_active']) style="color:#059669;" @endif>{{ $quick['account_status'] }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500">Next Due Date</dt>
                <dd class="text-right font-semibold text-slate-800">{{ $quick['next_due'] }}</dd>
            </div>
        </dl>
    </div>
</div>
