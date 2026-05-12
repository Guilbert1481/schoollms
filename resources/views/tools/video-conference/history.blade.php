@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">History</h1>
            <p class="text-sm text-slate-600">Room and session history placeholder for later audit, attendance review, and follow-up workflows.</p>
        </div>
        <a href="{{ route('tools.video-conference.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Video Conference</a>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="space-y-3 text-sm text-slate-600">
            <p><strong class="text-slate-800">Room history</strong> should show room ownership, context, and latest session status.</p>
            <p><strong class="text-slate-800">Session history</strong> should show reopened sessions separately so attendance stays clean.</p>
        </div>
    </section>
</div>
@endsection
