@extends('layouts.app')

@section('content')

<div class="max-w-7xl mx-auto p-6">

    @include('school.settings.partials.tabs', [
        'tabs' => config('tabs.tabs.master_data')
    ])

    <div class="bg-white border rounded-2xl p-6 mt-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Training Master Data</h2>
            <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg">
                + Add Training
            </button>
        </div>

        <table class="w-full border border-slate-200">
            <thead class="bg-slate-100">
                <tr>
                    <th class="p-3 text-left">Training Type</th>
                    <th class="p-3 text-left">Course</th>
                    <th class="p-3 text-left">Assessment</th>
                    <th class="p-3 text-left">Created At</th>
                    <th class="p-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-t">
                    <td class="p-3">Review</td>
                    <td class="p-3">LET Review</td>
                    <td class="p-3">Pre-Test</td>
                    <td class="p-3">2026-01-01</td>
                    <td class="p-3">
                        <a href="#" class="text-blue-600">Edit</a>
                        <a href="#" class="text-red-600 ml-2">Delete</a>
                    </td>
                </tr>
            </tbody>
        </table>

    </div>

</div>

@endsection