@extends('layouts.app')

@section('content')
<style>
    /* Self-contained on/off switch — build-independent (no Tailwind peer utilities). */
    .fr-switch { position: relative; display: inline-block; width: 38px; height: 22px; flex: 0 0 auto; }
    .fr-switch input { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; }
    .fr-switch .fr-track { position: absolute; inset: 0; background: #cbd5e1; border-radius: 9999px; transition: background .2s; }
    .fr-switch .fr-track::before { content: ""; position: absolute; left: 3px; top: 3px; width: 16px; height: 16px; background: #fff; border-radius: 9999px; box-shadow: 0 1px 2px rgba(0,0,0,.2); transition: transform .2s; }
    .fr-switch input:checked + .fr-track { background: #4f46e5; }
    .fr-switch input:checked + .fr-track::before { transform: translateX(16px); }
</style>
<div class="w-full space-y-5">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Document Requirements</h1>
        <p class="text-sm text-slate-500">
            Documents each student type must upload with their enrollment application.
            New students, transferees, returnees and shifters see these as required uploads on the enrollment form.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <x-table.level-tabs
        route="registrar.settings.documents.index"
        :levels="$levels"
        :activeLevelId="$activeLevelId"
        :showAll="$showAll"
    />

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <x-table.table
            tableKey="document_requirements"
            :columns="$columns"
            :data="$rows"
            :hideActions="true"
            perPage="20"
            emptyMessage="No document requirements for this selection yet."
        >
            <button type="button" onclick="openAddRequirement()"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                <i data-lucide="plus" class="h-4 w-4"></i> Add Document
            </button>
        </x-table.table>
    </div>
</div>

{{-- ===== Add / Edit modal (reusable draggable + resizable) ===== --}}
<x-modal.form id="documentRequirementModal" title="Document Requirement" widthClass="w-full max-w-lg" :hideFooter="true">
    <form id="documentRequirementForm" method="POST" action="{{ route('registrar.settings.documents.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="_method" id="reqFormMethod" value="POST">

        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700">Student Type <span class="text-rose-500">*</span></label>
            <select name="student_type" id="reqStudentType" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="" disabled selected>— Select student type —</option>
                @foreach($studentTypes as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Level</label>
                <select name="education_node_id" id="reqLevel" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" onchange="reqSyncLevelFields()">
                    <option value="">Any level</option>
                    @foreach($levels as $level)
                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Year/Grade</label>
                <select name="year_level" id="reqYearLevel" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Any</option>
                </select>
            </div>
        </div>

        <div id="reqProgramWrap" class="hidden">
            <label class="mb-1 block text-sm font-semibold text-slate-700">Program</label>
            <select name="program_id" id="reqProgram" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Any program</option>
            </select>
        </div>

        {{-- Foreigner scoping: toggle on = only for foreign applicants; optional
             nationality narrows it to one nationality (blank = any foreigner). --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Foreigner requirement</label>
                <label class="inline-flex cursor-pointer select-none items-center gap-2">
                    <span class="fr-switch">
                        <input type="checkbox" name="for_foreigner" id="reqForeigner" value="1" onchange="reqToggleForeigner()">
                        <span class="fr-track"></span>
                    </span>
                    <span class="text-sm text-slate-600">Only for foreign applicants</span>
                </label>
            </div>
            <div id="reqNationalityWrap" class="hidden">
                <label class="mb-1 block text-sm font-semibold text-slate-700">
                    Nationality <span class="font-normal text-slate-400">(optional)</span>
                </label>
                <input type="text" name="nationality" id="reqNationality" list="reqNationalityOptions" maxlength="100"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                       placeholder="Any foreign — or e.g. Indian">
                <datalist id="reqNationalityOptions">
                    @foreach($nationalityOptions as $nat)
                        <option value="{{ $nat }}"></option>
                    @endforeach
                </datalist>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700">Documents <span class="text-rose-500">*</span></label>
            <div id="reqDocsList" class="space-y-2"></div>
            <button type="button" onclick="reqAddDocRow('')"
                    class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                <i data-lucide="plus" class="h-4 w-4"></i> Add another document
            </button>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <button type="button" onclick="closeModal('documentRequirementModal')"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
            <button type="submit" id="reqSubmitBtn"
                    class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save Requirement</button>
        </div>
    </form>
</x-modal.form>

{{-- ===== Read-only view modal ===== --}}
<x-modal.form id="viewRequirementModal" title="Required Documents" widthClass="w-full max-w-md" :hideFooter="true">
    <div id="viewRequirementBody" class="text-sm text-slate-600"></div>
</x-modal.form>

{{-- Hidden delete form --}}
<form id="deleteRequirementForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
    const REQUIREMENTS   = @json($requirementsJson);
    const PROGRAMS_BY_ROOT = @json($programsByRoot);
    const YEARS_BY_ROOT  = @json($yearOptionsByRoot);
    const BASIC_ROOTS    = @json($basicRootIds);
    const STORE_URL      = @json(route('registrar.settings.documents.store'));
    const UPDATE_URL     = @json(route('registrar.settings.documents.update', ['requirement' => '__ID__']));
    const DELETE_URL     = @json(route('registrar.settings.documents.destroy', ['requirement' => '__ID__']));

    function reqEsc(s) {
        return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function reqSyncLevelFields(selectedProgram = '', selectedYear = '') {
        const rootId = parseInt(document.getElementById('reqLevel').value || '0', 10);

        // Program select — higher-ed roots with programs only.
        const wrap = document.getElementById('reqProgramWrap');
        const prog = document.getElementById('reqProgram');
        const programs = (rootId && !BASIC_ROOTS.includes(rootId)) ? (PROGRAMS_BY_ROOT[rootId] || []) : [];
        prog.innerHTML = '<option value="">Any program</option>' + programs.map(
            (p) => `<option value="${p.id}">${reqEsc(p.label)}</option>`
        ).join('');
        prog.value = selectedProgram || '';
        wrap.classList.toggle('hidden', programs.length === 0);

        // Year/Grade select — per-level options, generic 1-12 for "Any level".
        const year = document.getElementById('reqYearLevel');
        let options = rootId ? (YEARS_BY_ROOT[rootId] || []) : [];
        if (!options.length) {
            options = Array.from({ length: 12 }, (_, i) => ({ value: String(i + 1), label: String(i + 1) }));
        }
        year.innerHTML = '<option value="">Any</option>' + options.map(
            (o) => `<option value="${reqEsc(o.value)}">${reqEsc(o.label)}</option>`
        ).join('');
        year.value = selectedYear || '';
    }

    function reqToggleForeigner() {
        const on = document.getElementById('reqForeigner').checked;
        document.getElementById('reqNationalityWrap').classList.toggle('hidden', !on);
        if (!on) document.getElementById('reqNationality').value = '';
    }

    function reqAddDocRow(value) {
        const list = document.getElementById('reqDocsList');
        const row = document.createElement('div');
        row.className = 'flex items-center gap-2';
        row.innerHTML = '<input type="text" name="documents[]" maxlength="150" required'
            + ' class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="e.g. Transcript of Records">'
            + '<button type="button" class="text-slate-400 hover:text-rose-600" onclick="this.parentElement.remove()">'
            + '<i data-lucide="x" class="h-4 w-4"></i></button>';
        row.querySelector('input').value = value || '';
        list.appendChild(row);
        if (window.lucide?.createIcons) window.lucide.createIcons();
    }

    function openAddRequirement() {
        const form = document.getElementById('documentRequirementForm');
        form.action = STORE_URL;
        document.getElementById('reqFormMethod').value = 'POST';
        form.reset();
        document.getElementById('reqDocsList').innerHTML = '';
        reqAddDocRow('');
        reqSyncLevelFields();
        reqToggleForeigner();
        openModal('documentRequirementModal');
    }

    function editRequirement(id) {
        const r = REQUIREMENTS.find((x) => x.id === id);
        if (!r) return;
        const form = document.getElementById('documentRequirementForm');
        form.action = UPDATE_URL.replace('__ID__', id);
        document.getElementById('reqFormMethod').value = 'PUT';
        document.getElementById('reqStudentType').value = r.student_type;
        document.getElementById('reqLevel').value = r.education_node_id || '';
        reqSyncLevelFields(r.program_id ? String(r.program_id) : '', r.year_level ? String(r.year_level) : '');
        document.getElementById('reqForeigner').checked = !!r.for_foreigner;
        document.getElementById('reqNationality').value = r.nationality || '';
        reqToggleForeigner();
        const list = document.getElementById('reqDocsList');
        list.innerHTML = '';
        (r.documents.length ? r.documents : ['']).forEach((d) => reqAddDocRow(d));
        openModal('documentRequirementModal');
    }

    function viewRequirement(id) {
        const r = REQUIREMENTS.find((x) => x.id === id);
        if (!r) return;
        const body = document.getElementById('viewRequirementBody');
        body.innerHTML = r.documents.length
            ? '<ul class="list-disc space-y-1 pl-5">' + r.documents.map((d) => `<li>${reqEsc(d)}</li>`).join('') + '</ul>'
            : '<p class="text-slate-400">No documents listed.</p>';
        openModal('viewRequirementModal');
    }

    function deleteRequirement(id) {
        if (!confirm('Delete this document requirement?')) return;
        const form = document.getElementById('deleteRequirementForm');
        form.action = DELETE_URL.replace('__ID__', id);
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
