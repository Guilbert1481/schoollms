@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Buzz In</h1>
            <p class="text-sm text-slate-600">Contest button placeholder for ordering participants from first to last press.</p>
        </div>
        <a href="{{ route('tools.video-conference.rooms.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Rooms</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">Participant Button</h2>
            <button type="button" class="mt-5 inline-flex items-center justify-center rounded-2xl bg-rose-600 px-8 py-8 text-lg font-bold text-white shadow-lg" disabled>
                PRESS BUTTON
            </button>
            <p class="mt-4 text-sm text-slate-600">Participants should be allowed one press per round.</p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">Game Master Order</h2>
            <ol class="mt-4 space-y-3 text-sm text-slate-600">
                <li>1. First to press</li>
                <li>2. Second to press</li>
                <li>3. Third to press</li>
            </ol>
        </section>
    </div>
</div>
@endsection
