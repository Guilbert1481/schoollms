{{--
    Shared form for academic policy create/edit.
    Expects: $policy (AcademicPolicy), $programs, $terms, $levels, $action (URL), $method ('POST'|'PUT')
--}}

@if ($errors->any())
    <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-800 rounded text-sm">
        <strong>Please fix the following:</strong>
        <ul class="ml-5 mt-1 list-disc">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="bg-white border rounded shadow p-6 space-y-6">
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif

    {{-- ============ SCOPE ============ --}}
    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">Scope (leave blank for "all")</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Education Level</span>
                <select name="education_level" class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
                    <option value="">All Levels</option>
                    @foreach ($levels as $lvl)
                        <option value="{{ $lvl }}" @selected(old('education_level', $policy->education_level) === $lvl)>
                            {{ ucfirst(str_replace('_',' ', $lvl)) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Program</span>
                <select name="program_id" class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
                    <option value="">All Programs</option>
                    @foreach ($programs as $p)
                        <option value="{{ $p->id }}" @selected((string)old('program_id', $policy->program_id) === (string)$p->id)>
                            {{ $p->code }} — {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Term</span>
                <select name="term_id" class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
                    <option value="">All Terms</option>
                    @foreach ($terms as $t)
                        <option value="{{ $t->id }}" @selected((string)old('term_id', $policy->term_id) === (string)$t->id)>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </label>
        </div>
    </section>

    {{-- ============ UNITS / LOAD ============ --}}
    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">Units &amp; Load</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Min Units</span>
                <input type="number" step="0.01" min="0" name="min_units"
                       value="{{ old('min_units', $policy->min_units) }}"
                       class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Max Units</span>
                <input type="number" step="0.01" min="0" name="max_units"
                       value="{{ old('max_units', $policy->max_units) }}"
                       class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Max Subjects</span>
                <input type="number" step="1" min="1" name="max_subjects"
                       value="{{ old('max_subjects', $policy->max_subjects) }}"
                       class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Overload Threshold</span>
                <input type="number" step="0.01" min="0" name="overload_threshold_units"
                       value="{{ old('overload_threshold_units', $policy->overload_threshold_units) }}"
                       class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
                <span class="text-xs text-slate-500">Units ≥ this amount require dean approval.</span>
            </label>
        </div>
    </section>

    {{-- ============ CAPACITY ============ --}}
    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">Section Capacity</h3>
        <label class="block max-w-xs">
            <span class="text-sm font-semibold text-slate-700">Max Section Capacity Override</span>
            <input type="number" step="1" min="1" name="max_section_capacity_override"
                   value="{{ old('max_section_capacity_override', $policy->max_section_capacity_override) }}"
                   class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
            <span class="text-xs text-slate-500">Leave blank to use the section's own capacity.</span>
        </label>
    </section>

    {{-- ============ PAYMENT GATE ============ --}}
    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">Payment Gate</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="flex items-center gap-2 mt-2">
                <input type="hidden" name="requires_payment_to_enrol" value="0">
                <input type="checkbox" name="requires_payment_to_enrol" value="1"
                       @checked(old('requires_payment_to_enrol', $policy->requires_payment_to_enrol))>
                <span class="text-sm font-semibold text-slate-700">Require payment to enrol</span>
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Min Payment Percent</span>
                <input type="number" step="0.01" min="0" max="100" name="min_payment_percent"
                       value="{{ old('min_payment_percent', $policy->min_payment_percent ?? 0) }}"
                       class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
            </label>
        </div>
    </section>

    {{-- ============ EFFECTIVITY / STATUS ============ --}}
    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">Effectivity &amp; Status</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Effective From</span>
                <input type="date" name="effective_from"
                       value="{{ old('effective_from', optional($policy->effective_from)->format('Y-m-d')) }}"
                       class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Effective To</span>
                <input type="date" name="effective_to"
                       value="{{ old('effective_to', optional($policy->effective_to)->format('Y-m-d')) }}"
                       class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
            </label>
            <label class="flex items-center gap-2 mt-7">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $policy->is_active ?? true))>
                <span class="text-sm font-semibold text-slate-700">Active</span>
            </label>
        </div>
    </section>

    <div class="flex justify-between pt-4 border-t">
        <a href="{{ route('dean.academic_policies.index') }}"
           class="px-4 py-2 bg-slate-200 rounded text-sm">Cancel</a>
        <button type="submit"
                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-sm font-semibold">
            Save Policy
        </button>
    </div>
</form>
