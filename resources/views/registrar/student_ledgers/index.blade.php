@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif
    @if(session('import_errors'))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <div class="font-bold">Rows needing attention</div>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach(session('import_errors') as $importError)
                    <li>{{ $importError }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <h1 class="text-xl font-extrabold text-slate-800">Student Ledgers</h1>

    {{-- Education-level tabs (hidden when only one level is offered) --}}
    @if ($showTabs)
        @php
            $ledgerTabs = [[
                'label'  => 'All Levels',
                'url'    => route('registrar.student-ledgers.index', ['level' => 'all']),
                'active' => $showAll,
            ]];
            foreach ($levels as $lvl) {
                $ledgerTabs[] = [
                    'label'  => $lvl->name,
                    'url'    => route('registrar.student-ledgers.index', ['level' => $lvl->id]),
                    'active' => ! $showAll && $activeLevelId === $lvl->id,
                ];
            }
        @endphp
        <x-tabs.count-tabs :tabs="$ledgerTabs" />
    @endif

    {{-- Master list --}}
    <x-table.table
        tableKey="student_ledgers"
        :columns="$columns"
        :data="$rows->values()"
        :actions="$actions"
        perPage="20"
        :emptyMessage="$tableEmptyMessage"
    >
        <x-slot:afterFilter>
            {{-- Always shown so the registrar can see the available filters,
                 even before any data exists (just "All ..." when empty). --}}
            <select onchange="ledgerApplyFilter('status', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="all" @selected($statusFilter === 'all')>All Statuses</option>
                @foreach($statusOptions as $sKey => $sLabel)
                    <option value="{{ $sKey }}" @selected($statusFilter === $sKey)>{{ $sLabel }}</option>
                @endforeach
            </select>

            <select onchange="ledgerApplyFilter('academic_year_id', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All Academic Years</option>
                @foreach($academicYears as $ayId => $ayName)
                    <option value="{{ $ayId }}" @selected((string) $academicYearId === (string) $ayId)>{{ $ayName }}</option>
                @endforeach
            </select>

            <select onchange="ledgerApplyFilter('year_level', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All {{ $activeLevelIsBasic ? 'Grade Levels' : 'Year Levels' }}</option>
                @foreach($yearLevelOptions as $ylValue => $ylLabel)
                    <option value="{{ $ylValue }}" @selected((string) $yearLevel === (string) $ylValue)>{{ $ylLabel }}</option>
                @endforeach
            </select>

            @if($activeLevelIsBasic)
                {{-- Section filter (Basic Education) — "All Sections" when none set. --}}
                <select onchange="ledgerApplyFilter('section_id', this.value)"
                        class="rounded border border-gray-300 px-2 py-2 text-sm">
                    <option value="">All Sections</option>
                    @foreach($sectionOptions as $secId => $secName)
                        <option value="{{ $secId }}" @selected((int) $sectionId === (int) $secId)>{{ $secName }}</option>
                    @endforeach
                </select>
            @endif

            @if($showProgramFilter)
                <select onchange="ledgerApplyFilter('program_id', this.value)"
                        class="rounded border border-gray-300 px-2 py-2 text-sm">
                    <option value="">All Programs</option>
                    @foreach($programOptions as $pid => $pname)
                        <option value="{{ $pid }}" @selected((int) $programId === (int) $pid)>{{ $pname }}</option>
                    @endforeach
                </select>
            @endif
        </x-slot:afterFilter>

        {{-- Export (dropdown: CSV / Excel / both) + Import, side by side. --}}
        <div class="flex items-center gap-2">
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = ! open"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="download" class="h-4 w-4 text-indigo-600"></i>
                    Export
                    <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-slate-400"></i>
                </button>
                <div x-show="open" x-cloak x-transition
                     class="absolute right-0 z-30 mt-1 w-48 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                     style="display:none;">
                    <button type="button" onclick="ledgerExport('csv'); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="file-text" class="h-4 w-4 text-slate-500"></i> Export as CSV
                    </button>
                    <button type="button" onclick="ledgerExport('xlsx'); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="file-spreadsheet" class="h-4 w-4 text-emerald-600"></i> Export as Excel
                    </button>
                    <button type="button" onclick="ledgerExport('both'); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="files" class="h-4 w-4 text-indigo-600"></i> Export both
                    </button>
                </div>
            </div>

            <button type="button" onclick="openStudentImportModal()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i data-lucide="upload" class="h-4 w-4 text-emerald-600"></i>
                Import
            </button>
        </div>
    </x-table.table>
</div>

<div id="studentImportModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     onclick="if(event.target === this) closeStudentImportModal()">
    <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-4">
            <div>
                <h2 class="text-lg font-black text-slate-900">Import Enrolled Students</h2>
                <p class="text-xs text-slate-500">CSV only. Required headers: first_name, last_name.</p>
            </div>
            <button type="button" onclick="closeStudentImportModal()" class="rounded p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-700">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <form method="POST"
              action="{{ route('registrar.student-ledgers.import') }}"
              enctype="multipart/form-data"
              class="space-y-4 px-5 py-5">
            @csrf

            <div>
                <label for="import_term_id" class="mb-1 block text-sm font-semibold text-slate-700">Term</label>
                <select id="import_term_id"
                        name="term_id"
                        required
                        onchange="toggleImportOthers()"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    @foreach($importTerms as $term)
                        <option value="{{ $term->id }}" @selected($term->is_current)>
                            {{ $term->academic_year_name ? $term->academic_year_name.' - ' : '' }}{{ $term->name }}
                        </option>
                    @endforeach
                    <option value="others">Others — specify academic year{{ $showImportTermNumber ? ' & term' : '' }}…</option>
                </select>
            </div>

            {{-- Shown only when "Others" is selected: bypasses the term list. --}}
            <div id="importOthersFields" class="hidden space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div>
                    <label for="import_academic_year" class="mb-1 block text-sm font-semibold text-slate-700">Academic Year</label>
                    <input type="text"
                           id="import_academic_year"
                           name="academic_year"
                           list="import_academic_year_options"
                           placeholder="e.g. 2018 - 2019"
                           autocomplete="off"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    <datalist id="import_academic_year_options">
                        @foreach($importAcademicYears as $ay)
                            <option value="{{ $ay->name }}"></option>
                        @endforeach
                    </datalist>
                    <p class="mt-1 text-xs text-slate-500">Type any year — current or historical. A new academic year is created automatically if it doesn't exist yet.</p>
                </div>

                @if($showImportTermNumber)
                    <div>
                        <label for="import_term_number" class="mb-1 block text-sm font-semibold text-slate-700">Term</label>
                        <x-forms.term-select id="import_term_number" name="term_number" placeholder="Select term" />
                    </div>
                @endif
            </div>

            <div>
                <label for="student_import_file" class="mb-1 block text-sm font-semibold text-slate-700">CSV File</label>
                <input id="student_import_file"
                       type="file"
                       name="file"
                       accept=".csv,text/csv,text/plain"
                       required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100">
                <p class="mt-2 text-xs text-slate-500">
                    Optional headers: student_number, lrn, email, middle_name, phone, program_code, year_level, gender, date_of_birth, address.
                </p>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button"
                        onclick="closeStudentImportModal()"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                    <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                    Import
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Change Status modal --}}
<div id="changeStatusModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     onclick="if(event.target === this) closeChangeStatusModal()">
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-4">
            <div>
                <h2 class="text-lg font-black text-slate-900">Change Student Status</h2>
                <p class="text-xs text-slate-500" id="changeStatusName">—</p>
            </div>
            <button type="button" onclick="closeChangeStatusModal()" class="rounded p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-700">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <form id="changeStatusForm" method="POST" class="space-y-4 px-5 py-5">
            @csrf
            @method('PATCH')
            <div>
                <label for="changeStatusSelect" class="mb-1 block text-sm font-semibold text-slate-700">Status</label>
                <select id="changeStatusSelect" name="status" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    @foreach($statusOptions as $sKey => $sLabel)
                        <option value="{{ $sKey }}">{{ $sLabel }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">
                    Moves the student to the selected lifecycle status (e.g. Graduated, On Leave, Dropped).
                    Choose “Enrolled” to restore an active student.
                </p>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" onclick="closeChangeStatusModal()"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Student lookup for the Change Status modal: { id: { name, status } }.
    const LEDGER_STUDENTS = @json($ledgerStudents);
    const LEDGER_STATUS_BASE = @json(url('/registrar/student-ledgers'));

    function openChangeStatusModal(id) {
        const meta = LEDGER_STUDENTS[id] || {};
        const form = document.getElementById('changeStatusForm');
        form.action = LEDGER_STATUS_BASE + '/' + id + '/status';
        document.getElementById('changeStatusName').textContent = meta.name || '';
        const sel = document.getElementById('changeStatusSelect');
        if (meta.status) sel.value = meta.status;

        const modal = document.getElementById('changeStatusModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        if (window.lucide?.createIcons) window.lucide.createIcons();
    }

    function closeChangeStatusModal() {
        const modal = document.getElementById('changeStatusModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    // Export the current (filtered) list. Builds the export URL from the active
    // query params so the file matches what's on screen. "both" saves two files.
    const LEDGER_EXPORT_URL = @json(route('registrar.student-ledgers.export'));
    function ledgerExport(format) {
        const current = new URLSearchParams(window.location.search);
        const buildUrl = (fmt) => {
            const p = new URLSearchParams(current);
            p.set('format', fmt);
            return LEDGER_EXPORT_URL + '?' + p.toString();
        };
        const download = (url) => {
            const a = document.createElement('a');
            a.href = url;
            document.body.appendChild(a);
            a.click();
            a.remove();
        };
        if (format === 'both') {
            download(buildUrl('csv'));
            setTimeout(() => download(buildUrl('xlsx')), 500);
        } else {
            download(buildUrl(format));
        }
    }

    // Navigate with an updated query param, preserving the others (level, etc.).
    function ledgerApplyFilter(key, value) {
        const url = new URL(window.location.href);
        if (value === '' || value === null) {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, value);
        }
        window.location = url.toString();
    }

    // Toggle the "Others" fields (academic year / term) when the term
    // dropdown is set to "Others"; bypasses the normal term selection.
    function toggleImportOthers() {
        const select = document.getElementById('import_term_id');
        const others = document.getElementById('importOthersFields');
        const ay = document.getElementById('import_academic_year');
        if (!select || !others) return;

        const isOthers = select.value === 'others';
        others.classList.toggle('hidden', !isOthers);
        if (ay) ay.required = isOthers;
    }

    function openStudentImportModal() {
        const modal = document.getElementById('studentImportModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        toggleImportOthers();
        if (window.lucide?.createIcons) window.lucide.createIcons();
    }

    function closeStudentImportModal() {
        const modal = document.getElementById('studentImportModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeStudentImportModal();
            closeChangeStatusModal();
        }
    });
</script>
@endsection
