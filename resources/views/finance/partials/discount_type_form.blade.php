@php
    $prefix = $prefix ?? 'discount';
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label for="{{ $prefix }}_code" class="mb-1 block text-sm font-semibold text-slate-700">Code</label>
        <input id="{{ $prefix }}_code" name="code" type="text" required maxlength="40"
               value="{{ old('code') }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
    </div>

    <div>
        <label for="{{ $prefix }}_name" class="mb-1 block text-sm font-semibold text-slate-700">Discount Name</label>
        <input id="{{ $prefix }}_name" name="name" type="text" required maxlength="191"
               value="{{ old('name') }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
    </div>

    <div>
        <label for="{{ $prefix }}_discount_kind" class="mb-1 block text-sm font-semibold text-slate-700">Kind</label>
        <select id="{{ $prefix }}_discount_kind" name="discount_kind" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            @foreach($discountKinds as $value => $label)
                <option value="{{ $value }}" @selected(old('discount_kind', 'percentage') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="{{ $prefix }}_value" class="mb-1 block text-sm font-semibold text-slate-700">Value</label>
        <input id="{{ $prefix }}_value" name="value" type="number" min="0" step="0.01" required
               value="{{ old('value', '0.00') }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
    </div>

    <div>
        <label for="{{ $prefix }}_applies_to" class="mb-1 block text-sm font-semibold text-slate-700">Applies To</label>
        <select id="{{ $prefix }}_applies_to" name="applies_to" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            @foreach($discountAppliesTo as $value => $label)
                <option value="{{ $value }}" @selected(old('applies_to', 'total') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex items-end gap-4">
        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
            <input type="hidden" name="requires_approval" value="0">
            <input id="{{ $prefix }}_requires_approval" type="checkbox" name="requires_approval" value="1"
                   class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                   @checked(old('requires_approval', '1') === '1')>
            Requires approval
        </label>

        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
            <input type="hidden" name="is_active" value="0">
            <input id="{{ $prefix }}_is_active" type="checkbox" name="is_active" value="1"
                   class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                   @checked(old('is_active', '1') === '1')>
            Active
        </label>
    </div>
</div>

<div>
    <label for="{{ $prefix }}_notes" class="mb-1 block text-sm font-semibold text-slate-700">Notes</label>
    <textarea id="{{ $prefix }}_notes" name="notes" rows="3"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">{{ old('notes') }}</textarea>
</div>
