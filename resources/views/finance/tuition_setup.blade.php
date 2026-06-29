@extends('layouts.app')

@section('content')
@php
    $isFeeTab = in_array($tab, ['tuition', 'miscellaneous', 'other'], true);
    $routeMap = [
        'tuition'       => ['store' => 'fees.store',          'base' => 'fees',          'modal' => 'feeModal'],
        'miscellaneous' => ['store' => 'fees.store',          'base' => 'fees',          'modal' => 'feeModal'],
        'other'         => ['store' => 'fees.store',          'base' => 'fees',          'modal' => 'feeModal'],
        'discounts'     => ['store' => 'discounts.store',     'base' => 'discounts',     'modal' => 'discountModal'],
        'scholarships'  => ['store' => 'scholarships.store',  'base' => 'scholarships',  'modal' => 'scholarshipModal'],
        'payment-plans' => ['store' => 'payment-plans.store', 'base' => 'payment-plans', 'modal' => 'paymentPlanModal'],
        'penalty-rules' => ['store' => 'penalty-rules.store', 'base' => 'penalty-rules', 'modal' => 'penaltyModal'],
    ];
    $storeUrl    = route('finance.tuition-setup.'.$routeMap[$tab]['store']);
    $updateBase  = url('/finance/tuition-setup/'.$routeMap[$tab]['base']);
    $deleteRoute = 'finance.tuition-setup.'.$routeMap[$tab]['base'].'.destroy';
    $activeModal = $routeMap[$tab]['modal'];
    $sectionSub  = [
        'tuition'       => 'Set tuition rates per grade/year level and payment plan.',
        'miscellaneous' => 'Recurring miscellaneous fees assessed to students.',
        'other'         => 'Registration, laboratory, and other one-off fees.',
        'discounts'     => 'Discounts applied to a student\'s assessment.',
        'scholarships'  => 'Scholarship grants and their coverage.',
        'payment-plans' => 'Payment schemes students can be billed under.',
        'penalty-rules' => 'Late-payment penalties and surcharge rules.',
    ][$tab] ?? '';
    $fieldCls = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100';
@endphp

