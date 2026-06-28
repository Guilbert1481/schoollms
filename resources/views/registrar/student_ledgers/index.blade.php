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
    <div class="rounded-2xl bg-gradient-to-br from-emerald-600 via-teal-700 to-cyan-800 p-6 md:p-8 text-white shadow-lg">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <i data-lucide="id-card" class="w-8 h-8"></i>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold">Student Ledgers</h1>
                    <p class="text-sm md:text-base text-emerald-100 mt-1">
                        Detailed records of officially enrolled students.
                    </p>
                </div>
            </div>
            <div class="text-sm text-emerald-100">
                <span class="font-bold text-white">{{ $total }}</span>
                enrolled student{{ $total === 1 ? '' : 's' }}
            </div>
        </div>
    </div>

    {{-- Education-level tabs (hidden when only one level is offered) --}}
    @if ($showTabs)
        <div class="flex flex-wrap gap-2 border-b border-slate-200">
            @foreach ($levels as $lvl)
                @php $count = $counts[$lvl->id] ?? 0; @endphp
                <a href="{{ route('registrar.student-ledgers.index', ['level' => $lvl->id]) }}"
                   class="px-4 py-2 text-sm font-semibold rounded-t-lg border-b-2 -mb-px
                          {{ ! $showAll && $activeLevelId === $lvl->id
                              ? 'border-emerald-600 text-emerald-700 bg-emerald-50'
                              : 'border-transparent text-slate-600 hover:text-emerald-700 hover:bg-slate-50' }}">
                    {{ $lvl->name }}
                    <span class="ml-1 inline-flex items-center justify-center min-w-[1.5rem] h-5 px-1.5 rounded-full text-[10px] font-bold
                                 {{ ! $showAll && $activeLevelId === $lvl->id ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600' }}">
                        {{ $count }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Master list --}}
    <x-table.table
        tableKey="student_ledgers"
        :columns="$columns"
        :data="$rows->values()"
        :hideActions="true"
        perPage="20"
    >
        <x-slot:afterFilter>
            @if(!empty($academicYears))
                <select onchange="ledgerApplyFilter('academic_year_id', this.value)"
                        class="rounded border border-gray-300 px-2 py-2 text-sm">
                    <option value="">All Academic Years</option>
                    @foreach($academicYears as $ayId => $ayName)
                        <option value="{{ $ayId }}" @selected((string) $academicYearId === (string) $ayId)>{{ $ayName }}</option>
                    @endforeach
                </select>
            @endif

            @if(!empty($yearLevelOptions))
                <select onchange="ledgerApplyFilter('year_level', this.value)"
                        class="rounded border border-gray-300 px-2 py-2 text-sm">
                    <option value="">All {{ $activeLevelIsBasic ? 'Grade Levels' : 'Year Levels' }}</option>
                    @foreach($yearLevelOptions as $ylValue => $ylLabel)
                        <option value="{{ $ylValue }}" @selected((string) $yearLevel === (string) $ylValue)>{{ $ylLabel }}</option>
                    @endforeach
                </select>
            @endif
        </x-slot:afterFilter>

        @if($showTabs)
            <a href="{{ route('registrar.student-ledgers.index', ['level' => 'all']) }}"
               class="px-3 py-2 border rounded text-sm whitespace-nowrap transition
                      {{ ($showAll ?? false)
                          ? 'bg-emerald-600 text-white border-emerald-600'
                          : 'bg-white text-slate-700 border-gray-300 hover:bg-slate-50' }}">
                All
            </a>
        @endif

        {{-- Always enabled: "Others" lets the registrar type any academic year. --}}
        @php $importDisabled = false; @endphp
        <button type="button"
                onclick="openStudentImportModal()"
                @disabled($importDisabled)
                class="inline-flex items-center gap-2 rounded border px-3 py-2 text-sm font-semibold transition
                       {{ $importDisabled
                            ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400'
                            : 'border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-700' }}">
            <i data-lucide="upload" class="h-4 w-4"></i>
            Import
        </button>
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
                    Optional headers: student_number, email, middle_name, phone, program_code, year_level, gender, date_of_birth, address.
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

<script>
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
        if (event.key === 'Escape') closeStudentImportModal();
    });
</script>
@endsection
