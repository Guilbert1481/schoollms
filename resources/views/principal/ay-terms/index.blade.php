{{-- Principal — Basic Ed Academic Year & Sessions --}}

@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6 space-y-6">

    @include('principal.curricula-panel.partials._header')

    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.principal_curricula_panel'),
    ])

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <div class="font-bold mb-1">Please fix the following:</div>
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6">

        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-extrabold text-slate-800">Basic-Ed Academic Years & Sessions</h2>
                <p class="text-xs text-slate-500">
                    One enrollment per school year. Special non-academic sessions (e.g. Training, Review) may be opened inside an AY.
                </p>
            </div>

            <button type="button"
                    class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold"
                    onclick="openCreateAYModal()">
                Create Academic Year
            </button>
        </div>

        <table class="w-full text-sm table-fixed">
            <thead>
                <tr class="text-left text-slate-500">
                    <th class="py-2 pr-4 w-[28%]">Academic Year / Session</th>
                    <th class="py-2 pr-4 w-[18%]">Start</th>
                    <th class="py-2 pr-4 w-[18%]">End</th>
                    <th class="py-2 pr-4 w-[14%]">Status</th>
                    <th class="py-2 pr-4 w-[22%]">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($academicYears as $ay)
                    <tr class="bg-slate-50/60">
                        <td class="py-3 pr-4 font-black text-slate-800 cursor-pointer"
                            onclick="toggleSessions({{ $ay->id }})">
                            {{ $ay->name }}
                            <div class="text-xs font-medium text-slate-500">Academic Year</div>
                        </td>
                        <td class="py-3 pr-4 font-semibold">{{ \Carbon\Carbon::parse($ay->start_date)->format('Y-m-d') }}</td>
                        <td class="py-3 pr-4 font-semibold">{{ \Carbon\Carbon::parse($ay->end_date)->format('Y-m-d') }}</td>
                        <td class="py-3 pr-4">
                            @php($status = $ay->computed_status)
                            @if($status === 'active')
                                <span class="inline-flex rounded-full bg-emerald-100 text-emerald-800 px-2 py-1 text-xs font-extrabold">Active</span>
                            @elseif($status === 'upcoming')
                                <span class="inline-flex rounded-full bg-amber-100 text-amber-800 px-2 py-1 text-xs font-extrabold">Upcoming</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 text-slate-800 px-2 py-1 text-xs font-extrabold">Closed</span>
                            @endif
                        </td>
                        <td class="py-3 pr-4">
                            <div class="flex items-center gap-2 whitespace-nowrap flex-wrap">

                                @if($ay->is_active)
                                    <form method="POST" action="{{ route('principal.ay-terms.academic_year.deactivate', $ay->id) }}">
                                        @csrf
                                        <button class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-amber-200 bg-amber-50 text-amber-800">Deactivate</button>
                                    </form>
                                @elseif($ay->can_activate)
                                    <form method="POST" action="{{ route('principal.ay-terms.academic_year.activate', $ay->id) }}">
                                        @csrf
                                        <button class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-emerald-200 bg-emerald-50 text-emerald-800">Activate</button>
                                    </form>
                                @endif

                                <button type="button"
                                        class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-indigo-200 bg-indigo-50 text-indigo-800"
                                        onclick="openEditAYModal(
                                            {{ (int) $ay->id }},
                                            @js($ay->name),
                                            @js(\Carbon\Carbon::parse($ay->start_date)->format('Y-m-d')),
                                            @js(\Carbon\Carbon::parse($ay->end_date)->format('Y-m-d'))
                                        )">Edit</button>

                                <form method="POST" action="{{ route('principal.ay-terms.academic_year.destroy', $ay->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-red-200 bg-red-50 text-red-800">Delete</button>
                                </form>

                                <button type="button"
                                        class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-slate-200"
                                        onclick="openCreateSessionModal(
                                            {{ (int) $ay->id }},
                                            @js($ay->name),
                                            @js(\Carbon\Carbon::parse($ay->start_date)->format('Y-m-d')),
                                            @js(\Carbon\Carbon::parse($ay->end_date)->format('Y-m-d'))
                                        )">+ Special Session</button>
                            </div>
                        </td>
                    </tr>

                    @php($sessions = ($termsByAcademicYearId[$ay->id] ?? collect()))
                    <tbody id="sessions-{{ $ay->id }}" class="hidden">
                        @foreach($sessions as $t)
                            <tr>
                                <td class="py-3 pr-4 pl-12">
                                    <div class="font-extrabold text-slate-800">{{ $t->name }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $t->term }}
                                        @if($t->title) · {{ $t->title }} @endif
                                    </div>
                                </td>
                                <td class="py-3 pr-4">{{ \Carbon\Carbon::parse($t->start_date)->format('Y-m-d') }}</td>
                                <td class="py-3 pr-4">{{ \Carbon\Carbon::parse($t->end_date)->format('Y-m-d') }}</td>
                                <td class="py-3 pr-4">
                                    @php($s = $t->computed_status)
                                    @if($s === 'active')
                                        <span class="inline-flex rounded-full bg-emerald-100 text-emerald-800 px-2 py-1 text-xs font-extrabold">Active</span>
                                    @elseif($s === 'upcoming')
                                        <span class="inline-flex rounded-full bg-amber-100 text-amber-800 px-2 py-1 text-xs font-extrabold">Upcoming</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-200 text-slate-800 px-2 py-1 text-xs font-extrabold">Closed</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <button type="button"
                                                class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-indigo-200 bg-indigo-50 text-indigo-800"
                                                onclick="openEditSessionModal(
                                                    {{ (int) $ay->id }},
                                                    {{ (int) $t->id }},
                                                    @js($t->term),
                                                    @js(\Carbon\Carbon::parse($t->start_date)->format('Y-m-d')),
                                                    @js(\Carbon\Carbon::parse($t->end_date)->format('Y-m-d')),
                                                    @js($t->title ?? '')
                                                )">Edit</button>

                                        <form method="POST" action="{{ route('principal.ay-terms.session.destroy', ['academicYearId' => $ay->id, 'id' => $t->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-red-200 bg-red-50 text-red-800">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">No academic years yet. Create one to open basic-ed enrollment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

