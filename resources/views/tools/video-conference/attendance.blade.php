@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Attendance</h1>
            <p class="text-sm text-slate-600">Meeting attendance placeholder for join time, leave time, and total duration per session.</p>
        </div>
        <a href="{{ route('tools.video-conference.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Video Conference</a>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="overflow-hidden overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Participant</th>
                        <th class="px-4 py-3">Joined</th>
                        <th class="px-4 py-3">Left</th>
                        <th class="px-4 py-3">Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-t border-slate-200">
                        <td class="px-4 py-3 text-slate-700">Sample Student</td>
                        <td class="px-4 py-3 text-slate-600">09:00 AM</td>
                        <td class="px-4 py-3 text-slate-600">11:32 AM</td>
                        <td class="px-4 py-3 text-slate-600">2h 32m</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
