@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Recording</h1>
            <p class="text-sm text-slate-600">Local-only recording placeholder. Files should save to the recorder's laptop, not your server.</p>
        </div>
        <a href="{{ route('tools.video-conference.rooms.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Rooms</a>
    </div>

    <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Recorder Policy</p>
            <p class="mt-2 text-sm text-rose-800">Only one active recorder is allowed at a time.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Host Rule</p>
            <p class="mt-2 text-sm text-slate-700">Host can always record.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Participant Rule</p>
            <p class="mt-2 text-sm text-slate-700">Participants can record only if host allows it.</p>
        </div>
    </section>
</div>
@endsection
