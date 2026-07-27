@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Gradebook</h1>
        <p class="text-sm text-slate-500">Enter component scores for a class, save a draft, then post the finals.</p>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Class picker --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @if ($classes->isEmpty())
            <p class="text-sm text-slate-500">You have no classes assigned this term.</p>
        @else
            <form method="GET" action="{{ route('teacher.gradebook.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="min-w-[260px]">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Class</label>
                    <select name="class_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select a class…</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}" @selected(($context['class']->id ?? null) === $c->id)>
                                {{ $c->subject->name ?? $c->code }}@if ($c->section) &middot; {{ $c->section->name }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    Open gradebook
                </button>
            </form>
        @endif
    </div>

    @if ($context)
        {{-- Basic ed grades per grading period — let the teacher pick which. The
             period names come from Principal → Settings → Grades → Division. --}}
        @if ($context['track'] === 'basic')
            @php
                $gpSetting = \App\Models\GradeSetting::where('school_id', (int) (auth()->user()?->school_id ?? 0))->first();
                $gpPeriods = $gpSetting ? $gpSetting->periods() : \App\Models\GradeSetting::defaultPeriods();
                $gpCurrentName = $gpPeriods[$context['period']] ?? ('Period '.$context['period']);
            @endphp
            <form method="GET" action="{{ route('teacher.gradebook.index') }}" class="max-w-[240px]">
                <input type="hidden" name="class_id" value="{{ $context['class']->id }}">
                <x-grading_period_basiced name="period" :selected="$context['period']" :auto-submit="true" :setting="$gpSetting" />
                <noscript>
                    <button type="submit" class="mt-2 rounded-lg bg-slate-800 px-3 py-1.5 text-sm font-medium text-white">Go</button>
                </noscript>
            </form>
        @endif

        @if (! $context['has_scheme'])
            @php($owner = $context['track'] === 'basic' ? 'Principal' : 'Dean')
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-6 text-sm text-amber-800">
                No grading scheme is configured for this class's level yet, so grades can't be entered.
                This is set by the <span class="font-semibold">{{ $owner }}</span> under
                <span class="font-semibold">Settings → Grading Scheme</span> — that page is in the
                {{ $owner }} portal, not the teacher portal.
            </div>
        @elseif ($context['roster']->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
                No students are enrolled in this class.
            </div>
        @else
            @php($gbClass = $context['class'])

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">
                            {{ $gbClass->subject->name ?? $gbClass->code }}
                            @if ($gbClass->section) — {{ $gbClass->section->name }} @endif
                            @if ($context['track'] === 'basic') <span class="text-sm font-normal text-slate-400">· {{ $gpCurrentName }}</span> @endif
                        </h2>
                        <p class="text-xs text-slate-400">{{ $context['roster']->count() }} student(s) · click a row for the full record</p>
                    </div>
                    <button type="button" onclick="GbRecord.open()"
                        class="gb-btn-blue inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold">
                        <i data-lucide="plus" class="h-4 w-4"></i> Add Record
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wider text-slate-400">
                                <th class="px-4 py-3">Student</th>
                                @foreach ($context['components'] as $component)
                                    <th class="px-3 py-3 text-center">{{ $component->name }}<br><span class="text-[10px] normal-case text-slate-300">{{ (float) $component->weight }}%</span></th>
                                @endforeach
                                <th class="px-3 py-3 text-center">Computed</th>
                                <th class="px-3 py-3 text-center">Posted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($context['roster'] as $row)
                                <tr class="cursor-pointer border-b border-slate-100 hover:bg-slate-50" onclick="GbLedger.open({{ $row['student_id'] }})">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-800">{{ $row['name'] }}</div>
                                        <div class="text-xs text-slate-400">{{ $row['number'] }}</div>
                                    </td>
                                    @foreach ($context['components'] as $component)
                                        <td class="px-3 py-3 text-center text-slate-700">
                                            @php($v = $row['scores'][$component->id] ?? null)
                                            @if ($v !== null)
                                                {{ rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.') }}
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    {{-- Computed (preview, not yet posted) --}}
                                    <td class="px-3 py-3 text-center">
                                        @php($p = $row['preview'])
                                        @if ($p && $p->final !== null)
                                            <span class="font-semibold {{ $p->passed ? 'text-emerald-600' : 'text-rose-600' }}">{{ $p->final }}</span>
                                            @unless ($p->isComplete)<div class="text-[10px] text-amber-600">partial</div>@endunless
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    {{-- Posted (on the record) --}}
                                    <td class="px-3 py-3 text-center">
                                        @if ($row['posted_final'] !== null)
                                            <span class="font-semibold text-slate-700">{{ rtrim(rtrim(number_format((float) $row['posted_final'], 2, '.', ''), '0'), '.') }}</span>
                                            <div class="text-[10px] uppercase text-slate-400">{{ $row['posted_status'] }}</div>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-4">
                    <p class="text-xs text-slate-400">Grades auto-fill from records (manual + online); only complete grades (every component scored) post to the record.</p>
                    <form method="POST" action="{{ route('teacher.gradebook.post') }}" onsubmit="return confirm('Post finals for all complete grades in this class?');">
                        @csrf
                        <input type="hidden" name="class_id" value="{{ $gbClass->id }}">
                        @if ($context['track'] === 'basic')<input type="hidden" name="period" value="{{ $context['period'] }}">@endif
                        <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Post grades</button>
                    </form>
                </div>
            </div>

            {{-- Manual "Add Record" modal --}}
            <div id="gbRecordModal" class="gbm">
                <div class="gbm__card">
                    <form method="POST" action="{{ route('teacher.gradebook.record') }}">
                        @csrf
                        <input type="hidden" name="class_id" value="{{ $gbClass->id }}">
                        @if ($context['track'] === 'basic')<input type="hidden" name="period" value="{{ $context['period'] }}">@endif

                        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                            <h3 class="text-base font-semibold text-slate-800">Add Record</h3>
                            <button type="button" onclick="GbRecord.close()" class="text-slate-400 hover:text-slate-700" aria-label="Close">✕</button>
                        </div>

                        <div class="space-y-4 px-5 py-4">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Type</label>
                                    <select name="grade_component_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                        @foreach ($context['components'] as $component)
                                            <option value="{{ $component->id }}">{{ $component->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Total items</label>
                                    <input type="number" name="total_items" min="1" step="1" required placeholder="e.g. 20"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Label <span class="text-slate-400">(optional)</span></label>
                                    <input type="text" name="title" maxlength="120" placeholder="e.g. Quiz 1"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                </div>
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label class="text-xs font-medium text-slate-600">Raw scores</label>
                                    <span class="text-[11px] text-slate-400">Leave blank if not taken</span>
                                </div>
                                <div class="divide-y divide-slate-100 rounded-lg border border-slate-200" style="max-height:320px;overflow-y:auto;">
                                    @foreach ($context['roster'] as $row)
                                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                                            <div>
                                                <div class="text-sm text-slate-700">{{ $row['name'] }}</div>
                                                <div class="text-[11px] text-slate-400">{{ $row['number'] }}</div>
                                            </div>
                                            <input type="number" name="scores[{{ $row['student_id'] }}]" min="0" step="0.01"
                                                class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-center text-sm">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-5 py-4">
                            <button type="button" onclick="GbRecord.close()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                            <button type="submit" class="gb-btn-blue rounded-lg px-5 py-2 text-sm font-semibold">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Per-student detailed ledger --}}
            <x-drawer.right-drawer id="gbLedger" :width="580" />

            <style>
                .gbm { position: fixed; inset: 0; z-index: 70; display: none; align-items: flex-start; justify-content: center; background: rgba(15,23,42,.5); padding: 24px; overflow-y: auto; }
                .gbm.gbm--open { display: flex; }
                .gbm__card { width: 100%; max-width: 640px; background: #fff; border-radius: 16px; box-shadow: 0 24px 60px -20px rgba(15,23,42,.55); }
                .gb-btn-blue { background: #0369a1; color: #fff; }
                .gb-btn-blue:hover { background: #0284c7; }
            </style>

            <script>
                (function () {
                    var recEl = document.getElementById('gbRecordModal');
                    window.GbRecord = {
                        open: function () { if (recEl) { recEl.classList.add('gbm--open'); document.body.style.overflow = 'hidden'; if (window.lucide) window.lucide.createIcons(); } },
                        close: function () { if (recEl) { recEl.classList.remove('gbm--open'); if (!document.querySelector('.rd.rd--open')) document.body.style.overflow = ''; } }
                    };

                    var ledgerBase = @json(route('teacher.gradebook.ledger'));
                    var ledgerClassId = @json($context['class']->id);
                    var ledgerPeriod = @json($context['track'] === 'basic' ? $context['period'] : null);

                    window.GbLedger = {
                        open: function (studentId, periodOverride) {
                            var period = (periodOverride !== undefined && periodOverride !== null && periodOverride !== '') ? periodOverride : ledgerPeriod;
                            var url = ledgerBase + '?class_id=' + ledgerClassId + '&student_id=' + studentId + (period ? '&period=' + period : '');
                            if (window.RightDrawer) window.RightDrawer.load('gbLedger', url);
                        }
                    };

                    // Close the record modal on backdrop click / Escape.
                    if (recEl) {
                        recEl.addEventListener('click', function (e) { if (e.target === recEl) window.GbRecord.close(); });
                        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') window.GbRecord.close(); });
                    }
                })();
            </script>
        @endif
    @endif
</div>
@endsection
