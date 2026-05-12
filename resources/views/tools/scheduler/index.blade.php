@extends('layouts.app')

@section('content')
<div class="p-4 md:p-6" x-data="schedulerApp()" x-init="init()">

    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800">Academic Scheduler</h1>
            <p class="text-sm text-slate-600 mt-1">Generate optimized timetables, detect conflicts, and apply schedules.</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="GET" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Term</label>
                <select name="term_id" onchange="this.form.submit()" class="rounded border-slate-300 px-2 py-1 text-sm">
                    <option value="">Select term</option>
                    @foreach(($terms ?? []) as $term)
                        <option value="{{ $term['id'] }}" @selected((string) ($termId ?? '') === (string) $term['id'])>
                            {{ $term['name'] }}
                        </option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('tools.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Tools Hub
            </a>
        </div>
    </div>

    @if(!empty($teacherCoverage['has_gaps']))
        <div x-data="{ open: true, modal: false }" x-show="open"
             class="mb-5 rounded-xl border border-rose-300 bg-rose-50 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 mt-0.5"></i>
                    <div>
                        <h3 class="text-sm font-bold text-rose-800">
                            Teacher coverage gap: {{ $teacherCoverage['uncovered_count'] }}
                            of {{ $teacherCoverage['total_active'] }} active subject(s) have NO qualified teacher.
                        </h3>
                        <p class="text-xs text-rose-700 mt-1">
                            These subjects cannot be scheduled. Hire or assign teachers for the items below.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="modal = true"
                            class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">
                        View details
                    </button>
                    <button type="button" @click="open = false" class="text-rose-600 hover:text-rose-900">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            {{-- Quick inline preview (top 5) --}}
            <ul class="mt-3 grid sm:grid-cols-2 gap-1 text-xs text-rose-800">
                @foreach(array_slice($teacherCoverage['missing'], 0, 6) as $m)
                    <li class="font-mono">
                        <span class="font-bold">{{ $m['program'] }}</span>
                        · Y{{ $m['year_level'] }} T{{ $m['semester'] }}
                        · {{ $m['code'] }} <span class="text-rose-600">— {{ $m['name'] }}</span>
                    </li>
                @endforeach
                @if($teacherCoverage['uncovered_count'] > 6)
                    <li class="italic text-rose-700">
                        +{{ $teacherCoverage['uncovered_count'] - 6 }} more — click "View details"
                    </li>
                @endif
            </ul>

            {{-- Modal --}}
            <div x-show="modal" x-transition style="display:none"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                 @keydown.escape.window="modal = false">
                <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full max-h-[80vh] overflow-hidden flex flex-col"
                     @click.outside="modal = false">
                    <div class="flex items-center justify-between p-4 border-b bg-rose-50">
                        <h3 class="font-bold text-rose-800">
                            Subjects without a qualified teacher
                            ({{ $teacherCoverage['uncovered_count'] }})
                        </h3>
                        <button type="button" @click="modal = false" class="text-slate-500 hover:text-slate-800">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <div class="flex-1 overflow-auto p-4">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="text-left p-2 font-semibold text-slate-700">Program</th>
                                    <th class="text-left p-2 font-semibold text-slate-700">Year</th>
                                    <th class="text-left p-2 font-semibold text-slate-700">Term</th>
                                    <th class="text-left p-2 font-semibold text-slate-700">Code</th>
                                    <th class="text-left p-2 font-semibold text-slate-700">Subject</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teacherCoverage['missing'] as $m)
                                    <tr class="border-t">
                                        <td class="p-2 font-mono">{{ $m['program'] }}</td>
                                        <td class="p-2">Y{{ $m['year_level'] }}</td>
                                        <td class="p-2">T{{ $m['semester'] }}</td>
                                        <td class="p-2 font-mono text-rose-700 font-bold">{{ $m['code'] }}</td>
                                        <td class="p-2">{{ $m['name'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 border-t bg-slate-50 text-right">
                        <button type="button" @click="modal = false"
                                class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="mb-5 flex gap-1 border-b border-slate-200">
        <button type="button" @click="tab='create'"
            :class="tab==='create' ? 'border-emerald-500 text-emerald-700' : 'border-transparent text-slate-600'"
            class="px-4 py-2 text-sm font-medium border-b-2">Create</button>
        <button type="button" @click="tab='schedule'"
            :class="tab==='schedule' ? 'border-emerald-500 text-emerald-700' : 'border-transparent text-slate-600'"
            class="px-4 py-2 text-sm font-medium border-b-2">Schedule</button>
    </div>

    {{-- ===================== CREATE TAB ===================== --}}
    <div x-show="tab==='create'" class="space-y-5">

        @include('tools.scheduler.partials.policy')
        @include('tools.scheduler.partials.section_policy')
        @include('tools.scheduler.partials.teacher_constraints')
        @include('tools.scheduler.partials.time')
        @include('tools.scheduler.partials.sections')
        @include('tools.scheduler.partials.weights')

        <div class="flex items-center justify-end gap-2">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" x-model="advanced" class="rounded border-slate-300">
                Optimize (Advanced – Genetic Algorithm)
            </label>
            <button type="button" @click="generate()" :disabled="generating"
                class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                <span x-show="!generating">Generate Schedules</span>
                <span x-show="generating">Generating...</span>
            </button>
        </div>

        {{-- Options --}}
        <div x-show="options.length > 0" class="space-y-3">
            <h2 class="text-lg font-semibold text-slate-800">Schedule Options</h2>
            <template x-for="(opt, idx) in options" :key="idx">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-slate-800">Option <span x-text="idx+1"></span></h3>
                            <p class="text-sm text-slate-600">
                                Score: <span class="font-semibold text-emerald-700" x-text="opt.score"></span> ·
                                Sessions: <span x-text="(opt.sessions||[]).length"></span> ·
                                Conflicts: <span class="text-rose-600" x-text="conflictCount(opt)"></span>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="preview(idx)" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm">Preview</button>
                            <button type="button" @click="resolve(idx)" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-sm text-amber-700">Improve</button>
                            <button type="button" @click="apply(idx)" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm text-white">Apply</button>
                        </div>
                    </div>
                    <div x-show="opt._open" x-cloak class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-2 py-1 text-left">Section</th>
                                    <th class="px-2 py-1 text-left">Subject</th>
                                    <th class="px-2 py-1 text-left">Teacher</th>
                                    <th class="px-2 py-1 text-left">Room</th>
                                    <th class="px-2 py-1 text-left">Day</th>
                                    <th class="px-2 py-1 text-left">Time</th>
                                    <th class="px-2 py-1 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(s, sIdx) in (opt.sessions||[])" :key="sIdx">
                                    <tr :class="{
                                        'bg-rose-50':    s.status==='conflict',
                                        'bg-amber-50':   s.status==='adjusted',
                                        'bg-yellow-100': s.status==='needs_teacher',
                                        'bg-orange-100': s.status==='needs_room',
                                        'bg-red-200':    s.status==='needs_teacher_room',
                                    }">
                                        <td class="px-2 py-1" x-text="s.section_name || s.section_id"></td>
                                        <td class="px-2 py-1" x-text="s.subject_name || s.subject_id"></td>
                                        <td class="px-2 py-1" x-text="s.teacher_name || s.teacher_id || '—'"></td>
                                        <td class="px-2 py-1" x-text="s.room_name || s.room_id || '—'"></td>
                                        <td class="px-2 py-1 capitalize" x-text="s.day_of_week || 'Unscheduled'"></td>
                                        <td class="px-2 py-1" x-text="s.start_time && s.end_time ? (s.start_time + ' – ' + s.end_time) : (s.hours + ' hr')"></td>
                                        <td class="px-2 py-1">
                                            <span :class="{
                                                'text-rose-700':    s.status==='conflict',
                                                'text-amber-700':   s.status==='adjusted',
                                                'text-yellow-800':  s.status==='needs_teacher',
                                                'text-orange-800':  s.status==='needs_room',
                                                'text-red-800 font-semibold': s.status==='needs_teacher_room',
                                                'text-emerald-700': s.status==='valid',
                                            }" x-text="s.status"></span>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="(opt.conflicts||[]).length">
                                    <tr><td colspan="7" class="px-2 pt-3 pb-1 text-[11px] font-semibold text-slate-500 uppercase">Diagnostics</td></tr>
                                </template>
                                <template x-for="(c, cIdx) in (opt.conflicts||[])" :key="'c'+cIdx">
                                    <tr class="bg-slate-50">
                                        <td colspan="7" class="px-2 py-1 text-[11px] text-slate-700">
                                            <span class="inline-block rounded bg-slate-200 px-1.5 py-0.5 mr-2 text-[10px] uppercase" x-text="c.severity || 'note'"></span>
                                            <span x-text="c.reason"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>

    </div>

    {{-- ===================== SCHEDULE TAB ===================== --}}
    <div x-show="tab==='schedule'" x-cloak class="space-y-4">
        @include('tools.scheduler.partials.grid')
    </div>

</div>

@push('scripts')
<script>
window.__SCHEDULER__ = {
    sections: @json($sections ?? []),
    subjects: @json($subjects ?? []),
    teachers: @json($teachers ?? []),
    rooms:    @json($rooms ?? []),
    sectionSubjects: @json($sectionSubjects ?? []),
    activeSchedule: @json($activeSchedule ?? null),
    defaults: @json($defaults ?? []),
    termId: @json($termId ?? null),
    routes: {
        generate: '{{ route('scheduler.generate') }}',
        apply:    '{{ route('scheduler.apply') }}',
        resolve:  '{{ route('scheduler.resolve') }}',
        saveConfig: '{{ route('scheduler.saveConfig') }}',
    },
    csrf: '{{ csrf_token() }}',
};
</script>
<script src="{{ asset('js/scheduler/scheduler.js') }}"></script>
@endpush
@endsection
