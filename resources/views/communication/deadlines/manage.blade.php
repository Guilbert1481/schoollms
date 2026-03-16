@extends('communication.layout')

@section('communication-content')
<div class="container mx-auto">

    {{-- VIEW A: MASTER LIST TABLE --}}
    @if(!$deadline)
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Deadline Management</h2>
                <p class="text-sm text-gray-500">Overview of all deadlines you have created.</p>
            </div>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Deadline Title</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Compliance</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($deadlines as $d)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-semibold text-gray-800">{{ $d->title }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $d->due_date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-center">
                                    <span class="text-xs font-bold {{ $d->completed_count == $d->user_completions_count ? 'text-green-600' : 'text-indigo-600' }}">
                                        {{ $d->completed_count }} / {{ $d->user_completions_count }}
                                    </span>
                                    <div class="w-24 h-1.5 bg-gray-100 rounded-full mt-1 overflow-hidden">
                                        @php 
                                            $percent = $d->user_completions_count > 0 ? ($d->completed_count / $d->user_completions_count) * 100 : 0;
                                        @endphp
                                        <div class="h-full bg-indigo-500" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('communication.deadlines.manage', ['id' => $d->id]) }}" 
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl text-sm font-bold hover:bg-indigo-100 transition">
                                    Manage Compliance <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500 italic">
                                No deadlines created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    {{-- VIEW B: COMPLIANCE DESK (The drill-down view) --}}
    @else
        <div class="mb-6 flex items-center gap-4">
            <a href="{{ route('communication.deadlines.index') }}" class="p-2 bg-white border rounded-full hover:bg-gray-100 transition">
                <i data-lucide="arrow-left" class="w-5 h-5 text-gray-500"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Compliance Desk</h2>
                <p class="text-sm text-gray-500">Verifying submissions for: <span class="font-bold text-indigo-600">{{ $deadline->title }}</span></p>
            </div>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6">
            <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2">
                Assigned Personnel 
                <span class="bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded-full">{{ $completionRecords->count() }} total</span>
            </h3>

            <div class="space-y-4">
                @forelse($completionRecords as $record)
                    <div class="flex justify-between items-center p-4 border border-gray-100 rounded-xl hover:bg-gray-50 transition">
                        <div>
                            <p class="font-bold text-gray-800">{{ $record->user->name }}</p>
                            <p class="text-xs text-gray-400 font-medium uppercase tracking-tighter"></p>
                        </div>

                        <div class="flex items-center gap-4">
                            @if($record->status === 'completed')
                                <div class="flex items-center gap-1.5 text-green-600 bg-green-50 px-3 py-1.5 rounded-lg text-xs font-bold uppercase">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i> Complied
                                </div>
                            @else
                                <span class="text-amber-500 text-xs font-bold uppercase">Pending Verification</span>
                                <form action="{{ route('communication.deadlines.markUserComplete', [$Deadline, $record->user]) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-sm transition">
                                        Verify Compliance
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 text-gray-400 italic bg-gray-50 rounded-xl border-2 border-dashed">
                        No users assigned to this deadlines.
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $completionRecords->appends(request()->query())->links() }}
            </div>
        </div>
    @endif
</div>
@endsection