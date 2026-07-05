{{--
    Reusable top-of-table filter toolbar (Student Ledgers pattern): an optional
    client-side search input on the left, URL-param dropdown filters beside it,
    and an actions slot (buttons, export menus, ...) pushed to the right.

    Usage:
        <x-table.filter-toolbar
            :filters="[
                ['key' => 'status',           'options' => $statusOptions, 'selected' => $statusFilter,  'all' => 'All Statuses'],
                ['key' => 'academic_year_id', 'options' => $academicYears, 'selected' => $academicYearId,'all' => 'All Academic Years'],
            ]"
            searchId="paymentsFilter" searchPlaceholder="Search payments...">
            <button ...>+ Record Payment</button>
        </x-table.filter-toolbar>

    Each filter: ['key' (query param), 'options' (assoc value => label),
    'selected' (current value), 'all' (label of the empty option)].
    Selecting an option navigates with that query param set/removed and every
    other param preserved (same semantics as ledgerApplyFilter).
--}}
@props([
    'filters' => [],
    'searchId' => null,
    'searchPlaceholder' => 'Filter...',
])

<div {{ $attributes->merge(['class' => 'mb-4']) }}
     style="display:flex; flex-wrap:wrap; align-items:center; gap:0.5rem;">
    @if($searchId)
        <input type="text" id="{{ $searchId }}" placeholder="{{ $searchPlaceholder }}"
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
               style="width:14rem; max-width:100%;">
    @endif

    @foreach($filters as $f)
        <select onchange="applyTableFilter(@js($f['key']), this.value)"
                class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
            <option value="">{{ $f['all'] ?? 'All' }}</option>
            @foreach(($f['options'] ?? []) as $value => $label)
                <option value="{{ $value }}" @selected((string) ($f['selected'] ?? '') === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
    @endforeach

    @if(trim($slot) !== '')
        <div style="margin-left:auto; display:flex; align-items:center; gap:0.5rem;">
            {{ $slot }}
        </div>
    @endif
</div>

@once
    <script>
        // Shared by every filter-toolbar on the page: set/remove one query
        // param and reload, preserving all the others.
        function applyTableFilter(key, value) {
            const url = new URL(window.location.href);
            if (value === '' || value === null) {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, value);
            }
            window.location = url.toString();
        }
    </script>
@endonce