<div class="w-full space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-extrabold text-slate-800">Tuition &amp; Fees</h1>
        <p class="text-sm text-slate-500">Manage tuition rates, discounts, scholarships and payment plans.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Tabs (with icons) --}}
    <nav class="flex flex-wrap gap-x-6 gap-y-1 border-b border-slate-200 text-sm">
        @foreach($tabs as $key => $meta)
            <a href="{{ route('finance.tuition-setup.index', ['tab' => $key]) }}"
               class="-mb-px flex items-center gap-2 border-b-2 px-1 py-3 font-semibold {{ $tab === $key ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                <i data-lucide="{{ $meta['icon'] }}" class="h-4 w-4"></i>
                {{ $meta['label'] }}
            </a>
        @endforeach
    </nav>

    {{-- Filter toolbar --}}
    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        @if($isFeeTab)
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Academic Year</label>
                <select onchange="tuitionFilter('academic_year_id', this.value)" class="{{ $fieldCls }} w-44">
                    <option value="">All</option>
                    @foreach($academicYears as $ay)
                        <option value="{{ $ay->id }}" @selected((int) $filters['academic_year_id'] === (int) $ay->id)>{{ $ay->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Education Level</label>
                <select onchange="tuitionFilter('education_level', this.value)" class="{{ $fieldCls }} w-44">
                    <option value="">All Levels</option>
                    @foreach($levels as $lvl)
                        <option value="{{ $lvl->id }}" @selected((int) $filters['education_level'] === (int) $lvl->id)>{{ $lvl->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Grade / Year Level</label>
                <select onchange="tuitionFilter('grade_year', this.value)" class="{{ $fieldCls }} w-40">
                    <option value="">All</option>
                    @foreach($gradeYearOptions as $val => $label)
                        <option value="{{ $val }}" @selected((string) $filters['grade_year'] === (string) $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Payment Plan</label>
                <select onchange="tuitionFilter('payment_plan', this.value)" class="{{ $fieldCls }} w-44">
                    <option value="">All</option>
                    @foreach($paymentPlans as $pp)
                        <option value="{{ $pp->id }}" @selected((int) $filters['payment_plan'] === (int) $pp->id)>{{ $pp->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="ml-auto self-end">
            <button type="button" onclick="openCreateModal('{{ $activeModal }}')"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                <i data-lucide="plus" class="h-4 w-4"></i> {{ $addLabel }}
            </button>
        </div>
    </div>

    {{-- Active tab table --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3">
            <h2 class="text-base font-bold text-slate-800">{{ $tabs[$tab]['label'] }}</h2>
            <p class="text-xs text-slate-500">{{ $sectionSub }}</p>
        </div>

        <x-table.table
            tableKey="tuition_{{ str_replace('-', '_', $tab) }}"
            :columns="$columns"
            :data="$rows->values()"
            :actions="$actions"
            :deleteRoute="$deleteRoute"
            :hideToolbar="true"
            perPage="10"
            emptyMessage="Nothing here yet. Use the button above to add one."
        />
    </div>

    {{-- Important Notes --}}
    <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
        <div class="flex items-center gap-2 text-sm font-bold text-sky-800">
            <i data-lucide="info" class="h-4 w-4"></i> Important Notes
        </div>
        <ul class="mt-2 list-disc space-y-1 pl-8 text-sm text-sky-800">
            <li>Tuition rates are automatically applied when generating billings.</li>
            <li>Changes to tuition rates will only affect new billings and not existing ones.</li>
        </ul>
    </div>
</div>

{{-- ===================== Modals (active tab only) ===================== --}}
@if($isFeeTab)
    <x-modal.form id="feeModal" title="Fee" widthClass="w-full max-w-2xl">
        <form id="tuitionSetupForm" method="POST" action="{{ $storeUrl }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Fee Type</label>
                    <select name="fee_type" class="{{ $fieldCls }}">
                        @foreach($feeTypes as $val => $label)<option value="{{ $val }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Code</label>
                    <input type="text" name="code" class="{{ $fieldCls }}" placeholder="TUI-G1">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Fee Name</label>
                    <input type="text" name="name" class="{{ $fieldCls }}" placeholder="Grade 1 Tuition">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Education Level / Node</label>
                    <select name="education_node_id" class="{{ $fieldCls }}">
                        <option value="">All students</option>
                        @foreach($educationNodes as $node)<option value="{{ $node->id }}">{{ $node->label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Program (optional)</label>
                    <select name="program_id" class="{{ $fieldCls }}">
                        <option value="">—</option>
                        @foreach($programs as $p)<option value="{{ $p->id }}">{{ $p->code ? $p->code.' - ' : '' }}{{ $p->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Grade / Year Level</label>
                    <input type="number" name="year_level" min="1" max="20" class="{{ $fieldCls }}" placeholder="1">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Payment Plan</label>
                    <select name="payment_plan_id" class="{{ $fieldCls }}">
                        <option value="">—</option>
                        @foreach($paymentPlans as $pp)<option value="{{ $pp->id }}">{{ $pp->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Academic Year</label>
                    <select name="academic_year_id" class="{{ $fieldCls }}">
                        <option value="">—</option>
                        @foreach($academicYears as $ay)<option value="{{ $ay->id }}">{{ $ay->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Term</label>
                    <select name="term_id" class="{{ $fieldCls }}">
                        <option value="">—</option>
                        @foreach($terms as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Billing Basis</label>
                    <select name="billing_basis" class="{{ $fieldCls }}">
                        @foreach($billingBases as $val => $label)<option value="{{ $val }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Amount</label>
                    <input type="number" step="0.01" min="0" name="amount" class="{{ $fieldCls }}" placeholder="0.00">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-indigo-600"> Active
            </label>
        </form>
    </x-modal.form>
@elseif($tab === 'discounts')
    <x-modal.form id="discountModal" title="Discount" widthClass="w-full max-w-lg">
        <form id="tuitionSetupForm" method="POST" action="{{ $storeUrl }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Code</label><input type="text" name="code" class="{{ $fieldCls }}"></div>
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Name</label><input type="text" name="name" class="{{ $fieldCls }}"></div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Kind</label>
                    <select name="discount_kind" class="{{ $fieldCls }}">@foreach($discountKinds as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
                </div>
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Value</label><input type="number" step="0.01" min="0" name="value" class="{{ $fieldCls }}"></div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Applies To</label>
                    <select name="applies_to" class="{{ $fieldCls }}">@foreach($discountAppliesTo as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="requires_approval" value="1" class="rounded border-slate-300 text-indigo-600"> Requires approval</label>
            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-indigo-600"> Active</label>
        </form>
    </x-modal.form>
@elseif($tab === 'scholarships')
    <x-modal.form id="scholarshipModal" title="Scholarship" widthClass="w-full max-w-lg">
        <form id="tuitionSetupForm" method="POST" action="{{ $storeUrl }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Code</label><input type="text" name="code" class="{{ $fieldCls }}"></div>
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Name</label><input type="text" name="name" class="{{ $fieldCls }}"></div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Kind</label>
                    <select name="kind" class="{{ $fieldCls }}">@foreach($scholarshipKinds as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
                </div>
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Value</label><input type="number" step="0.01" min="0" name="value" class="{{ $fieldCls }}"></div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Coverage</label>
                    <select name="coverage" class="{{ $fieldCls }}">@foreach($scholarshipCoverage as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="requires_approval" value="1" checked class="rounded border-slate-300 text-indigo-600"> Requires approval</label>
            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-indigo-600"> Active</label>
        </form>
    </x-modal.form>
@elseif($tab === 'payment-plans')
    <x-modal.form id="paymentPlanModal" title="Payment Plan" widthClass="w-full max-w-lg">
        <form id="tuitionSetupForm" method="POST" action="{{ $storeUrl }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Code</label><input type="text" name="code" class="{{ $fieldCls }}" placeholder="CASH"></div>
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Name</label><input type="text" name="name" class="{{ $fieldCls }}" placeholder="Cash (Full Payment)"></div>
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Installments</label><input type="number" min="1" max="48" name="installments" value="1" class="{{ $fieldCls }}"></div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-indigo-600"> Active</label>
        </form>
    </x-modal.form>
@else
    <x-modal.form id="penaltyModal" title="Penalty Rule" widthClass="w-full max-w-lg">
        <form id="tuitionSetupForm" method="POST" action="{{ $storeUrl }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Code</label><input type="text" name="code" class="{{ $fieldCls }}"></div>
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Name</label><input type="text" name="name" class="{{ $fieldCls }}"></div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Basis</label>
                    <select name="basis" class="{{ $fieldCls }}">@foreach($penaltyBases as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
                </div>
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Amount / Rate</label><input type="number" step="0.01" min="0" name="amount" class="{{ $fieldCls }}"></div>
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Grace Days</label><input type="number" min="0" max="365" name="grace_days" value="0" class="{{ $fieldCls }}"></div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-indigo-600"> Active</label>
        </form>
    </x-modal.form>
@endif

<script>
    const TS_STORE_URL   = @json($storeUrl);
    const TS_UPDATE_BASE = @json($updateBase);
    const TS_PAYLOAD     = @json($editPayload);
    const TS_DEFAULT_FEE_TYPE = @json($isFeeTab ? $tab : null);

    function tuitionFilter(key, value) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', @json($tab));
        if (value === '' || value === null) url.searchParams.delete(key);
        else url.searchParams.set(key, value);
        window.location = url.toString();
    }

    function tsForm() { return document.getElementById('tuitionSetupForm'); }

    function openCreateModal(modalId) {
        const form = tsForm();
        if (form) {
            form.reset();
            form.action = TS_STORE_URL;
            const m = form.querySelector('[name="_method"]'); if (m) m.value = 'POST';
            // default fee type for the active fee tab
            if (TS_DEFAULT_FEE_TYPE) {
                const ft = form.querySelector('[name="fee_type"]'); if (ft) ft.value = TS_DEFAULT_FEE_TYPE;
            }
        }
        openModal(modalId);
    }

    function openRowModal(modalId, id) {
        const form = tsForm();
        const data = (TS_PAYLOAD && TS_PAYLOAD[id]) ? TS_PAYLOAD[id] : {};
        if (form) {
            form.reset();
            form.action = TS_UPDATE_BASE + '/' + id;
            const m = form.querySelector('[name="_method"]'); if (m) m.value = 'PUT';
            Object.keys(data).forEach((k) => {
                const el = form.querySelector('[name="' + k + '"]');
                if (!el) return;
                if (el.type === 'checkbox') el.checked = !!Number(data[k]);
                else el.value = (data[k] === null || data[k] === undefined) ? '' : data[k];
            });
        }
        openModal(modalId);
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
