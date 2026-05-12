@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Raise Hand</h1>
            <p class="text-sm text-slate-600">Everyone can raise hand; host should see the queue and clear entries as needed.</p>
        </div>
        <a href="{{ route('tools.video-conference.rooms.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Rooms</a>
    </div>

    <section class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">Participant Action</h2>
            <button type="button" class="mt-4 rounded-xl bg-amber-500 px-5 py-3 text-sm font-semibold text-white" disabled>Raise Hand</button>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">Host Queue</h2>
            <ol class="mt-4 space-y-2 text-sm text-slate-600">
                <li>1. Sample Student</li>
                <li>2. Another Participant</li>
            </ol>
        </div>
    </section>
</div>
@endsection
