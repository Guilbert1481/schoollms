@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Screen Share</h1>
            <p class="text-sm text-slate-600">Phase 1 includes one-person-at-a-time screen sharing for teaching and presentations.</p>
        </div>
        <a href="{{ route('tools.video-conference.rooms.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Rooms</a>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="aspect-video rounded-2xl border border-dashed border-slate-300 bg-slate-100"></div>
        <p class="mt-4 text-sm text-slate-600">This screen will later hold presenter share state, share status, and stop-share controls.</p>
    </section>
</div>
@endsection
