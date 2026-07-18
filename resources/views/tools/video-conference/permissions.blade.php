@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Permissions</h1>
            <p class="text-sm text-slate-600">Teacher-controlled student room creation scope for classes, groups, and other meeting contexts.</p>
        </div>
        <a href="{{ route('tools.video-conference.rooms.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Rooms</a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
        @if($canManagePermissions)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-800">Grant Permission</h2>
                <form action="{{ route('tools.video-conference.permissions.store') }}" method="POST" class="mt-4 space-y-4">
                    @csrf

                    @if(auth()->user()->isAdmin() || auth()->user()->isSuperadmin())
                        <div>
                            <label for="teacher_id" class="mb-2 block text-sm font-medium text-slate-700">Teacher</label>
                            <select id="teacher_id" name="teacher_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700" required>
                                <option value="">Select teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ (string) old('teacher_id') === (string) $teacher->id ? 'selected' : '' }}>{{ $teacher->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="student_id" class="mb-2 block text-sm font-medium text-slate-700">Student</label>
                        <select id="student_id" name="student_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700" required>
                            <option value="">Select student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" {{ (string) old('student_id') === (string) $student->id ? 'selected' : '' }}>{{ $student->full_name }}</option>
                            @endforeach
                        </select>
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

                    <div>
                        <label for="context_name" class="mb-2 block text-sm font-medium text-slate-700">Context Name</label>
                        <input id="context_name" name="context_name" type="text" value="{{ old('context_name') }}" placeholder="Debate Team / Group Project / Class Discussion" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700" required>
                    </div>

                    <div>
                        <label for="notes" class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
                        <textarea id="notes" name="notes" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Grant Permission</button>
                </form>
            </section>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">{{ $canManagePermissions ? 'Granted Permissions' : 'My Active Permissions' }}</h2>

            @if($permissions->isEmpty())
                <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    No permissions found yet.
                </div>
            @else
                <div class="mt-4 overflow-hidden overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Context</th>
                                <th class="px-4 py-3">Teacher</th>
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Group</th>
                                <th class="px-4 py-3">Status</th>
                                @if($canManagePermissions)
                                    <th class="px-4 py-3">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permissions as $permission)
                                <tr class="border-t border-slate-200">
                                    <td class="px-4 py-3 text-slate-700">
                                        <div class="font-medium">{{ $permission->context_name }}</div>
                                        @if($permission->notes)
                                            <div class="mt-1 text-xs text-slate-500">{{ $permission->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $permission->teacher?->full_name ?? 'Unknown' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $permission->student?->full_name ?? 'Unknown' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $permission->group?->name ?? 'None' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $permission->is_active ? 'border border-emerald-200 bg-emerald-100 text-emerald-700' : 'border border-slate-200 bg-slate-100 text-slate-700' }}">
                                            {{ $permission->is_active ? 'Active' : 'Revoked' }}
                                        </span>
                                    </td>
                                    @if($canManagePermissions)
                                        <td class="px-4 py-3">
                                            <form action="{{ route('tools.video-conference.permissions.toggle', $permission) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                    {{ $permission->is_active ? 'Revoke' : 'Activate' }}
                                                </button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
