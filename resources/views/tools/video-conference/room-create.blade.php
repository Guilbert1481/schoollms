@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Create Room</h1>
            <p class="text-sm text-slate-600">Admins and teachers can create freely. Students can create only from active teacher-approved permission contexts.</p>
        </div>
        <a href="{{ route('tools.video-conference.rooms.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Rooms</a>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    <form action="{{ route('tools.video-conference.rooms.store') }}" method="POST" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="title" class="mb-2 block text-sm font-medium text-slate-700">Room Title</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700" required>
            </div>

            <div class="md:col-span-2">
                <label for="description" class="mb-2 block text-sm font-medium text-slate-700">Description</label>
                <textarea id="description" name="description" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700">{{ old('description') }}</textarea>
            </div>

            <div>
                <label for="starts_at" class="mb-2 block text-sm font-medium text-slate-700">Scheduled Start</label>
                <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700">
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Auto End</label>
                <input type="text" value="3 hours (180 minutes)" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700" disabled>
            </div>

            @if($isStudent)
                <div class="md:col-span-2">
                    <label for="permission_id" class="mb-2 block text-sm font-medium text-slate-700">Teacher Permission Context</label>
                    <select id="permission_id" name="permission_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700" required>
                        <option value="">Select approved context</option>
                        @foreach($studentPermissions as $permission)
                            <option value="{{ $permission->id }}" {{ (string) old('permission_id') === (string) $permission->id ? 'selected' : '' }}>
                                {{ $permission->context_name }}{{ $permission->group ? ' - '.$permission->group->name : '' }} | Teacher: {{ $permission->teacher?->full_name ?? 'Unknown' }}
                            </option>
                        @endforeach
                    </select>
                    @if($studentPermissions->isEmpty())
                        <p class="mt-2 text-sm text-amber-700">No active teacher permissions found. Ask your teacher to grant room creation access first.</p>
                    @endif
                </div>
            @else
                <div>
                    <label for="context_name" class="mb-2 block text-sm font-medium text-slate-700">Context Name</label>
                    <input id="context_name" name="context_name" type="text" value="{{ old('context_name') }}" placeholder="Class Discussion / Faculty Meeting / Advising" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700">
                </div>

                <div>
                    <label for="group_id" class="mb-2 block text-sm font-medium text-slate-700">Group</label>
                    <select id="group_id" name="group_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700">
                        <option value="">No specific group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ (string) old('group_id') === (string) $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500">Rooms can be reopened later. Each reopened session creates a new session record.</p>
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700" {{ $isStudent && $studentPermissions->isEmpty() ? 'disabled' : '' }}>
                Create Room
            </button>
        </div>
    </form>
</div>
@endsection
