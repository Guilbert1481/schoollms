@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Video Conference Rooms</h1>
            <p class="text-sm text-slate-600">Create rooms, review live status, and jump into sessions from one place.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tools.video-conference.permissions.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Permissions</a>
            <a href="{{ route('tools.video-conference.rooms.create') }}" class="rounded-lg bg-slate-800 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700">Create Room</a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Scheduled</p>
            <p class="mt-2 text-sm text-slate-600">Room exists but no live session is running yet.</p>
        </section>
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Live</p>
            <p class="mt-2 text-sm text-emerald-800">Active session started. The session host controls the room.</p>
        </section>
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Ended</p>
            <p class="mt-2 text-sm text-amber-800">Ended rooms can be reopened and will create a new session record.</p>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Rooms</h2>
                <p class="text-sm text-slate-500">Showing rooms for your school.</p>
            </div>
            <a href="{{ route('tools.video-conference.sessions.index') }}" class="text-sm font-medium text-slate-700 underline-offset-2 hover:underline">View All Sessions</a>
        </div>

        @if($rooms->count() === 0)
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                No rooms yet. Create the first room to start your video conferencing flow.
            </div>
        @else
            <div class="space-y-4">
                @foreach($rooms as $room)
                    @php
                        $activeSession = $room->activeSession;
                        $latestSession = $room->latestSession;
                    @endphp
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-semibold text-slate-800">{{ $room->title }}</h3>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $room->status === 'live' ? 'border border-emerald-200 bg-emerald-100 text-emerald-700' : ($room->status === 'ended' ? 'border border-amber-200 bg-amber-100 text-amber-700' : 'border border-slate-200 bg-white text-slate-700') }}">
                                        {{ ucfirst($room->status) }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-600">{{ $room->description ?: 'No description provided yet.' }}</p>
                                <div class="flex flex-wrap gap-3 text-xs text-slate-500">
                                    <span>Owner: <strong class="text-slate-700">{{ $room->owner?->full_name ?? 'Unknown' }}</strong></span>
                                    <span>Context: <strong class="text-slate-700">{{ $room->context_name ?: ($room->group?->name ?? 'General') }}</strong></span>
                                    <span>Group: <strong class="text-slate-700">{{ $room->group?->name ?? 'None' }}</strong></span>
                                    <span>Auto End: <strong class="text-slate-700">{{ $room->auto_end_minutes }} mins</strong></span>
                                </div>
                                @if($room->permission)
                                    <p class="text-xs text-slate-500">Student permission: {{ $room->permission->student?->full_name ?? 'Unknown student' }} via {{ $room->permission->teacher?->full_name ?? 'Unknown teacher' }}</p>
                                @endif
                                @if($activeSession)
                                    <p class="text-xs text-emerald-700">Live now. Host: {{ $activeSession->host?->full_name ?? 'Unknown' }}. Auto ends at {{ optional($activeSession->auto_end_at)->format('M d, Y h:i A') }}</p>
                                @elseif($latestSession)
                                    <p class="text-xs text-slate-500">Latest session started {{ optional($latestSession->started_at)->format('M d, Y h:i A') }} and ended {{ optional($latestSession->ended_at)->format('M d, Y h:i A') ?: 'not yet' }}.</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('tools.video-conference.rooms.join', $room) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Open</a>
                                @if($canManageAllRooms || $room->owner_user_id === auth()->id())
                                    <a href="{{ route('tools.video-conference.rooms.edit', $room) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Edit</a>
                                @endif
                                @if($activeSession)
                                    <a href="{{ route('tools.video-conference.meeting-room.show', $room) }}" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-500">Enter Live Room</a>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-5">
                {{ $rooms->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
