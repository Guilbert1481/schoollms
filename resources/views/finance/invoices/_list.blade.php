{{--
    Invoices list (status filter pills + table). Shared by the standalone
    Invoices page and the Billing page's "Invoices" tab.
    Expects: $rows, $columns, $actions, $status, $statuses, $filterRoute.
--}}
<div class="space-y-4">
    <div class="flex flex-wrap gap-2">
        @foreach($statuses as $key => $label)
            <a href="{{ route($filterRoute, ['status' => $key]) }}"
               class="rounded-full px-3 py-1 text-sm font-semibold {{ $status === $key ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <x-table.table
        tableKey="finance_invoices"
        :columns="$columns"
        :data="$rows"
        :actions="$actions"
        perPage="20"
        emptyMessage="No invoices yet."
    />
</div>
