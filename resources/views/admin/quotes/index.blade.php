@extends('layouts.app')

@section('content')

<div class="min-h-screen bg-gray-50/30 py-6 md:py-10 px-4 md:px-6">
    <div class="w-full flex flex-col gap-4">

        {{-- Header: title + action buttons (same height, side by side) --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Quote Management</h1>
                <p class="text-sm text-slate-500">Create, rotate, and manage dashboard quotes.</p>
            </div>

            <div class="flex items-center gap-3">
                <button type="button"
                        onclick="openModal('create-quote-modal')"
                        class="h-11 px-5 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition text-sm whitespace-nowrap">
                    + Add Quote
                </button>
                <button type="button"
                        onclick="openModal('update-display-modal')"
                        class="h-11 px-5 rounded-xl bg-amber-500 text-white font-bold hover:bg-amber-600 transition text-sm whitespace-nowrap">
                    Update Display
                </button>
            </div>
        </div>

        {{-- Shareable table with built-in filter, pagination, columns settings --}}
        <x-table.table
            tableKey="quotes"
            :columns="$columns"
            :data="$quotes"
            :actions="[
                [
                    'type'    => 'modal',
                    'label'   => 'Edit',
                    'class'   => 'bg-blue-100 text-blue-700 hover:bg-blue-200',
                    'modal'   => 'edit-quote-modal',
                    'handler' => 'openEditQuote({id}, {theme}, {author}, {content})',
                ],
                [
                    'type'  => 'delete',
                    'label' => 'Delete',
                    'class' => 'bg-red-100 text-red-700 hover:bg-red-200',
                ],
            ]"
            deleteRoute="admin.quotes.destroy"
        />
    </div>

    @include('admin.quotes.partials.modals.create')
    @include('admin.quotes.partials.modals.update_display')
    @include('admin.quotes.partials.modals.edit')
@endsection

@push('scripts')
<script src="{{ asset('js/admin/quote.js') }}"></script>
@php
    $quoteRows = $quotes->map(function ($q) {
        return [
            'id'      => $q->id,
            'theme'   => $q->theme,
            'author'  => $q->author,
            'content' => $q->content,
        ];
    })->values();
@endphp
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const data = @json($quoteRows);
        document.querySelectorAll('#quotesTable tbody tr').forEach(function (tr, idx) {
            const editBtn = tr.querySelector('button[onclick*="openEditQuote"]');
            if (!editBtn) return;
            const row = data[idx];
            if (!row) return;
            editBtn.setAttribute('onclick', '');
            editBtn.addEventListener('click', function () {
                openEditQuote(row.id, row.theme, row.author, row.content);
            });
        });
    });
</script>
@endpush
