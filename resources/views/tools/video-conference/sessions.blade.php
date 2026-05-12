@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Sessions</h1>
            <p class="text-sm text-slate-600">Session history for live, ended, and reopened meeting runs.</p>
        </div>
        <a href="{{ route('tools.video-conference.rooms.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Rooms</a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Auto End</p>
            <p class="mt-2 text-sm text-slate-700">Every live session ends automatically after 3 hours.</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reopen</p>
            <p class="mt-2 text-sm text-slate-700">Reopening creates a fresh session record under the same room.</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Host Rule</p>
            <p class="mt-2 text-sm text-slate-700">The person who starts a live session becomes the host for that run.</p>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @if($sessions->count() === 0)
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                No session records yet.
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Room</th>
                            <th class="px-4 py-3">Host</th>
                            <th class="px-4 py-3">Started</th>
                            <th class="px-4 py-3">Auto End</th>
                            <th class="px-4 py-3">Ended</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sessions as $session)
                            <tr class="border-t border-slate-200">
                                <td class="px-4 py-3 text-slate-700">
                                    <div class="font-medium">{{ $session->room?->title ?? 'Room removed' }}</div>
                                    @if($session->reopenedFrom)
                                        <div class="mt-1 text-xs text-slate-500">Reopened from session #{{ $session->reopenedFrom->id }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $session->host?->full_name ?? 'Unknown' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($session->started_at)->format('M d, Y h:i A') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($session->auto_end_at)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($session->ended_at)->format('M d, Y h:i A') ?: 'Still live' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $session->status === 'live' ? 'border border-emerald-200 bg-emerald-100 text-emerald-700' : 'border border-slate-200 bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst($session->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if($session->room)
                                            <a href="{{ route('tools.video-conference.rooms.join', $session->room) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Room</a>
                                        @endif
                                        @if($session->status === 'live')
                                            <form action="{{ route('tools.video-conference.sessions.end', $session) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">End</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $sessions->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
