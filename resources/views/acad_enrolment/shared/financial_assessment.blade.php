@extends('layouts.enrollment')

@section('content')
{{-- Alpine.js — selecting a plan swaps the live computation + schedule. --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
    [x-cloak] { display: none !important; }

    /* Build-independent layout (compiled Tailwind build is stale). */
    .fa-grid { display: grid; grid-template-columns: 1fr; gap: 1.25rem; }
    @media (min-width: 1024px) { .fa-grid { grid-template-columns: 1fr 1fr; } }

    .fa-card { border: 1px solid #e2e8f0; border-radius: 0.75rem; background: #fff; }
    .fa-plan { border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.85rem 1rem; cursor: pointer; transition: all .15s; display: flex; gap: 0.75rem; align-items: flex-start; }
    .fa-plan:hover { border-color: #c7d2fe; }
    .fa-plan.is-selected { border-color: #4f46e5; background: #eef2ff; box-shadow: 0 0 0 1px #4f46e5 inset; }
    .fa-srow { display: flex; align-items: center; justify-content: space-between; padding: 0.4rem 0; font-size: 0.86rem; }
    .fa-srow + .fa-srow { border-top: 1px dashed #eef2f7; }
</style>

@php
    use Illuminate\Support\Carbon;

    $studentName = trim(($student->first_name ?? '').' '.($student->last_name ?? ''))
        ?: (auth()->user()->name ?? auth()->user()->email);
    $studentNo   = $student->student_number ?? $student->student_no ?? $student->id;
    $initials    = collect(explode(' ', $studentName))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
    $gradeLabel  = $program?->name ?: ($node?->name ?: '—');
    if (! empty($pathway['year_level'])) {
        $gradeLabel .= ' · '.($node && \Illuminate\Support\Str::contains(strtolower($node->name ?? ''), 'basic') ? 'Grade ' : 'Year ').$pathway['year_level'];
    }
    $ayLabel = optional($term->academicYear)->name ?? $term->name;
@endphp

<div class="px-4 sm:px-8 py-6 max-w-5xl mx-auto"
     x-data="financialAssessment({
        comps: @js($computations),
        initial: @js($savedKey ?? ($choices[0]['key'] ?? '')),
     })">

    <div class="text-xs font-extrabold text-slate-500 tracking-widest mb-1">
        STEP 8 OF 10 — FINANCIAL ASSESSMENT
    </div>
    <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden mb-6">
        <div class="h-full bg-indigo-600" style="width:78%"></div>
    </div>

    <h1 class="text-2xl font-extrabold text-slate-800 mb-1">Financial Assessment</h1>
    <p class="text-sm text-slate-500 mb-5">Review the total school fees and select your preferred payment plan.</p>

    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-lg p-3 mb-4 text-sm">
            <ul class="list-disc pl-5 space-y-0.5">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Student context card --}}
    <div class="fa-card p-4 mb-5" style="display:flex; align-items:center; gap:1rem;">
        <div style="width:48px; height:48px; border-radius:9999px; background:#4f46e5; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1rem; flex:0 0 auto;">{{ strtoupper($initials) ?: 'S' }}</div>
        <div style="min-width:0;">
            <div class="font-extrabold text-slate-800">{{ $studentName }}</div>
            <div class="text-xs text-slate-500 mt-0.5">
                Student ID: <span class="font-semibold text-slate-600">{{ $studentNo }}</span>
                &nbsp;·&nbsp; {{ $gradeLabel }}
                &nbsp;·&nbsp; Academic Year: <span class="font-semibold text-slate-600">{{ $ayLabel }}</span>
            </div>
        </div>
    </div>

    @if (empty($choices))
        <div class="fa-card p-6 text-center text-sm text-slate-500">
            @if ($plans->isEmpty())
                No payment plans have been configured by the school yet. Please contact the finance office.
            @else
                The available payment plans have no payment options enabled yet. Please contact the finance office.
            @endif
        </div>
        <div class="flex items-center justify-start pt-5">
            <a href="{{ route('public.apply.health', $term->id) }}"
               class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-bold">← Back</a>
        </div>
    @else
    <form method="POST" action="{{ route('public.apply.financial.store', $term->id) }}">
        @csrf

        <div class="fa-grid">

            {{-- LEFT: fees + plan selection --}}
            <div class="space-y-5">

                {{-- Total School Fees --}}
                <div class="fa-card p-4">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 mb-2">Total School Fees</div>
                    @forelse ($fees['items'] as $item)
                        <div class="fa-srow">
                            <span class="text-slate-600">{{ $item->label }}</span>
                            <span class="font-semibold text-slate-800">₱{{ number_format($item->amount, 2) }}</span>
                        </div>
                    @empty
                        <div class="text-xs text-slate-400 py-2">No fees are configured for this program/grade yet.</div>
                    @endforelse
                    <div class="fa-srow" style="border-top:2px solid #e2e8f0; margin-top:0.25rem;">
                        <span class="font-extrabold text-slate-800">TOTAL</span>
                        <span class="font-extrabold text-indigo-700 text-lg">₱{{ number_format($fees['total'], 2) }}</span>
                    </div>
                    @if ($scholarship ?? null)
                        <div class="fa-srow">
                            <span class="text-emerald-700">{{ $scholarship['label'] }}
                                <span class="text-[11px] text-emerald-500">({{ rtrim(rtrim(number_format($scholarship['percent'], 2), '0'), '.') }}% {{ $scholarship['apply_to'] === 'tuition' ? 'of tuition' : 'of total' }})</span>
                            </span>
                            <span class="font-semibold text-emerald-700">− ₱{{ number_format($scholarship['amount'], 2) }}</span>
                        </div>
                        <div class="fa-srow" style="border-top:2px solid #e2e8f0; margin-top:0.25rem;">
                            <span class="font-extrabold text-slate-800">NET PAYABLE</span>
                            <span class="font-extrabold text-emerald-700 text-lg">₱{{ number_format($netTotal, 2) }}</span>
                        </div>
                    @endif
                </div>

                {{-- Plan selection --}}
                <div class="fa-card p-4">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 mb-3">Select Your Preferred Payment Plan</div>
                    <div class="space-y-2.5">
                        @foreach ($choices as $choice)
                            <label class="fa-plan" :class="selected === @js($choice['key']) ? 'is-selected' : ''">
                                <input type="radio" name="choice" value="{{ $choice['key'] }}"
                                       x-model="selected" class="mt-1 w-4 h-4 text-indigo-600" style="flex:0 0 auto;">
                                <span style="min-width:0;">
                                    <span class="block font-bold text-slate-800 text-sm">{{ $choice['title'] }}</span>
                                    <span class="block text-xs text-slate-500 mt-0.5">{{ $choice['descriptor'] }}</span>
                                    @if ($plans->count() > 1)
                                        <span class="block text-[11px] text-slate-400 mt-0.5">{{ $choice['plan_name'] }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- RIGHT: live computation + schedule --}}
            <div class="space-y-5">

                {{-- Live computation --}}
                <div class="fa-card p-4">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 mb-2">Live Computation</div>
                    <div class="rounded-lg bg-indigo-50 border border-indigo-100 px-3 py-2 mb-3 text-sm font-bold text-indigo-700">
                        Selected Plan: <span x-text="active.title"></span>
                    </div>
                    <div class="fa-srow"><span class="text-slate-600">Total School Fees</span><span class="font-semibold text-slate-800" x-text="peso(active.gross_fees ?? active.total_fees)"></span></div>
                    <div class="fa-srow" x-show="(active.scholarship ?? 0) > 0"><span class="text-emerald-700">Scholarship</span><span class="font-semibold text-emerald-700" x-text="'− ' + peso(active.scholarship)"></span></div>
                    <div class="fa-srow"><span class="text-slate-600">Cash Discount</span><span class="font-semibold text-slate-800" x-text="peso(active.discount)"></span></div>
                    <div class="fa-srow"><span class="text-slate-600">Interest</span><span class="font-semibold text-slate-800" x-text="peso(active.interest)"></span></div>
                    <div class="fa-srow"><span class="text-slate-600">Downpayment</span><span class="font-semibold text-indigo-700" x-text="peso(active.downpayment)"></span></div>
                    <div class="fa-srow"><span class="text-slate-600">Remaining Balance</span><span class="font-semibold text-indigo-700" x-text="peso(active.remaining)"></span></div>
                    <div class="fa-srow"><span class="text-slate-700 font-semibold" x-text="installmentLabel + ' Installment'"></span><span class="font-extrabold text-indigo-700" x-text="peso(active.per_installment)"></span></div>
                    <div class="fa-srow"><span class="text-slate-600">No. of Installments</span><span class="font-semibold text-slate-800" x-text="active.num_installments"></span></div>
                    <div class="fa-srow" style="border-top:2px solid #e2e8f0; margin-top:0.25rem;"><span class="font-extrabold text-slate-800">Total Amount Due</span><span class="font-extrabold text-indigo-700" x-text="peso(active.total_due)"></span></div>
                </div>

                {{-- Payment schedule --}}
                <div class="fa-card p-4">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 mb-2">Payment Schedule</div>
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                            <thead>
                                <tr style="background:#4f46e5; color:#fff;">
                                    <th style="text-align:left; padding:0.5rem 0.6rem; border-radius:0.4rem 0 0 0.4rem;">Due Date</th>
                                    <th style="text-align:left; padding:0.5rem 0.6rem;">Description</th>
                                    <th style="text-align:right; padding:0.5rem 0.6rem; border-radius:0 0.4rem 0.4rem 0;">Amount (PHP)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, i) in active.schedule" :key="i">
                                    <tr style="border-bottom:1px solid #eef2f7;">
                                        <td style="padding:0.45rem 0.6rem;" class="text-slate-600" x-text="row.due_date"></td>
                                        <td style="padding:0.45rem 0.6rem;" class="text-slate-700" x-text="row.description"></td>
                                        <td style="padding:0.45rem 0.6rem; text-align:right;" class="font-semibold text-slate-800" x-text="peso(row.amount)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Late-payment penalty + agreement --}}
        @php
            $penaltyText = $penalty
                ? 'Late payment will incur a '.rtrim(rtrim(number_format($penalty->amount, 2), '0'), '.').($penalty->basis === 'percentage' ? '%' : ' peso').' penalty charge'
                    .($penalty->grace_days ? ' if overdue for '.$penalty->grace_days.' day'.($penalty->grace_days == 1 ? '' : 's').'.' : '.')
                : 'Late payment may incur a penalty charge per the school\'s finance policy.';
        @endphp
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 mt-5 text-sm text-amber-800 flex items-start gap-2">
            <span style="flex:0 0 auto;">⚠️</span>
            <span>{{ $penaltyText }}</span>
        </div>
        <label class="flex items-center gap-2 mt-3 text-sm text-slate-700">
            <input type="checkbox" name="agreed_to_penalty" value="1" x-model="agreed" class="w-4 h-4 rounded text-indigo-600"
                   @checked(! empty($saved['agreed_to_penalty']))>
            I agree to the late payment penalty policy stated above.
        </label>

        {{-- Footer --}}
        <div class="flex items-center justify-between pt-5">
            <a href="{{ route('public.apply.health', $term->id) }}"
               class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-bold">← Back</a>
            <button type="submit" x-bind:disabled="! agreed || ! selected"
                    class="px-7 py-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold shadow-lg"
                    :style="(! agreed || ! selected) ? 'opacity:.5;cursor:not-allowed;' : ''">
                Confirm Payment Plan →
            </button>
        </div>
    </form>
    @endif
</div>

<script>
    function financialAssessment({ comps, initial }) {
        const empty = { plan_name: '—', title: '—', total_fees: 0, discount: 0, interest: 0,
                        downpayment: 0, remaining: 0, per_installment: 0, num_installments: 0,
                        total_due: 0, frequency: 'monthly', frequency_label: 'Monthly', schedule: [] };
        return {
            comps: comps || {},
            selected: initial || '',
            agreed: {{ ! empty($saved['agreed_to_penalty']) ? 'true' : 'false' }},
            get active() { return this.comps[this.selected] || empty; },
            get installmentLabel() {
                const a = this.active;
                return a.num_installments > 1 ? (a.frequency_label || 'Per') : 'Single';
            },
            peso(n) {
                return '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    }
</script>
@endsection
