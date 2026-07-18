@extends('layouts.app')

@section('content')
@php($bandLabel = $band === 'basic' ? 'Basic Education' : 'Higher Education')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Grading Scheme</h1>
        <p class="text-sm text-slate-500">
            Weighted components, passing mark, and the attendance share for each {{ $bandLabel }} level.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Education-level tabs (basic ed only): stage groups fold the long grade
         list. Renders nothing when fewer than two groups are offered. --}}
    <x-table.level-tabs :route="$indexRoute"
                        :levels="$stageGroups"
                        :activeLevelId="$activeLevelId"
                        :showAll="$showAll"
                        allLabel="All Grade Levels" />

    @if ($levels->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
            @if (! $showAll)
                No grade levels under this group yet.
            @else
                No {{ strtolower($bandLabel) }} levels are set up for this school yet.
            @endif
        </div>
    @else
        <form method="POST" action="{{ route($updateRoute) }}" class="space-y-4">
            @csrf

            @foreach ($levels as $row)
                @php($level = $row['level'])
                @php($s = $row['setting'])
                @php($componentSeed = ($s->exists ? $s->components : collect())->map(fn ($c) => ['name' => $c->name, 'weight' => (float) $c->weight])->values())
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                     x-data="{
                        open: false,
                        attendance: {{ (float) $s->attendance_weight }},
                        components: {{ \Illuminate\Support\Js::from($componentSeed) }},
                        get total() {
                            return (parseFloat(this.attendance) || 0)
                                + this.components.reduce((sum, c) => sum + (parseFloat(c.weight) || 0), 0);
                        },
                        add() { this.components.push({ name: '', weight: 0 }); },
                        remove(i) { this.components.splice(i, 1); }
                     }">
                    {{-- Accordion header — click to expand; sections start collapsed. --}}
                    <button type="button" @click="open = ! open"
                        class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left hover:bg-slate-50">
                        <span class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                                 :style="open ? 'transform: rotate(90deg)' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                            <span class="text-base font-semibold text-slate-800">{{ $level->name }}</span>
                        </span>
                        <span class="flex items-center gap-3">
                            {{-- Total-weight badge stays visible while collapsed. --}}
                            <span class="text-xs font-semibold"
                                  :class="Math.abs(total - 100) < 0.01 ? 'text-emerald-600' : 'text-amber-600'"
                                  x-text="total + '%'"></span>
                            <span class="hidden text-xs uppercase tracking-wider text-slate-400 sm:inline">{{ $bandLabel }}</span>
                        </span>
                    </button>

                    {{-- Body: collapsed by default (display:none avoids a flash before Alpine boots). --}}
                    <div x-show="open" x-collapse style="display: none;">
                        <div class="space-y-4 border-t border-slate-100 px-5 pb-5 pt-4">
                            <div class="grid gap-4 md:grid-cols-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Scale</label>
                                    <select name="settings[{{ $level->id }}][scale_type]"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                        @foreach ($scales as $scale)
                                            <option value="{{ $scale }}" @selected($s->scale_type === $scale)>
                                                {{ ucwords(str_replace('_', ' ', $scale)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Passing mark</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="settings[{{ $level->id }}][passing_mark]"
                                        value="{{ rtrim(rtrim(number_format((float) $s->passing_mark, 2, '.', ''), '0'), '.') }}"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Attendance weight (%)</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="settings[{{ $level->id }}][attendance_weight]"
                                        x-model="attendance"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                </div>
                            </div>

                            {{-- Weighted components --}}
                            <div class="border-t border-slate-100 pt-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-xs font-medium text-slate-600">Weighted components</span>
                                    <button type="button" @click="add()"
                                        class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        + Add component
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <template x-for="(c, i) in components" :key="i">
                                        <div class="flex items-center gap-2">
                                            <input type="text" placeholder="e.g. Written Work"
                                                :name="`settings[{{ $level->id }}][components][${i}][name]`"
                                                x-model="c.name"
                                                class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                            <input type="number" step="0.01" min="0" max="100" placeholder="%"
                                                :name="`settings[{{ $level->id }}][components][${i}][weight]`"
                                                x-model="c.weight"
                                                class="w-24 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                            <button type="button" @click="remove(i)"
                                                class="rounded-lg px-2 py-1 text-sm text-rose-500 hover:bg-rose-50">✕</button>
                                        </div>
                                    </template>
                                    <p x-show="components.length === 0" class="text-xs text-slate-400">
                                        No components yet — add Written Work, Performance Tasks, etc.
                                    </p>
                                </div>

                                {{-- Live total: attendance + components should reach 100. --}}
                                <div class="mt-3 text-sm">
                                    <span class="text-slate-500">Total weight:</span>
                                    <span class="font-semibold"
                                          :class="Math.abs(total - 100) < 0.01 ? 'text-emerald-600' : 'text-amber-600'"
                                          x-text="total + '%'"></span>
                                    <span x-show="Math.abs(total - 100) >= 0.01" class="text-xs text-amber-600">
                                        (should total 100%)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end">
                <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                    {{ $showAll ? 'Save all levels' : 'Save these levels' }}
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
