@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-md flex flex-col items-center justify-center min-h-[60vh] p-4 md:p-8">
    <div class="w-full rounded-2xl border border-slate-200 bg-white p-8 shadow-lg flex flex-col items-center">
        <h1 class="text-2xl font-bold text-slate-800 mb-2">Start or Schedule a Meeting</h1>
        <p class="text-sm text-slate-600 mb-6 text-center">Create a new meeting room for your class or group, just like Zoom. You can also join or view existing rooms.</p>
        <a href="{{ route('tools.video-conference.rooms.create') }}" class="w-full mb-3 rounded-lg bg-sky-600 px-4 py-3 text-center text-base font-semibold text-white hover:bg-sky-700 transition">Create New Meeting</a>
        <a href="{{ route('tools.video-conference.rooms.index') }}" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-center text-base font-medium text-slate-700 hover:bg-slate-50 transition">View or Join Existing Rooms</a>
    </div>
</div>
@endsection
