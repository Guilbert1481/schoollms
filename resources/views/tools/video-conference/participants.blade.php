@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Participants</h1>
            <p class="text-sm text-slate-600">Host moderation area for mute, video off, removal, and room presence state.</p>
        </div>
        <a href="{{ route('tools.video-conference.rooms.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Rooms</a>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="overflow-hidden overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Participant</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Mic</th>
                        <th class="px-4 py-3">Video</th>
                        <th class="px-4 py-3">Host Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-t border-slate-200">
                        <td class="px-4 py-3 text-slate-700">Sample Student</td>
                        <td class="px-4 py-3 text-slate-600">Participant</td>
                        <td class="px-4 py-3 text-slate-600">On</td>
                        <td class="px-4 py-3 text-slate-600">On</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">Mute</span>
                                <span class="rounded-md border border-sky-200 bg-sky-50 px-2 py-1 text-xs font-medium text-sky-700">Video Off</span>
                                <span class="rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700">Remove</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
