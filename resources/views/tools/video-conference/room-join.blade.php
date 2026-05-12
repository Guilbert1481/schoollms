@extends('layouts.app')

@section('content')
@php
    $activeSession = $room->activeSession;
    $latestSession = $room->latestSession;
@endphp

<div class="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-800">{{ $room->title }}</h1>
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                    {{ $room->status === 'live' ? 'border border-emerald-200 bg-emerald-100 text-emerald-700' : ($room->status === 'ended' ? 'border border-amber-200 bg-amber-100 text-amber-700' : 'border border-slate-200 bg-white text-slate-700') }}">
                    {{ ucfirst($room->status) }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-600">{{ $room->description ?: 'No description provided yet.' }}</p>
        </div>
        <a href="{{ route('tools.video-conference.rooms.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Rooms</a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">Room Details</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Owner</dt>
                    <dd class="font-medium text-slate-800">{{ $room->owner?->full_name ?? 'Unknown' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Created By</dt>
                    <dd class="font-medium text-slate-800">{{ $room->creator?->full_name ?? 'Unknown' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Context</dt>
                    <dd class="font-medium text-slate-800">{{ $room->context_name ?: 'General' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Group</dt>
                    <dd class="font-medium text-slate-800">{{ $room->group?->name ?? 'None' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Scheduled Start</dt>
                    <dd class="font-medium text-slate-800">{{ optional($room->starts_at)->format('M d, Y h:i A') ?: 'Not scheduled' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Auto End</dt>
                    <dd class="font-medium text-slate-800">{{ $room->auto_end_minutes }} minutes</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">Session Action</h2>

            @if($activeSession)
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-sm font-semibold text-emerald-800">Live session in progress</p>
                    <p class="mt-2 text-sm text-emerald-700">Host: {{ $activeSession->host?->full_name ?? 'Unknown' }}</p>
                    <p class="mt-1 text-sm text-emerald-700">Auto ends at {{ optional($activeSession->auto_end_at)->format('M d, Y h:i A') }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('tools.video-conference.meeting-room.show', $room) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Enter Meeting Room</a>
                        <form action="{{ route('tools.video-conference.sessions.end', $activeSession) }}" method="POST">
                            @csrf
                            <button type="submit" class="rounded-lg border border-rose-300 bg-white px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">End Session</button>
                        </form>
                    </div>
                </div>
            @elseif($latestSession)
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-800">Last session ended</p>
                    <p class="mt-2 text-sm text-amber-700">Started: {{ optional($latestSession->started_at)->format('M d, Y h:i A') }}</p>
                    <p class="mt-1 text-sm text-amber-700">Ended: {{ optional($latestSession->ended_at)->format('M d, Y h:i A') ?: 'Not recorded' }}</p>
                    <form action="{{ route('tools.video-conference.sessions.reopen', $room) }}" method="POST" class="mt-4">
                        @csrf
                        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Reopen Room</button>
                    </form>
                </div>
            @else
                <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4">
                    <p class="text-sm font-semibold text-sky-800">No session has been started yet</p>
                    <p class="mt-2 text-sm text-sky-700">Starting a session makes the starter the host and applies the 3-hour auto-end policy.</p>
                    <form action="{{ route('tools.video-conference.sessions.start', $room) }}" method="POST" class="mt-4">
                        @csrf
                        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Start Session</button>
                    </form>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
