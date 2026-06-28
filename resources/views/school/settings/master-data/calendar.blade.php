@extends('layouts.app')

@section('content')

<div class="max-w-7xl mx-auto p-6">

    @include('school.settings.partials.tabs', [
        'tabs' => config('tabs.tabs.master_data')
    ])

    <div class="bg-white border rounded-2xl p-6 mt-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Calendar Master Data</h2>
            <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg">
                + Add Record
            </button>
        </div>

        <table class="w-full border border-slate-200">
            <thead class="bg-slate-100">
                <tr>
                    <th class="p-3 text-left">School Year</th>
                    <th class="p-3 text-left">Term</th>
                    <th class="p-3 text-left">Start Date</th>
                    <th class="p-3 text-left">End Date</th>
                    <th class="p-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-t">
                    <td class="p-3">2025-2026</td>
                    <td class="p-3">1st Term</td>
                    <td class="p-3">Aug 01</td>
                    <td class="p-3">Dec 15</td>
                    <td class="p-3">
                        <div class="flex items-center gap-1">
                            <a href="#" aria-label="Edit"
                               class="relative group inline-flex h-8 w-8 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                                <x-table.action-tooltip label="Edit" />
                            </a>
                            <a href="#" aria-label="Delete"
                               class="relative group inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                <x-table.action-tooltip label="Delete" />
                            </a>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

    </div>

</div>

@endsection