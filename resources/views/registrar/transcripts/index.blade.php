@extends('layouts.app')

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
