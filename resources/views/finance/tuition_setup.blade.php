@extends('layouts.app')

@section('content')
@php
    $feePayload = $fees->map(fn ($fee) => [
        'id' => $fee->id,
        'academic_year_id' => $fee->academic_year_id,
        'term_id' => $fee->term_id,
        'education_node_id' => $fee->education_node_id,
        'program_id' => $fee->program_id,
        'year_level' => $fee->year_level,
        'fee_type' => $fee->fee_type,
        'code' => $fee->code,
        'name' => $fee->name,
        'billing_basis' => $fee->billing_basis,
        'amount' => $fee->amount,
        'is_active' => (bool) $fee->is_active,
        'notes' => $fee->notes,
    ])->values();

    $discountPayload = $discounts->map(fn ($discount) => [
        'id' => $discount->id,
        'code' => $discount->code,
        'name' => $discount->name,
        'discount_kind' => $discount->discount_kind,
        'value' => $discount->value,
        'applies_to' => $discount->applies_to,
        'requires_approval' => (bool) $discount->requires_approval,
        'is_active' => (bool) $discount->is_active,
        'notes' => $discount->notes,
    ])->values();
@endphp

<div class="w-full space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Tuition &amp; Fees Setup</h1>
            <p class="text-sm text-slate-500">Finance master data for fee assessment and student discounts.</p>
        </div>
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <span class="rounded-full bg-slate-100 px-3 py-1">{{ $fees->count() }} fee rules</span>
            <span class="rounded-full bg-slate-100 px-3 py-1">{{ $discounts->count() }} discounts</span>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <div class="font-bold">Please check the form.</div>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-table.table
        tableKey="finance_fee_setups"
        :columns="$feeColumns"
        :data="$fees"
        :actions="$feeActions"
        deleteRoute="finance.tuition-setup.fees.destroy"
        createModal="feeCreateModal"
        createLabel="Add Fee"
        perPage="10"
    />

    <x-table.table
        tableKey="finance_discount_types"
        :columns="$discountColumns"
        :data="$discounts"
        :actions="$discountActions"
        deleteRoute="finance.tuition-setup.discounts.destroy"
        createModal="discountCreateModal"
        createLabel="Add Discount"
        perPage="10"
    />
</div>

<x-modal.form id="feeCreateModal" title="Add Fee Setup" widthClass="w-full max-w-3xl">
    <form method="POST" action="{{ route('finance.tuition-setup.fees.store') }}" class="space-y-4">
        @csrf
        @include('finance.partials.fee_setup_form', ['prefix' => 'fee_create', 'fee' => null])
    </form>
</x-modal.form>

<x-modal.form id="feeEditModal" title="Edit Fee Setup" widthClass="w-full max-w-3xl">
    <form id="feeEditForm" method="POST" action="#" class="space-y-4">
        @csrf
        @method('PUT')
        @include('finance.partials.fee_setup_form', ['prefix' => 'fee_edit', 'fee' => null])
    </form>
</x-modal.form>

<x-modal.form id="discountCreateModal" title="Add Discount Type" widthClass="w-full max-w-2xl">
    <form method="POST" action="{{ route('finance.tuition-setup.discounts.store') }}" class="space-y-4">
        @csrf
        @include('finance.partials.discount_type_form', ['prefix' => 'discount_create', 'discount' => null])
    </form>
</x-modal.form>

<x-modal.form id="discountEditModal" title="Edit Discount Type" widthClass="w-full max-w-2xl">
    <form id="discountEditForm" method="POST" action="#" class="space-y-4">
        @csrf
        @method('PUT')
        @include('finance.partials.discount_type_form', ['prefix' => 'discount_edit', 'discount' => null])
    </form>
</x-modal.form>

<script>
    const financeFeeRecords = @json($feePayload);
    const financeDiscountRecords = @json($discountPayload);
    const feeUpdateUrlTemplate = @json(route('finance.tuition-setup.fees.update', ['fee' => '__ID__']));
    const discountUpdateUrlTemplate = @json(route('finance.tuition-setup.discounts.update', ['discount' => '__ID__']));

    function setField(prefix, name, value) {
        const field = document.getElementById(`${prefix}_${name}`);
        if (!field) return;
        if (field.type === 'checkbox') {
            field.checked = Boolean(value);
            return;
        }
        field.value = value ?? '';
    }

    function openFeeEditModal(id) {
        const record = financeFeeRecords.find((item) => Number(item.id) === Number(id));
        if (!record) return;

        document.getElementById('feeEditForm').action = feeUpdateUrlTemplate.replace('__ID__', record.id);
        [
            'academic_year_id',
            'term_id',
            'education_node_id',
            'program_id',
            'year_level',
            'fee_type',
            'code',
            'name',
            'billing_basis',
            'amount',
            'is_active',
            'notes',
        ].forEach((name) => setField('fee_edit', name, record[name]));

        openModal('feeEditModal');
    }

    function openDiscountEditModal(id) {
        const record = financeDiscountRecords.find((item) => Number(item.id) === Number(id));
        if (!record) return;

        document.getElementById('discountEditForm').action = discountUpdateUrlTemplate.replace('__ID__', record.id);
        [
            'code',
            'name',
            'discount_kind',
            'value',
            'applies_to',
            'requires_approval',
            'is_active',
            'notes',
        ].forEach((name) => setField('discount_edit', name, record[name]));

        openModal('discountEditModal');
    }
</script>
@endsection
