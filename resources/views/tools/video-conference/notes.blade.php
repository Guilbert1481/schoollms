@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Notes</h1>
            <p class="text-sm text-slate-600">Private participant notes placeholder with autosave direction for Phase 1.</p>
        </div>
        <a href="{{ route('tools.video-conference.rooms.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Rooms</a>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <label for="notes-body" class="mb-2 block text-sm font-medium text-slate-700">My Private Notes</label>
        <textarea id="notes-body" rows="14" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700" placeholder="This note area will later autosave per user and per meeting session." disabled></textarea>
    </section>
</div>
@endsection
