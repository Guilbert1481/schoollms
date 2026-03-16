@extends('layouts.app')

@section('page-title', 'Chat Review')

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Header Section --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Chat Review</h2>
            <p class="text-sm text-slate-500 mt-1">
                Review and moderate student communication.
            </p>
        </div>

        {{-- Status Badge --}}
        @php
            $statusColors = [
                'pending'   => 'bg-amber-100 text-amber-700',
                'approved'  => 'bg-emerald-100 text-emerald-700',
                'escalated' => 'bg-rose-100 text-rose-700',
                'closed'    => 'bg-slate-200 text-slate-700',
            ];
            $badgeClass = $statusColors[$chat->status] ?? 'bg-slate-100 text-slate-600';
        @endphp

        <span class="px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wide {{ $badgeClass }}">
            {{ ucfirst($chat->status) }}
        </span>
    </div>


    {{-- Chat Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 space-y-6">

        {{-- Student Info --}}
        <div class="flex items-center gap-4 border-b border-slate-100 pb-6">
            <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                {{ strtoupper(substr($chat->student->name ?? 'NA', 0, 2)) }}
            </div>
            <div>
                <p class="font-semibold text-slate-800">
                    {{ $chat->student->name ?? 'N/A' }}
                </p>
                <p class="text-xs text-slate-500">
                    Submitted {{ $chat->created_at->format('M d, Y • h:i A') }}
                </p>
            </div>
        </div>

        {{-- Message --}}
        <div>
            <h4 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">
                Message
            </h4>
            <div class="bg-slate-50 rounded-xl p-6 text-slate-700 leading-relaxed border border-slate-100">
                {{ $chat->message }}
            </div>
        </div>

        {{-- Approval Info --}}
        @if($chat->approvedBy)
            <div class="text-sm text-slate-500 border-t border-slate-100 pt-4">
                Approved by <span class="font-medium text-slate-700">
                    {{ $chat->approvedBy->name }}
                </span>
                on {{ $chat->approved_at?->format('M d, Y • h:i A') }}
            </div>
        @endif

    </div>


    {{-- Action Buttons --}}
    <div class="flex items-center justify-between mt-8">

        <a href="{{ route('communication.admin.chats.index') }}"
           class="text-sm text-slate-500 hover:text-slate-700 transition">
            ← Back to Chat Management
        </a>

        <div class="flex items-center gap-3">

            @if($chat->status === 'pending')
                <form method="POST" action="{{ route('communication.admin.chats.approve', $chat) }}">
                    @csrf
                    <button class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium shadow-sm transition">
                        Approve
                    </button>
                </form>
            @endif

            @if(in_array($chat->status, ['pending','approved']))
                <form method="POST" action="{{ route('communication.admin.chats.escalate', $chat) }}">
                    @csrf
                    <button class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium shadow-sm transition">
                        Escalate
                    </button>
                </form>
            @endif

            @if(in_array($chat->status, ['approved','escalated']))
                <form method="POST" action="{{ route('communication.admin.chats.close', $chat) }}">
                    @csrf
                    <button class="px-5 py-2.5 rounded-xl bg-slate-700 hover:bg-slate-800 text-white text-sm font-medium shadow-sm transition">
                        Close
                    </button>
                </form>
            @endif

        </div>

    </div>

</div>
@endsection
