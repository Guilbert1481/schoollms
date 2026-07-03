{{--
    Invoices list (status pills + filter toolbar + table). Shared by the
    standalone Invoices page and the Billing page's "Invoices" tab.
    Expects: $rows, $columns, $actions, $status, $statuses, $filterRoute,
    $academicYears, $levels, $programOptions, $showProgramFilter,
    $gradeYearOptions, $paymentPlans, $invoiceFilters.
--}}
@php
    // Build-independent field styling (the compiled Tailwind build is stale).
    $invField = 'rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100';
    $invLabel = 'mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400';
@endphp
<div class="space-y-4">
    {{-- Status pills --}}
    <div class="flex flex-wrap gap-2">
        @foreach($statuses as $key => $label)
            <a href="{{ route($filterRoute, array_merge(request()->query(), ['status' => $key])) }}"
               class="rounded-full px-3 py-1 text-sm font-semibold {{ $status === $key ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Filter toolbar: dropdowns on the left, search input on the right.
         Flex layout is inline-styled so it can't be stripped by the stale build. --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
         style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:0.75rem;">
        <div>
            <label class="{{ $invLabel }}">Academic Year</label>
            <select onchange="invoiceFilter('academic_year_id', this.value)" class="{{ $invField }}" style="width:9rem;">
                <option value="">All</option>
                @foreach($academicYears as $ay)
                    <option value="{{ $ay->id }}" @selected((int) ($invoiceFilters['academic_year_id'] ?? 0) === (int) $ay->id)>{{ $ay->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="{{ $invLabel }}">Education Level</label>
            <select onchange="invoiceFilter('education_level', this.value)" class="{{ $invField }}" style="width:12rem;">
                <option value="">All Levels</option>
                @foreach($levels as $lvl)
                    <option value="{{ $lvl->id }}" @selected((int) ($invoiceFilters['education_level'] ?? 0) === (int) $lvl->id)>{{ $lvl->name }}</option>
                @endforeach
            </select>
        </div>

        @if($showProgramFilter)
            <div>
                <label class="{{ $invLabel }}">Program</label>
                <select onchange="invoiceFilter('program', this.value)" class="{{ $invField }}" style="width:14rem;">
                    <option value="">All Programs</option>
                    @foreach($programOptions as $pid => $pname)
                        <option value="{{ $pid }}" @selected((int) ($invoiceFilters['program'] ?? 0) === (int) $pid)>{{ $pname }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label class="{{ $invLabel }}">Grade / Year Level</label>
            <select onchange="invoiceFilter('grade_year', this.value)" class="{{ $invField }}" style="width:10rem;">
                <option value="">All</option>
                @foreach($gradeYearOptions as $val => $label)
                    <option value="{{ $val }}" @selected((string) ($invoiceFilters['grade_year'] ?? '') === (string) $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="{{ $invLabel }}">Payment Plan</label>
            <select onchange="invoiceFilter('payment_plan', this.value)" class="{{ $invField }}" style="width:11rem;">
                <option value="">All</option>
                @foreach($paymentPlans as $pp)
                    <option value="{{ $pp->id }}" @selected((int) ($invoiceFilters['payment_plan'] ?? 0) === (int) $pp->id)>{{ $pp->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Search input — pushed to the right (inline margin-left:auto so it
             survives a stale build); the reusable table's JS binds to this by id,
             so the table's own toolbar is hidden below. --}}
        <div style="margin-left:auto; align-self:flex-end;">
            <input type="text" id="finance_invoicesFilter" placeholder="Filter..."
                   class="{{ $invField }}" style="width:16rem; max-width:100%;">
        </div>
    </div>

    <x-table.table
        tableKey="finance_invoices"
        :columns="$columns"
        :data="$rows"
        :actions="$actions"
        perPage="20"
        :hideToolbar="true"
        emptyMessage="No invoices yet."
    />
</div>

<script>
    // Dropdown filters reload with the chosen param, preserving status + others.
    // Changing the Education Level invalidates the Grade/Year + Program picks.
    function invoiceFilter(key, value) {
        const url = new URL(window.location.href);
        if (value === '' || value === null) url.searchParams.delete(key);
        else url.searchParams.set(key, value);
        if (key === 'education_level') {
            url.searchParams.delete('grade_year');
            url.searchParams.delete('program');
        }
        window.location = url.toString();
    }
</script>
