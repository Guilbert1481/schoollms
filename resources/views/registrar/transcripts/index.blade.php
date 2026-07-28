@extends('layouts.app')

@push('styles')
<style>
    /* Registrar grade-view visibility toggles — scoped + build-independent. */
    .gv-switch { position: relative; display: inline-block; width: 40px; height: 22px; vertical-align: middle; }
    .gv-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
    .gv-switch .gv-slider { position: absolute; inset: 0; cursor: pointer; background: #cbd5e1;
        border-radius: 9999px; transition: background-color .2s; }
    .gv-switch .gv-slider::before { content: ""; position: absolute; height: 16px; width: 16px; left: 3px; top: 3px;
        background: #fff; border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 2px rgba(0,0,0,.2); }
    .gv-switch input:checked + .gv-slider { background: #4f46e5; }
    .gv-switch input:checked + .gv-slider::before { transform: translateX(18px); }
    .gv-switch input:focus-visible + .gv-slider { outline: 2px solid #6366f1; outline-offset: 2px; }
</style>
@endpush

@section('content')
<div class="w-full space-y-6">

    {{-- Header --}}
    <h1 class="text-xl font-extrabold text-slate-800">Transcript of Records</h1>

    {{-- Education-level tabs (hidden when the school only offers one level) --}}
    @if ($showTabs)
        @php
            $transcriptTabs = [[
                'label'  => 'All Levels',
                'url'    => route('registrar.transcripts.index', ['level' => 'all']),
                'active' => $showAll,
            ]];
            foreach ($levels as $lvl) {
                $transcriptTabs[] = [
                    'label'  => $lvl->name,
                    'url'    => route('registrar.transcripts.index', ['level' => $lvl->id]),
                    'active' => ! $showAll && $activeLevelId === $lvl->id,
                ];
            }
        @endphp
        <x-tabs.count-tabs :tabs="$transcriptTabs" />
    @endif

    {{-- Master list --}}
    <x-table.table
        tableKey="transcript_master"
        :columns="$columns"
        :data="$rows->values()"
        :actions="config('tables.table-actions.transcript_master', [])"
    >
        <x-slot:afterFilter>
            <select onchange="transcriptApplyFilter('status', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="all" @selected($statusFilter === 'all')>All Statuses</option>
                @foreach($statusOptions as $sKey => $sLabel)
                    <option value="{{ $sKey }}" @selected($statusFilter === $sKey)>{{ $sLabel }}</option>
                @endforeach
            </select>

            <select onchange="transcriptApplyFilter('academic_year_id', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All Academic Years</option>
                @foreach($academicYears as $ayId => $ayName)
                    <option value="{{ $ayId }}" @selected((string) $academicYearId === (string) $ayId)>{{ $ayName }}</option>
                @endforeach
            </select>

            <select onchange="transcriptApplyFilter('year_level', this.value)"
                    class="rounded border border-gray-300 px-2 py-2 text-sm">
                <option value="">All Year Levels</option>
                @foreach($yearLevelOptions as $ylValue => $ylLabel)
                    <option value="{{ $ylValue }}" @selected((string) $yearLevel === (string) $ylValue)>{{ $ylLabel }}</option>
                @endforeach
            </select>

            {{-- Program filter — shown only for non-basic-ed levels. --}}
            @if(! empty($showProgramFilter))
                <select onchange="transcriptApplyFilter('program', this.value)"
                        class="rounded border border-gray-300 px-2 py-2 text-sm">
                    <option value="">All Programs</option>
                    @foreach($programOptions as $pId => $pName)
                        <option value="{{ $pId }}" @selected((string) $programFilter === (string) $pId)>{{ $pName }}</option>
                    @endforeach
                </select>
            @endif

            {{-- Section filter — shown only for basic-ed levels. --}}
            @if(! empty($showSectionFilter))
                <select onchange="transcriptApplyFilter('section', this.value)"
                        class="rounded border border-gray-300 px-2 py-2 text-sm">
                    <option value="">All Sections</option>
                    @foreach($sectionOptions as $secId => $secName)
                        <option value="{{ $secId }}" @selected((string) $sectionFilter === (string) $secId)>{{ $secName }}</option>
                    @endforeach
                </select>
            @endif
        </x-slot:afterFilter>

        {{-- Bulk visibility + Export + Import, side by side. --}}
        <div class="flex items-center gap-2">
            {{-- Bulk visibility — apply Grades / Form 137 on/off to every student
                 the current filters show (master action when no filters set). --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = ! open"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="list-checks" class="h-4 w-4 text-indigo-600"></i>
                    Bulk visibility
                    <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-slate-400"></i>
                </button>
                <div x-show="open" x-cloak x-transition
                     class="absolute right-0 z-30 mt-1 w-64 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                     style="display:none;">
                    <div class="px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        Applies to {{ $rows->count() }} shown student(s)
                    </div>
                    <button type="button" onclick="gvBulk('grades', 1); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="eye" class="h-4 w-4 text-emerald-600"></i> Grades — show all
                    </button>
                    <button type="button" onclick="gvBulk('grades', 0); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="eye-off" class="h-4 w-4 text-slate-400"></i> Grades — hide all
                    </button>
                    <div class="my-1 border-t border-slate-100"></div>
                    <button type="button" onclick="gvBulk('form137', 1); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="eye" class="h-4 w-4 text-emerald-600"></i> Form 137 — show all
                    </button>
                    <button type="button" onclick="gvBulk('form137', 0); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="eye-off" class="h-4 w-4 text-slate-400"></i> Form 137 — hide all
                    </button>
                </div>
            </div>

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
                    <button type="button" onclick="transcriptExport('csv'); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="file-text" class="h-4 w-4 text-slate-500"></i> Export as CSV
                    </button>
                    <button type="button" onclick="transcriptExport('xlsx'); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="file-spreadsheet" class="h-4 w-4 text-emerald-600"></i> Export as Excel
                    </button>
                    <button type="button" onclick="transcriptExport('both'); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="files" class="h-4 w-4 text-indigo-600"></i> Export both
                    </button>
                </div>
            </div>

            <button type="button" onclick="openModal('transcriptImportModal')"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i data-lucide="upload" class="h-4 w-4 text-emerald-600"></i>
                Import
            </button>
        </div>
    </x-table.table>
</div>

{{-- Import: transcripts are generated from enrollments, so there's nothing to
     import here — point the registrar to the Student Registry importer. --}}
<x-modal.form id="transcriptImportModal" title="Import" widthClass="w-full max-w-lg" :hideFooter="true">
    <div class="space-y-3 text-sm text-slate-600">
        <p>Transcript records are generated automatically from each student's enrollments and grades — there's nothing to import on this page.</p>
        <p>To add students, use
            <a href="{{ route('registrar.student-registry.index') }}" class="font-semibold text-indigo-600 hover:underline">Student Registry → Import</a>.
        </p>
    </div>
</x-modal.form>

<script>
    const TRANSCRIPT_EXPORT_URL = @json(route('registrar.transcripts.export'));
    const GV_URL = @json(route('registrar.transcripts.grade-visibility', ['student' => '__ID__']));
    const GV_BULK_URL = @json(route('registrar.transcripts.grade-visibility.bulk'));
    const GV_SHOWN_COUNT = @json($rows->count());

    // Bulk grade-view visibility — applies to every student the current filters
    // show. Posts the same filter params so the server targets exactly the
    // visible rows; confirms first, reloads to reflect the change.
    function gvBulk(view, enabled) {
        const label = view === 'grades' ? 'Grades' : 'Form 137';
        if (!GV_SHOWN_COUNT) { alert('No students are shown for the current filters.'); return; }
        if (!confirm('Turn ' + label + ' ' + (enabled ? 'ON' : 'OFF') + ' for all ' + GV_SHOWN_COUNT + ' student(s) currently shown?')) return;
        const params = new URLSearchParams(window.location.search);
        params.set('view', view);
        params.set('enabled', enabled ? 1 : 0);
        fetch(GV_BULK_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: params.toString(),
        }).then((r) => {
            if (!r.ok) throw new Error('request failed');
            return r.json();
        }).then(() => window.location.reload())
          .catch(() => alert('Could not apply the bulk change. Please try again.'));
    }

    // Per-student grade-view visibility toggle. Saves immediately; reverts the
    // switch if the request fails so the UI never lies about the stored state.
    function gvToggle(studentId, view, checkbox) {
        const enabled = checkbox.checked;
        fetch(GV_URL.replace('__ID__', studentId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ view: view, enabled: enabled ? 1 : 0 }),
        }).then((r) => {
            if (!r.ok) throw new Error('request failed');
            return r.json();
        }).catch(() => {
            checkbox.checked = !enabled;
            alert('Could not update visibility. Please try again.');
        });
    }

    function transcriptApplyFilter(key, value) {
        const url = new URL(window.location.href);
        if (value === '' || value === null) {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, value);
        }
        window.location = url.toString();
    }

    function transcriptExport(format) {
        const current = new URLSearchParams(window.location.search);
        const buildUrl = (fmt) => {
            const p = new URLSearchParams(current);
            p.set('format', fmt);
            return TRANSCRIPT_EXPORT_URL + '?' + p.toString();
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

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
