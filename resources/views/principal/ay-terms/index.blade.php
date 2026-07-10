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

        <div class="mb-4">
            <h2 class="text-lg font-extrabold text-slate-800">Basic-Ed Academic Years &amp; Sessions</h2>
            <p class="text-xs text-slate-500">
                One enrollment per school year. Special non-academic sessions (e.g. Training, Review) may be opened
                inside an AY — use a year's <span class="font-semibold">Sessions</span> button to view and manage them.
            </p>
        </div>

        <x-table.table
            tableKey="basicEdAY"
            :columns="$columns"
            :data="$rows"
            :hideActions="true"
            perPage="10"
            emptyMessage="No academic years yet. Create one to open basic-ed enrollment.">
            <button type="button" onclick="openCreateAYModal()"
                    class="px-4 py-2 rounded text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700">
                Create Academic Year
            </button>
        </x-table.table>

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

{{-- Sessions drawer (per-AY) — populated by openSessionsModal() --}}
<x-modal.form id="sessionsModal" title="Special Sessions" widthClass="w-full max-w-2xl" :hideFooter="true">
    <div id="sessionsModalSub" class="text-xs text-slate-500 mb-3"></div>
    <div id="sessionsModalBody"></div>
    <div class="mt-5 flex justify-end">
        <button type="button" id="sessionsAddBtn"
                class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold">
            + Special Session
        </button>
    </div>
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
const AY_BASE       = @json(url('/principal/ay-terms/academic-years'));
const AYS_BY_ID     = @json($aysJson);
const SESSIONS_BY_AY = @json($sessionsJson);
const AY_CSRF       = @json(csrf_token());

function ayEsc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
}

function openCreateAYModal() { openModal('createAYModal'); }

function openEditAYModal(id) {
    const ay = AYS_BY_ID[id] || {};
    document.getElementById('edit_ay_name').value       = ay.name  || '';
    document.getElementById('edit_ay_start_date').value = ay.start || '';
    document.getElementById('edit_ay_end_date').value   = ay.end   || '';
    document.getElementById('editAYForm').action = `${AY_BASE}/${id}`;
    openModal('editAYModal');
}

function openCreateSessionModal(ayId) {
    const ay = AYS_BY_ID[ayId] || {};
    const form = document.getElementById('createSessionForm');
    form.action = `${AY_BASE}/${ayId}/sessions`;
    form.reset();
    document.getElementById('createSessionSub').textContent = `${ay.name || ''} (${ay.start || ''} → ${ay.end || ''})`;
    openModal('createSessionModal');
}

function openEditSessionModal(ayId, id) {
    const list = SESSIONS_BY_AY[ayId] || [];
    const s = list.find(x => String(x.id) === String(id)) || {};
    document.getElementById('edit_session_term').value  = s.term  || '';
    document.getElementById('edit_session_start').value = s.start || '';
    document.getElementById('edit_session_end').value   = s.end   || '';
    document.getElementById('edit_session_title').value = s.title || '';
    document.getElementById('editSessionForm').action = `${AY_BASE}/${ayId}/sessions/${id}`;
    openModal('editSessionModal');
}

function openSessionsModal(ayId) {
    const ay   = AYS_BY_ID[ayId] || {};
    const list = SESSIONS_BY_AY[ayId] || [];

    document.getElementById('sessionsModalSub').textContent =
        `${ay.name || ''} (${ay.start || ''} → ${ay.end || ''}) — ${list.length} session(s)`;

    const badge = (st) =>
        st === 'active'
            ? '<span class="inline-flex rounded-full bg-emerald-100 text-emerald-800 px-2 py-0.5 text-xs font-bold">Active</span>'
        : st === 'upcoming'
            ? '<span class="inline-flex rounded-full bg-amber-100 text-amber-800 px-2 py-0.5 text-xs font-bold">Upcoming</span>'
            : '<span class="inline-flex rounded-full bg-slate-200 text-slate-800 px-2 py-0.5 text-xs font-bold">Closed</span>';

    const body = document.getElementById('sessionsModalBody');
    if (!list.length) {
        body.innerHTML = '<p class="text-sm text-slate-500 py-8 text-center">No special sessions yet for this academic year.</p>';
    } else {
        body.innerHTML = '<div class="divide-y divide-slate-100">' + list.map(s =>
            '<div class="flex items-center justify-between gap-3 py-3">'
              + '<div class="min-w-0">'
                + '<div class="font-extrabold text-slate-800 truncate">' + ayEsc(s.name) + '</div>'
                + '<div class="text-xs text-slate-500">' + ayEsc(s.term)
                  + (s.title ? ' · ' + ayEsc(s.title) : '') + ' · ' + ayEsc(s.start) + ' → ' + ayEsc(s.end) + '</div>'
              + '</div>'
              + '<div class="flex items-center gap-2 shrink-0">'
                + badge(s.status)
                + '<button type="button" onclick="openEditSessionModal(' + ayId + ',' + s.id + ')" '
                  + 'class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-indigo-200 bg-indigo-50 text-indigo-800">Edit</button>'
                + '<form method="POST" action="' + AY_BASE + '/' + ayId + '/sessions/' + s.id + '" '
                  + 'data-confirm="Delete this session?" class="inline">'
                  + '<input type="hidden" name="_token" value="' + AY_CSRF + '">'
                  + '<input type="hidden" name="_method" value="DELETE">'
                  + '<button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-extrabold border border-red-200 bg-red-50 text-red-800">Delete</button>'
                + '</form>'
              + '</div>'
            + '</div>'
        ).join('') + '</div>';
    }

    document.getElementById('sessionsAddBtn').onclick = function () {
        closeModal('sessionsModal');
        openCreateSessionModal(ayId);
    };

    openModal('sessionsModal');
}
</script>
@endsection
