@extends('layouts.app')
@extends('communication.layout')

@section('communication-content')

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-900">
        Chat
    </h2>

    <a href="{{ route('communication.chat.create') }}"
       class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-sm">
        New Chat
    </a>
</div>


<div class="bg-white border border-gray-100 rounded-2xl shadow-sm divide-y divide-gray-100 overflow-hidden">

    @forelse($threads as $thread)

    @php
        $otherUser = $thread->participants
            ->firstWhere('id', '!=', auth()->id());

        $participant = $thread->participants
            ->firstWhere('id', auth()->id());

        $lastRead = $participant?->pivot?->last_read_at;

        $unreadCount = $thread->messages()
            ->when($lastRead, function ($query) use ($lastRead) {
                $query->where('created_at', '>', $lastRead);
            })
            ->where('user_id', '!=', auth()->id())
            ->count();
    @endphp

    <a href="{{ route('communication.chat.show', $thread) }}"
       class="block px-6 py-4 transition
       {{ $unreadCount > 0 ? 'bg-indigo-50 hover:bg-indigo-100' : 'hover:bg-gray-50' }}">

        <div class="flex justify-between items-center">

            {{-- LEFT SIDE --}}
            <div class="flex items-center gap-4">

                {{-- Avatar --}}
                <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 
                            flex items-center justify-center text-sm font-semibold">
                    @if($thread->title)
                        {{ strtoupper(substr($thread->title, 0, 1)) }}
                    @elseif($otherUser)
                        {{ strtoupper(substr($otherUser->name, 0, 1)) }}
                    @else
                        G
                    @endif
                </div>

                {{-- Title + Meta --}}
                <div>

                    <p class="{{ $unreadCount > 0 ? 'font-semibold text-gray-900' : 'font-medium text-gray-900' }}">
                        @if($thread->title)
                            {{ $thread->title }}
                        @elseif($thread->type === 'private' && $otherUser)
                            {{ $otherUser->name }}
                        @else
                            Group Conversation
                        @endif
                    </p>

                    <p class="text-sm text-gray-500">
                        {{ ucfirst($thread->type) }} • 
                        Last updated {{ $thread->updated_at->diffForHumans() }}
                    </p>

                </div>

            </div>

            {{-- RIGHT SIDE --}}
            <div class="flex items-center gap-3">

                @if($unreadCount > 0)
                    <span style="background:red; color:white; padding:2px 6px; border-radius:9999px; font-size:11px; font-weight:600;">
                        {{ $unreadCount }}
                    </span>
                @endif

                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>

            </div>

        </div>

    </a>

@empty
    <div class="p-8 text-center text-gray-500">
        No conversations yet.
    </div>
@endforelse

</div>

@endsection

