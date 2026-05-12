@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Whiteboard</h1>
            <p class="text-sm text-slate-600">Presenter-led whiteboard placeholder for discussion writing, drawing, erasing, and clearing.</p>
        </div>
        <a href="{{ route('tools.video-conference.rooms.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Rooms</a>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-wrap gap-2">
            <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">Pen</span>
            <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">Eraser</span>
            <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">Clear</span>
        </div>
        <div class="aspect-[16/10] rounded-2xl border border-dashed border-slate-300 bg-slate-50"></div>
    </section>
</div>
@endsection
