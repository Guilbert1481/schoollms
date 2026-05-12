@extends('layouts.app')

@section('content')
@php
    $activeSession = $room->activeSession;
@endphp

<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-800">{{ $room->title }}</h1>
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $activeSession ? 'border border-emerald-200 bg-emerald-100 text-emerald-700' : 'border border-slate-200 bg-slate-100 text-slate-700' }}">
                    {{ $activeSession ? 'Live Session' : ucfirst($room->status) }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-600">{{ $room->context_name ?: ($room->group?->name ?? 'General meeting') }}</p>
        </div>
        <a href="{{ route('tools.video-conference.rooms.join', $room) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Room</a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <section class="rounded-2xl border border-slate-200 bg-white p-0 shadow-sm flex flex-col h-full">
            <!-- Main Video/Classroom Area -->
            <div class="aspect-video rounded-t-2xl border-b border-slate-200 bg-black flex items-center justify-center relative">
                <span class="text-white text-lg opacity-60">Video Stream Placeholder</span>
                <!-- Participant Tiles (Zoom-style grid, placeholders for now) -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                    @for($i = 0; $i < 4; $i++)
                        <div class="w-20 h-20 rounded-lg bg-slate-800 border-2 border-white flex items-center justify-center text-white text-xs font-semibold shadow-md">User {{ $i+1 }}</div>
                    @endfor
                </div>
            </div>
            <!-- Bottom Bar with Feature Icons -->
            <div class="w-full flex items-center justify-center gap-6 py-4 bg-slate-50 rounded-b-2xl border-t border-slate-200">
                <a href="#" class="flex flex-col items-center group">
                    <i data-lucide="mic" class="h-6 w-6 text-slate-700 group-hover:text-sky-600"></i>
                    <span class="text-xs mt-1 text-slate-500">Mute</span>
                </a>
                <a href="#" class="flex flex-col items-center group">
                    <i data-lucide="video" class="h-6 w-6 text-slate-700 group-hover:text-sky-600"></i>
                    <span class="text-xs mt-1 text-slate-500">Camera</span>
                </a>
                <a href="{{ route('tools.video-conference.screen-share.index') }}" class="flex flex-col items-center group">
                    <i data-lucide="screen-share" class="h-6 w-6 text-slate-700 group-hover:text-sky-600"></i>
                    <span class="text-xs mt-1 text-slate-500">Share</span>
                </a>
                <a href="{{ route('tools.video-conference.chat.index') }}" class="flex flex-col items-center group">
                    <i data-lucide="message-circle" class="h-6 w-6 text-slate-700 group-hover:text-sky-600"></i>
                    <span class="text-xs mt-1 text-slate-500">Chat</span>
                </a>
                <a href="{{ route('tools.video-conference.participants.index') }}" class="flex flex-col items-center group">
                    <i data-lucide="users" class="h-6 w-6 text-slate-700 group-hover:text-sky-600"></i>
                    <span class="text-xs mt-1 text-slate-500">Participants</span>
                </a>
                <a href="{{ route('tools.video-conference.raise-hand.index') }}" class="flex flex-col items-center group">
                    <i data-lucide="hand" class="h-6 w-6 text-slate-700 group-hover:text-sky-600"></i>
                    <span class="text-xs mt-1 text-slate-500">Raise</span>
                </a>
                <a href="{{ route('tools.video-conference.whiteboard.index') }}" class="flex flex-col items-center group">
                    <i data-lucide="pen-tool" class="h-6 w-6 text-slate-700 group-hover:text-sky-600"></i>
                    <span class="text-xs mt-1 text-slate-500">Whiteboard</span>
                </a>
                <a href="{{ route('tools.video-conference.notes.index') }}" class="flex flex-col items-center group">
                    <i data-lucide="notebook-text" class="h-6 w-6 text-slate-700 group-hover:text-sky-600"></i>
                    <span class="text-xs mt-1 text-slate-500">Notes</span>
                </a>
                <a href="{{ route('tools.video-conference.buzz-in.index') }}" class="flex flex-col items-center group">
                    <i data-lucide="zap" class="h-6 w-6 text-slate-700 group-hover:text-sky-600"></i>
                    <span class="text-xs mt-1 text-slate-500">Buzz In</span>
                </a>
                <a href="{{ route('tools.video-conference.recording.index') }}" class="flex flex-col items-center group">
                    <i data-lucide="circle-dot" class="h-6 w-6 text-slate-700 group-hover:text-sky-600"></i>
                    <span class="text-xs mt-1 text-slate-500">Record</span>
                </a>
            </div>
        </section>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-sm font-semibold text-emerald-800">Live session details</p>
                @if($activeSession)
                    <p class="mt-2 text-sm text-emerald-700">Host: {{ $activeSession->host?->full_name ?? 'Unknown' }}</p>
                    <p class="mt-1 text-sm text-emerald-700">Started: {{ optional($activeSession->started_at)->format('M d, Y h:i A') }}</p>
                    <p class="mt-1 text-sm text-emerald-700">Auto end: {{ optional($activeSession->auto_end_at)->format('M d, Y h:i A') }}</p>
                @else
                    <p class="mt-2 text-sm text-emerald-700">No live session is running for this room right now.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-semibold text-slate-800">Phase 1 reminders</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li>Only one active recorder at a time.</li>
                    <li>Everyone can raise hand.</li>
                    <li>Host can mute, turn video off, and remove participants.</li>
                    <li>Session auto-ends after 3 hours.</li>
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection
