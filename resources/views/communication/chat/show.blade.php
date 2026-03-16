@extends('layouts.app')


@section('content')
<div class="max-w-4xl mx-auto">

    <div class="bg-white shadow-lg rounded-2xl border border-slate-100 flex flex-col h-[75vh]">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">
                    @if($thread->title)
                        {{ $thread->title }}
                    @elseif($thread->type === 'private')
                        {{ $thread->participants->where('id', '!=', auth()->id())->first()->name ?? 'Private Chat' }}
                    @else
                        Group Conversation
                    @endif
                </h2>

                <p class="text-xs text-slate-500">
                    {{ ucfirst($thread->type) }} conversation
                </p>
            </div>
        </div>

        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-4">

            @php
                use Carbon\Carbon;

                $lastDate = null;
                $timezone = auth()->user()->timezone ?? config('app.timezone');
            @endphp

            @forelse($messages as $message)

                @php
                    $messageDate = Carbon::parse($message->created_at)->timezone($timezone);
                    $currentDate = $messageDate->format('Y-m-d');

                    $today = Carbon::now($timezone)->format('Y-m-d');
                    $yesterday = Carbon::now($timezone)->subDay()->format('Y-m-d');
                @endphp

                {{-- Date Separator --}}
                @if($lastDate !== $currentDate)
                    <div class="flex justify-center my-6">
                        <span class="px-4 py-1 text-xs font-medium text-slate-600 bg-slate-100 rounded-full shadow-sm">
                            @if($currentDate === $today)
                                Today
                            @elseif($currentDate === $yesterday)
                                Yesterday
                            @else
                                {{ $messageDate->format('F d, Y') }}
                            @endif
                        </span>
                    </div>
                    @php $lastDate = $currentDate; @endphp
                @endif

                {{-- Message Row --}}
                <div class="flex items-end gap-3
                    {{ $message->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">

                    {{-- Other User Photo --}}
                    @if($message->user_id !== auth()->id())
                        <img
                            src="{{ $message->user->profile_photo
                                    ? asset('storage/' . $message->user->profile_photo)
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($message->user->name) }}"
                            class="w-8 h-8 rounded-full object-cover shadow-sm">
                    @endif

                    <div class="max-w-xs">

                        {{-- Sender Name --}}
                        @if($message->user_id !== auth()->id())
                            <div class="text-xs text-slate-500 mb-1 ml-1">
                                {{ $message->user->name }}
                            </div>
                        @endif

                        {{-- Bubble --}}
                        <div class="px-4 py-2 rounded-2xl
                            {{ $message->user_id === auth()->id()
                                ? 'bg-indigo-600 text-white rounded-br-sm'
                                : 'bg-slate-100 text-slate-800 rounded-bl-sm' }}">

                            <div class="text-sm">
                                {{ $message->message }}
                            </div>

                            <div class="text-[10px] mt-1 opacity-70 text-right">
                                {{ $message->created_at
                                ->timezone(auth()->user()->timezone ?? 'UTC')
                                ->format('h:i A') }}


                            </div>
                        </div>
                    </div>

                    {{-- Your Photo --}}
                    @if($message->user_id === auth()->id())
                        <img
                            src="{{ auth()->user()->profile_photo
                                    ? asset('storage/' . auth()->user()->profile_photo)
                                    : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}"
                            class="w-8 h-8 rounded-full object-cover shadow-sm">
                    @endif

                </div>

            @empty
                <div class="text-center text-slate-400 text-sm">
                    No messages yet.
                </div>
            @endforelse

        </div>

        {{-- Message Form --}}
        <div class="border-t border-slate-100 p-4">

            <form method="POST" action="{{ route('communication.chat.message.store', $thread) }}">
                @csrf

                <div class="flex gap-3">

                    {{-- Back Button --}}
                    <a href="{{ route('communication.chat.index') }}"
                       class="px-4 py-2 rounded-xl bg-slate-200 text-slate-700
                              hover:bg-slate-300 transition whitespace-nowrap flex items-center gap-2">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        Back
                    </a>

                    {{-- Message Input --}}
                    <input type="text" name="message"
                        class="flex-1 border border-slate-200 rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        placeholder="Type a message..."
                        required>

                    {{-- Send --}}
                    <button type="submit"
                        class="px-6 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition">
                        Send
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


@endsection
