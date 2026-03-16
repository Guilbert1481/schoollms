@extends('layouts.app')

@section('page-title', 'Chat Management')

@section('content')
<div class="max-w-6xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Chat Management</h2>
            <p class="text-sm text-slate-500 mt-1">
                Review, approve, and moderate student communications.
            </p>
        </div>
    </div>


    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
            {{ session('error') }}
        </div>
    @endif


    {{-- Table Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Student</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Created</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($chats as $chat)

                        @php
                            $statusColors = [
                                'pending'   => 'bg-amber-100 text-amber-700',
                                'approved'  => 'bg-emerald-100 text-emerald-700',
                                'escalated' => 'bg-rose-100 text-rose-700',
                                'closed'    => 'bg-slate-200 text-slate-700',
                            ];
                            $badgeClass = $statusColors[$chat->status] ?? 'bg-slate-100 text-slate-600';
                        @endphp

                        <tr class="hover:bg-slate-50 transition">

                            {{-- ID --}}
                            <td class="px-6 py-4 text-sm font-medium text-slate-700">
                                #{{ $chat->id }}
                            </td>

                            {{-- Student --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-bold">
                                        {{ strtoupper(substr($chat->student->name ?? 'NA', 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-800">
                                            {{ $chat->student->name ?? 'N/A' }}
                                        </div>
                                        <div class="text-xs text-slate-400">
                                            Student ID: {{ $chat->student->id ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide {{ $badgeClass }}">
                                    {{ ucfirst($chat->status) }}
                                </span>
                            </td>

                            {{-- Created --}}
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $chat->created_at->format('M d, Y') }}
                                <div class="text-xs text-slate-400">
                                    {{ $chat->created_at->format('h:i A') }}
                                </div>
                            </td>

                            {{-- Action --}}
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('communication.admin.chats.show', $chat) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium shadow-sm transition">
                                    View
                                </a>
                            </td>

                        </tr>

                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-400">
                                No chats available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $chats->links() }}
    </div>

</div>
@endsection
