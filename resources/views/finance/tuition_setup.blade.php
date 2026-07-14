@extends('layouts.app')

@section('content')
@php
    $isFeeTab = in_array($tab, ['tuition', 'miscellaneous', 'other'], true);
    $routeMap = [
        'tuition'       => ['store' => 'fees.store',          'base' => 'fees',          'modal' => 'feeModal'],
        'miscellaneous' => ['store' => 'fees.store',          'base' => 'fees',          'modal' => 'feeModal'],
        'other'         => ['store' => 'fees.store',          'base' => 'fees',          'modal' => 'feeModal'],
        'incidental'    => ['store' => 'incidental.store',    'base' => 'incidental',    'modal' => 'incidentalModal'],
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
        'incidental'    => 'One-off charges (play tickets, fair t-shirts, field trips). Saving one auto-bills the students it covers.',
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
            @if($showProgramFilter)
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Program</label>
                    <select onchange="tuitionFilter('program', this.value)" class="{{ $fieldCls }} w-52">
                        <option value="">All Programs</option>
                        @foreach($programOptions as $pid => $pname)
                            <option value="{{ $pid }}" @selected((int) ($filters['program'] ?? 0) === (int) $pid)>{{ $pname }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
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
                {{-- Payment plan applies once to the whole assessment (tuition +
                     miscellaneous + other − discounts − scholarships), chosen by the
                     guardian at enrollment — so it is not set per miscellaneous/other fee. --}}
                @if($tab === 'tuition')
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Payment Plan</label>
                    <select name="payment_plan_id" class="{{ $fieldCls }}">
                        <option value="">—</option>
                        @foreach($paymentPlans as $pp)<option value="{{ $pp->id }}">{{ $pp->name }}</option>@endforeach
                    </select>
                </div>
                @endif
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
@elseif($tab === 'incidental')
    <x-modal.form id="incidentalModal" title="Incidental Fee" widthClass="w-full max-w-2xl">
        <form id="tuitionSetupForm" method="POST" action="{{ $storeUrl }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" value="POST">

            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                <i data-lucide="info" class="mr-1 inline h-3.5 w-3.5"></i>
                Saving generates a one-time invoice for every officially-enrolled student the scope covers. Leave a scope field blank to widen it (e.g. no Program = all).
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Item</label>
                    <input type="text" name="name" class="{{ $fieldCls }}" placeholder="School Play Ticket">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Amount</label>
                    <input type="number" step="0.01" min="0" name="amount" class="{{ $fieldCls }}" placeholder="0.00">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Description <span class="font-normal text-slate-400">(optional)</span></label>
                    <input type="text" name="description" class="{{ $fieldCls }}" placeholder="Ticket for the annual school play">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Educational Level / Node</label>
                    <select name="education_node_id" class="{{ $fieldCls }}">
                        <option value="">All levels</option>
                        @foreach($educationNodes as $node)<option value="{{ $node->id }}">{{ $node->label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Program <span class="font-normal text-slate-400">(non-basic ed)</span></label>
                    <select name="program_id" class="{{ $fieldCls }}">
                        <option value="">All / not applicable</option>
                        @foreach($programs as $p)<option value="{{ $p->id }}">{{ $p->code ? $p->code.' - ' : '' }}{{ $p->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Year Level <span class="font-normal text-slate-400">(optional)</span></label>
                    <input type="number" name="year_level" min="1" max="20" class="{{ $fieldCls }}" placeholder="e.g. 5">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Academic Year <span class="font-normal text-slate-400">(optional)</span></label>
                    <select name="academic_year_id" class="{{ $fieldCls }}">
                        <option value="">All</option>
                        @foreach($academicYears as $ay)<option value="{{ $ay->id }}">{{ $ay->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Due Date <span class="font-normal text-slate-400">(optional)</span></label>
                    <input type="date" name="due_date" class="{{ $fieldCls }}">
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
    @php
        $dpSymStyle = 'position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); pointer-events:none; color:#94a3b8; font-size:0.875rem;';
        $cardCls    = 'rounded-lg border border-slate-200 p-3';
    @endphp
    <x-modal.form id="paymentPlanModal" title="Payment Plan" widthClass="w-full max-w-2xl">
        <form id="tuitionSetupForm" method="POST" action="{{ $storeUrl }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" value="POST">

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Code</label><input type="text" name="code" class="{{ $fieldCls }}" placeholder="STANDARD"></div>
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Plan Name</label><input type="text" name="name" class="{{ $fieldCls }}" placeholder="Standard Plan"></div>
            </div>

            {{-- Class & Billing schedule — drives how many installments are computed --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Class Start Date</label><input type="date" name="class_start_date" class="{{ $fieldCls }}"></div>
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Class End Date</label><input type="date" name="class_end_date" class="{{ $fieldCls }}"></div>
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">Billing / SOA End Date</label><input type="date" name="billing_end_date" class="{{ $fieldCls }}"></div>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Billing Day <span class="font-normal text-slate-400">(of the month)</span></label>
                    <input type="number" name="billing_day" min="1" max="31" placeholder="e.g. 8" class="{{ $fieldCls }}">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Days to Due Date <span class="font-normal text-slate-400">(after billing day)</span></label>
                    <input type="number" name="due_days" min="0" max="60" placeholder="e.g. 5" class="{{ $fieldCls }}">
                </div>
            </div>
            <p class="text-xs text-slate-500">No. of installments = periods from Class Start to the Billing/SOA End Date at the chosen frequency. Each invoice/SOA is billed on the Billing Day of its period (monthly, or every 3/6 months for quarterly/semi-annual). The due date is the Billing Day plus Days to Due Date (e.g. billing day 8 + 5 → due on the 13th; leave blank/0 to fall due on the billing day). Leave the schedule blank to fall back to the term dates.</p>

            {{-- Billing Invoice / SOA Frequency --}}
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Billing Invoice / SOA Frequency</label>
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem 1.75rem;">
                    @foreach($planFrequencies as $val => $label)
                        <label class="text-sm text-slate-700" style="display:inline-flex; align-items:center; gap:0.5rem;"><input type="checkbox" name="frequencies[]" value="{{ $val }}" class="rounded border-slate-300 text-indigo-600"> {{ $label }}</label>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-slate-500">Installment options divide the balance across the selected frequency.</p>
            </div>

            {{-- Payment Plan options --}}
            <div class="space-y-3">
                <label class="block text-sm font-semibold text-slate-700">Payment Plan Options</label>

                {{-- Option 1: Cash (Full Payment) + optional discount --}}
                <div class="{{ $cardCls }}">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" name="cash_enabled" value="1" data-opt-toggle="cash" onchange="planOptToggle(this)" class="rounded border-slate-300 text-indigo-600"> Cash (Full Payment)</label>
                    <div data-opt-body="cash" class="mt-3" style="display:none;">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Discount (optional)</label>
                        <div class="flex gap-2">
                            <select name="cash_discount_type" data-sym-for="cash" onchange="planSymSync(this)" class="{{ $fieldCls }}" style="max-width:11rem;">
                                @foreach($planValueTypes as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                            </select>
                            <div class="relative flex-1">
                                <span data-sym="cash" style="{{ $dpSymStyle }}">%</span>
                                <input type="number" step="0.01" min="0" name="cash_discount_value" value="0" data-val="cash" class="{{ $fieldCls }}" style="padding-left:1.75rem;">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Option 2: Down Payment + Installment --}}
                <div class="{{ $cardCls }}">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" name="dp_enabled" value="1" data-opt-toggle="dp" onchange="planOptToggle(this)" class="rounded border-slate-300 text-indigo-600"> Down Payment + Installment</label>
                    <div data-opt-body="dp" class="mt-3" style="display:none;">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Down Payment</label>
                        <div class="flex gap-2">
                            <select name="down_payment_type" data-sym-for="dp" onchange="planSymSync(this)" class="{{ $fieldCls }}" style="max-width:11rem;">
                                @foreach($planValueTypes as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                            </select>
                            <div class="relative flex-1">
                                <span data-sym="dp" style="{{ $dpSymStyle }}">%</span>
                                <input type="number" step="0.01" min="0" name="down_payment_value" value="0" data-val="dp" class="{{ $fieldCls }}" style="padding-left:1.75rem;">
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">The remaining balance is divided across the selected frequency.</p>
                    </div>
                </div>

                {{-- Option 3: Installment + Interest --}}
                <div class="{{ $cardCls }}">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" name="interest_enabled" value="1" data-opt-toggle="interest" onchange="planOptToggle(this)" class="rounded border-slate-300 text-indigo-600"> Installment + Interest</label>
                    <div data-opt-body="interest" class="mt-3" style="display:none;">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Interest Rate (%) &mdash; optional</label>
                        <div class="relative" style="max-width:11rem;">
                            <input type="number" step="0.01" min="0" max="100" name="interest_rate" value="0" class="{{ $fieldCls }}" style="padding-right:1.75rem;">
                            <span style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%); pointer-events:none; color:#94a3b8; font-size:0.875rem;">%</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">(Tuition + miscellaneous + interest) divided across the selected frequency.</p>
                    </div>
                </div>
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
        // Switching the Education Level invalidates the Grade/Year + Program picks.
        if (key === 'education_level') {
            url.searchParams.delete('grade_year');
            url.searchParams.delete('program');
        }
        window.location = url.toString();
    }

    function tsForm() { return document.getElementById('tuitionSetupForm'); }

    // Payment-plan option block: show/hide its fields with the header checkbox.
    function planOptToggle(cb) {
        if (!cb) return;
        const key = cb.getAttribute('data-opt-toggle');
        const body = cb.form ? cb.form.querySelector('[data-opt-body="' + key + '"]') : null;
        if (body) body.style.display = cb.checked ? '' : 'none';
    }
    window.planOptToggle = planOptToggle;

    // Swap the %/₱ adornment and lift the 0–100 cap when "Fixed amount" is chosen.
    function planSymSync(sel) {
        if (!sel) return;
        const key = sel.getAttribute('data-sym-for');
        if (!key || !sel.form) return;
        const fixed = sel.value === 'fixed';
        const sym = sel.form.querySelector('[data-sym="' + key + '"]');
        const val = sel.form.querySelector('[data-val="' + key + '"]');
        if (sym) sym.textContent = fixed ? '₱' : '%';
        if (val) { fixed ? val.removeAttribute('max') : val.setAttribute('max', '100'); }
    }
    window.planSymSync = planSymSync;

    // Re-sync every option block + symbol in the payment-plan form.
    function planVisualsSync(form) {
        if (!form) return;
        form.querySelectorAll('[data-opt-toggle]').forEach(planOptToggle);
        form.querySelectorAll('[data-sym-for]').forEach(planSymSync);
    }

    // Dedicated edit-fill for the payment-plan modal (multi-checkbox frequencies + option blocks).
    function fillPaymentPlan(form, d) {
        const setVal = (name, v) => { const el = form.querySelector('[name="' + name + '"]'); if (el) el.value = (v === null || v === undefined) ? '' : v; };
        const setChk = (name, on) => { const el = form.querySelector('[name="' + name + '"]'); if (el) el.checked = !!Number(on); };

        const asDate = (v) => (v ? String(v).slice(0, 10) : ''); // ISO datetime → YYYY-MM-DD

        setVal('code', d.code);
        setVal('name', d.name);
        setVal('class_start_date', asDate(d.class_start_date));
        setVal('class_end_date', asDate(d.class_end_date));
        setVal('billing_end_date', asDate(d.billing_end_date));
        setVal('billing_day', d.billing_day);
        setVal('due_days', d.due_days);
        setChk('is_active', d.is_active);

        setChk('cash_enabled', d.cash_enabled);
        setVal('cash_discount_type', d.cash_discount_type || 'percentage');
        setVal('cash_discount_value', d.cash_discount_value);

        setChk('dp_enabled', d.dp_enabled);
        setVal('down_payment_type', d.down_payment_type || 'percentage');
        setVal('down_payment_value', d.down_payment_value);

        setChk('interest_enabled', d.interest_enabled);
        setVal('interest_rate', d.interest_rate);

        let freqs = d.frequencies;
        if (typeof freqs === 'string') { try { freqs = JSON.parse(freqs); } catch (e) { freqs = []; } }
        if (!Array.isArray(freqs)) freqs = [];
        form.querySelectorAll('[name="frequencies[]"]').forEach((el) => { el.checked = freqs.indexOf(el.value) !== -1; });

        planVisualsSync(form);
    }

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
            if (modalId === 'paymentPlanModal') planVisualsSync(form);
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
            if (modalId === 'paymentPlanModal') {
                fillPaymentPlan(form, data);
            } else {
                Object.keys(data).forEach((k) => {
                    const el = form.querySelector('[name="' + k + '"]');
                    if (!el) return;
                    if (el.type === 'checkbox') el.checked = !!Number(data[k]);
                    else el.value = (data[k] === null || data[k] === undefined) ? '' : data[k];
                });
            }
        }
        openModal(modalId);
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
