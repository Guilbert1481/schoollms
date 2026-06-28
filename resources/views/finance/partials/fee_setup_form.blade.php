@php
    $prefix = $prefix ?? 'fee';
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label for="{{ $prefix }}_code" class="mb-1 block text-sm font-semibold text-slate-700">Code</label>
        <input id="{{ $prefix }}_code" name="code" type="text" required maxlength="40"
               value="{{ old('code') }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
    </div>

    <div>
        <label for="{{ $prefix }}_name" class="mb-1 block text-sm font-semibold text-slate-700">Fee Name</label>
        <input id="{{ $prefix }}_name" name="name" type="text" required maxlength="191"
               value="{{ old('name') }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
    </div>

    <div>
        <label for="{{ $prefix }}_fee_type" class="mb-1 block text-sm font-semibold text-slate-700">Fee Type</label>
        <select id="{{ $prefix }}_fee_type" name="fee_type" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            @foreach($feeTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('fee_type', 'tuition') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="{{ $prefix }}_billing_basis" class="mb-1 block text-sm font-semibold text-slate-700">Billing Basis</label>
        <select id="{{ $prefix }}_billing_basis" name="billing_basis" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            @foreach($billingBases as $value => $label)
                <option value="{{ $value }}" @selected(old('billing_basis', 'fixed') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="{{ $prefix }}_amount" class="mb-1 block text-sm font-semibold text-slate-700">Amount</label>
        <input id="{{ $prefix }}_amount" name="amount" type="number" min="0" step="0.01" required
               value="{{ old('amount', '0.00') }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
    </div>

    <div>
        <label for="{{ $prefix }}_year_level" class="mb-1 block text-sm font-semibold text-slate-700">Year/Grade Level</label>
        <input id="{{ $prefix }}_year_level" name="year_level" type="number" min="1" max="20"
               value="{{ old('year_level') }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
    </div>

    <div>
        <label for="{{ $prefix }}_academic_year_id" class="mb-1 block text-sm font-semibold text-slate-700">Academic Year</label>
        <select id="{{ $prefix }}_academic_year_id" name="academic_year_id"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            <option value="">All academic years</option>
            @foreach($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id') === (string) $academicYear->id)>
                    {{ $academicYear->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="{{ $prefix }}_term_id" class="mb-1 block text-sm font-semibold text-slate-700">Term</label>
        <select id="{{ $prefix }}_term_id" name="term_id"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            <option value="">All terms</option>
            @foreach($terms as $term)
                <option value="{{ $term->id }}" @selected((string) old('term_id') === (string) $term->id)>
                    {{ $term->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="{{ $prefix }}_education_node_id" class="mb-1 block text-sm font-semibold text-slate-700">Education Level</label>
        <select id="{{ $prefix }}_education_node_id" name="education_node_id"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            <option value="">All levels</option>
            @foreach($educationNodes as $node)
                <option value="{{ $node->id }}" @selected((string) old('education_node_id') === (string) $node->id)>
                    {{ $node->label }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="{{ $prefix }}_program_id" class="mb-1 block text-sm font-semibold text-slate-700">Program</label>
        <select id="{{ $prefix }}_program_id" name="program_id"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            <option value="">All programs</option>
            @foreach($programs as $program)
                <option value="{{ $program->id }}" @selected((string) old('program_id') === (string) $program->id)>
                    {{ $program->code ? $program->code.' - ' : '' }}{{ $program->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div>
    <label for="{{ $prefix }}_notes" class="mb-1 block text-sm font-semibold text-slate-700">Notes</label>
    <textarea id="{{ $prefix }}_notes" name="notes" rows="3"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">{{ old('notes') }}</textarea>
</div>

<label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
    <input type="hidden" name="is_active" value="0">
    <input id="{{ $prefix }}_is_active" type="checkbox" name="is_active" value="1"
           class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
           @checked(old('is_active', '1') === '1')>
    Active
</label>
