@extends('layouts.app')

@section('content')
@php
    // Map status -> Tailwind classes for the pill.
    $statusPill = function (string $status) {
        return match ($status) {
            'enrolled', 'completed', 'provisionally_enrolled'
                => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
            'rejected', 'exam_failed', 'cancelled', 'dropped'
                => 'bg-red-100 text-red-700 border border-red-200',
            'draft'
                => 'bg-slate-100 text-slate-700 border border-slate-200',
            'submitted'
                => 'bg-blue-100 text-blue-700 border border-blue-200',
            'assessed', 'exam_passed', 'provisional'
                => 'bg-indigo-100 text-indigo-700 border border-indigo-200',
            'sent_billing', 'billed', 'partially_paid'
                => 'bg-amber-100 text-amber-700 border border-amber-200',
            default
                => 'bg-slate-100 text-slate-600 border border-slate-200',
        };
    };
@endphp

<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900">My Applications</h1>
            <p class="text-sm text-slate-500">
                A permanent record of every enrolment application you've submitted.
            </p>
        </div>
        <div class="text-sm text-slate-500">
            <span class="font-bold">{{ $rows->count() }}</span>
            application{{ $rows->count() === 1 ? '' : 's' }}
        </div>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Reference</th>
                        <th class="px-4 py-3 text-left font-semibold">Academic Year &amp; Term</th>
                        <th class="px-4 py-3 text-left font-semibold">Program</th>
                        <th class="px-4 py-3 text-center font-semibold">Year Level</th>
                        <th class="px-4 py-3 text-center font-semibold">Type</th>
                        <th class="px-4 py-3 text-center font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Submitted</th>
                        <th class="px-4 py-3 text-center font-semibold w-40">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $r->reference }}</td>
                            <td class="px-4 py-3 text-slate-800">
                                <div class="font-semibold">{{ $r->academic_year }}</div>
                                <div class="text-xs text-slate-500">{{ $r->term_label }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-800">{{ $r->program_label }}</td>
                            <td class="px-4 py-3 text-center text-slate-700">{{ $r->year_level }}</td>
                            <td class="px-4 py-3 text-center text-xs uppercase tracking-wide text-slate-600">
                                {{ str_replace('_', ' ', (string) $r->student_type) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold {{ $statusPill($r->status) }}">
                                    {{ $r->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-700 text-xs">
                                {{ optional($r->submitted_at)->format('M d, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('student.applications.edit', $r->id) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1 rounded text-xs font-semibold bg-blue-500 text-white hover:bg-blue-600">
                                        <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                                    </a>

                                    <a href="{{ route('student.applications.view', $r->id) }}"
                                       target="_blank"
                                       rel="noopener"
                                       onclick="openApplicationPdf(event, '{{ route('student.applications.view', $r->id) }}', '{{ $r->reference }}')"
                                       class="inline-flex items-center gap-1 px-3 py-1 rounded text-xs font-semibold bg-emerald-500 text-white hover:bg-emerald-600">
                                        <i data-lucide="file-text" class="w-3 h-3"></i> View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-400 italic text-sm">
                                You haven't submitted any enrolment applications yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- PDF Preview Modal --}}
<div id="applicationPdfModal"
     class="hidden fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4"
     style="height: 100vh; width: 100vw;"
     onclick="if(event.target === this) closeApplicationPdf()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
         style="height: 95vh;">
        <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <div class="flex items-center gap-2">
                <i data-lucide="file-text" class="w-5 h-5 text-emerald-600"></i>
                <h3 class="text-base font-bold text-slate-900">
                    Enrolment Form
                    <span id="applicationPdfRef" class="font-mono text-xs text-slate-500 ml-2"></span>
                </h3>
            </div>
            <div class="flex items-center gap-2">
                <a id="applicationPdfDownload"
                   href="#"
                   target="_blank"
                   rel="noopener"
                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded text-xs font-semibold bg-emerald-500 text-white hover:bg-emerald-600">
                    <i data-lucide="external-link" class="w-3 h-3"></i> Open in new tab
                </a>
                <button type="button"
                        onclick="closeApplicationPdf()"
                        class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        <div class="flex-1 bg-slate-100">
            <iframe id="applicationPdfFrame"
                    src=""
                    class="w-full h-full border-0"
                    title="Application PDF"></iframe>
        </div>
    </div>
</div>

<script>
    function openApplicationPdf(event, url, reference) {
        if (event) event.preventDefault();
        document.getElementById('applicationPdfFrame').src = url;
        document.getElementById('applicationPdfDownload').href = url;
        document.getElementById('applicationPdfRef').textContent = reference || '';
        document.getElementById('applicationPdfModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        if (window.lucide?.createIcons) window.lucide.createIcons();
    }
    function closeApplicationPdf() {
        document.getElementById('applicationPdfModal').classList.add('hidden');
        document.getElementById('applicationPdfFrame').src = '';
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeApplicationPdf();
    });
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) {
            window.lucide.createIcons();
        }
    });
</script>
@endsection