{{-- =================== Modals =================== --}}

{{-- Create AY --}}
<x-modal.form id="createAYModal" title="Create Academic Year" widthClass="w-full max-w-lg">
    <form id="createAYForm" method="POST"
          action="{{ route('principal.ay-terms.academic_year.store') }}"
          class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-bold mb-1">Academic Year</label>
            @if(($adminAcademicYears ?? collect())->isNotEmpty())
                <select name="name" required class="w-full rounded-lg border p-2">
                    @foreach($adminAcademicYears as $i => $adminAy)
                        <option value="{{ $adminAy->name }}" @selected($i === 0)>{{ $adminAy->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1">
                    Fetched from the admin's academic years so the year label stays consistent. Set the basic-ed dates below.
                </p>
            @else
                <input type="text" name="name" required class="w-full rounded-lg border p-2" placeholder="2026-2027">
                <p class="text-xs text-amber-600 mt-1">
                    No admin-created academic year yet — type it here, or ask the admin to open the year first.
                </p>
            @endif
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-1">Start date</label>
                <input type="date" name="start_date" class="w-full rounded-lg border p-2">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">End date</label>
                <input type="date" name="end_date" class="w-full rounded-lg border p-2">
            </div>
        </div>
    </form>
</x-modal.form>

{{-- Edit AY --}}
<x-modal.form id="editAYModal" title="Edit Academic Year" widthClass="w-full max-w-lg">
    <form id="editAYForm" method="POST" action="#" class="space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-bold mb-1">Name</label>
            <input id="edit_ay_name" type="text" name="name" class="w-full rounded-lg border p-2">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-1">Start date</label>
                <input id="edit_ay_start_date" type="date" name="start_date" class="w-full rounded-lg border p-2">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">End date</label>
                <input id="edit_ay_end_date" type="date" name="end_date" class="w-full rounded-lg border p-2">
            </div>
        </div>
    </form>
</x-modal.form>

{{-- Create Session (special only) --}}
<x-modal.form id="createSessionModal" title="Create Special Session" widthClass="w-full max-w-lg">
    <form id="createSessionForm" method="POST" action="#" class="space-y-4">
        @csrf
        <div id="createSessionSub" class="text-xs text-slate-500"></div>

        <div>
            <label class="block text-sm font-bold mb-1">Title</label>
            <input id="create_session_title" type="text" name="title"
                   placeholder="e.g. Summer Reading, Robotics Bootcamp"
                   class="w-full rounded-lg border p-2">
        </div>

        <div>
            <label class="block text-sm font-bold mb-1">Session Type</label>
            <select id="create_session_term" name="term" class="w-full rounded-lg border p-2">
                <option value="" disabled selected>Select a session type</option>
                @foreach($specialTerms as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-1">Start date</label>
                <input id="create_session_start" type="date" name="start_date" class="w-full rounded-lg border p-2">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">End date</label>
                <input id="create_session_end" type="date" name="end_date" class="w-full rounded-lg border p-2">
            </div>
        </div>
        <div class="text-xs text-slate-500">Note: session dates must be within the academic year range.</div>
    </form>
</x-modal.form>

{{-- Edit Session --}}
<x-modal.form id="editSessionModal" title="Edit Special Session" widthClass="w-full max-w-lg">
    <form id="editSessionForm" method="POST" action="#" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-bold mb-1">Title</label>
            <input id="edit_session_title" type="text" name="title" class="w-full rounded-lg border p-2">
        </div>
        <div>
            <label class="block text-sm font-bold mb-1">Session Type</label>
            <select id="edit_session_term" name="term" class="w-full rounded-lg border p-2">
                @foreach($specialTerms as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-1">Start date</label>
                <input id="edit_session_start" type="date" name="start_date" class="w-full rounded-lg border p-2">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">End date</label>
                <input id="edit_session_end" type="date" name="end_date" class="w-full rounded-lg border p-2">
            </div>
        </div>
    </form>
</x-modal.form>

<script>
const AY_BASE = @json(url('/principal/ay-terms/academic-years'));

function toggleSessions(id) {
    const el = document.getElementById('sessions-' + id);
    if (el) el.classList.toggle('hidden');
}

function openCreateAYModal() { openModal('createAYModal'); }

function openEditAYModal(id, name, start, end) {
    document.getElementById('edit_ay_name').value = name || '';
    document.getElementById('edit_ay_start_date').value = start || '';
    document.getElementById('edit_ay_end_date').value = end || '';
    document.getElementById('editAYForm').action = `${AY_BASE}/${id}`;
    openModal('editAYModal');
}

function openCreateSessionModal(ayId, ayName, ayStart, ayEnd) {
    const form = document.getElementById('createSessionForm');
    form.action = `${AY_BASE}/${ayId}/sessions`;
    form.reset();
    document.getElementById('createSessionSub').textContent = `${ayName} (${ayStart} → ${ayEnd})`;
    openModal('createSessionModal');
}

function openEditSessionModal(ayId, id, term, start, end, title) {
    document.getElementById('edit_session_term').value = term || '';
    document.getElementById('edit_session_start').value = start || '';
    document.getElementById('edit_session_end').value = end || '';
    document.getElementById('edit_session_title').value = title || '';
    document.getElementById('editSessionForm').action = `${AY_BASE}/${ayId}/sessions/${id}`;
    openModal('editSessionModal');
}
</script>
@endsection
