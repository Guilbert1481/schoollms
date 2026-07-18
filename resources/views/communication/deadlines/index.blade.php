@extends('communication.layout')

@section('communication-content')

{{-- Main Header Wrapper --}}
<div class="flex items-center justify-between w-full mb-6">

    {{-- Title (Left) --}}
    <h2 class="text-xl font-semibold text-gray-800 whitespace-nowrap">
        Deadlines
    </h2>

    {{-- Right Side Controls --}}
    <div class="flex items-center gap-3">

        {{-- Search/Filter Bar --}}
    <div class="relative w-64">
        <input type="text" id="tableFilter" placeholder="Filter..." 
            class="w-full h-10 pl-14 pr-4 rounded-xl border border-gray-400 bg-white text-sm
                focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600
                hover:border-gray-500
                transition shadow-sm outline-none">

        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
            <i data-lucide="search" class="w-4 h-4 text-gray-500"></i>
        </div>
    </div>


        {{-- New Deadline Button --}}
        <a href="{{ route('communication.deadlines.create') }}"
           class="inline-flex items-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-800 transition shadow-sm whitespace-nowrap">
            <i data-lucide="plus" class="w-4 h-4"></i>
            New Deadline
        </a>

    </div>
</div>


<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden overflow-x-auto">
    <table class="min-w-full text-sm" id="deadlinesTable">
        <thead class="bg-gray-50 text-gray-600 border-b border-gray-100">
            <tr>
                <th class="text-left px-6 py-4 font-semibold uppercase tracking-wider text-[11px]">Title</th>
                <th class="text-left px-6 py-4 font-semibold uppercase tracking-wider text-[11px]">Created At</th>
                <th class="text-left px-6 py-4 font-semibold uppercase tracking-wider text-[11px]">Created By</th>
                <th class="text-left px-6 py-4 font-semibold uppercase tracking-wider text-[11px]">Due Date</th>
                <th class="text-left px-6 py-4 font-semibold uppercase tracking-wider text-[11px]">Compliance</th>
                <th class="text-left px-6 py-4 font-semibold uppercase tracking-wider text-[11px]">Status</th>
                <th class="text-left px-6 py-4 font-semibold uppercase tracking-wider text-[11px]">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($deadlines as $deadline)
                @php
                    $currentUser = auth()->user();
                    $isOwner = (int)$deadline->created_by === (int)$currentUser->id;
                    $isAdmin = $currentUser->is_admin ?? false; 
                    
                    $myCompletion = $deadline->userCompletions
                        ->where('user_id', $currentUser->id)
                        ->first();
                @endphp

                <tr class="hover:bg-gray-50 transition table-row-item">
                    <td class="px-6 py-4 font-medium text-gray-900">{{ $deadline->title }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $deadline->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-gray-600">
                        {{ $deadline->creator
                            ? $deadline->creator->first_name . ' ' .
                            ($deadline->creator->middle_name ? $deadline->creator->middle_name . ' ' : '') .
                            $deadline->creator->last_name
                            : 'N/A'
                        }}
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $deadline->due_date->format('M d, Y') }}</td>
                    <td class="px-6 py-4">
                        <span class="text-indigo-600 font-medium">
                            {{ $deadline->completed_users ?? 0 }} / {{ $deadline->total_users ?? 0 }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if($myCompletion && $myCompletion->status === 'completed')
                            <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-[11px] font-bold uppercase">Completed</span>
                        @elseif($deadline->due_date < now())
                            <span class="px-2.5 py-1 rounded-full bg-red-50 text-red-700 text-[11px] font-bold uppercase">Overdue</span>
                        @else
                            <span class="px-2.5 py-1 rounded-full bg-green-50 text-green-700 text-[11px] font-bold uppercase">Upcoming</span>
                        @endif
                    </td>

                    {{-- Actions (Left Aligned) --}}
                    <td class="px-6 py-4">
                        <div class="flex gap-4 items-center justify-start">
                            @if($isOwner || $isAdmin)
                                <a href="{{ route('communication.deadlines.manage', $deadline) }}"
                                   class="text-blue-600 hover:text-blue-800 transition">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('communication.deadlines.edit', $deadline) }}"
                                   class="text-amber-600 hover:text-amber-800 transition">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </a>
                            @else
                                <i data-lucide="lock" class="w-4 h-4 text-gray-300"></i>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-12 text-gray-500 italic">No deadlines available.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterInput = document.getElementById('tableFilter');
    const tableRows = document.querySelectorAll('.table-row-item');

    filterInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        tableRows.forEach(row => {
            const textContent = row.innerText.toLowerCase();
            row.style.display = textContent.includes(searchTerm) ? '' : 'none';
        });
    });

    if (typeof lucide !== 'undefined') { lucide.createIcons(); }
});
</script>

@endsection 