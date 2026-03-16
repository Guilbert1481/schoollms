@extends('layouts.admin')

@section('content')

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">
        Programs
    </h1>

    <a href="{{ route('admin.academic.programs.create') }}"
       class="px-4 py-2 bg-primary text-white rounded-lg shadow hover:opacity-90">
        + Add Program
    </a>
</div>

<div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">

    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="px-6 py-3 text-left">Code</th>
                <th class="px-6 py-3 text-left">Program Name</th>
                <th class="px-6 py-3 text-left">Type</th>
                <th class="px-6 py-3 text-left">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($programs as $program)
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="px-6 py-4 font-medium">{{ $program->code }}</td>
                    <td class="px-6 py-4">{{ $program->name }}</td>
                    <td class="px-6 py-4 capitalize">{{ $program->type }}</td>
                    <td class="px-6 py-4">
                        @if ($program->is_active)
                            <span class="px-2 py-1 text-xs bg-green-100 text-green-600 rounded-full">
                                Active
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs bg-red-100 text-red-600 rounded-full">
                                Inactive
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-6 text-center text-slate-400">
                        No programs yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>

@endsection
