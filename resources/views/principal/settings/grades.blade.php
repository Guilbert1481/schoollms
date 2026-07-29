{{-- Principal → Settings → Grades. Tabbed home for grading thresholds/policies.
     First tab: the passing threshold + promotion rule that Form 137 uses. --}}

@extends('layouts.app')

@section('content')
@php
    // Land on the Division tab when its own validation failed, otherwise honour
    // the flashed tab (set after a successful save) or the default.
    $divisionHasError = $errors->has('period_label')
        || collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'period_names'));
    $gradesInitialTab = $divisionHasError ? 'division' : session('grades_tab', 'threshold');
@endphp
<div class="w-full space-y-6" x-data="{ tab: '{{ $gradesInitialTab }}' }">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
            <i data-lucide="graduation-cap" class="w-6 h-6 text-indigo-600"></i>
            Grade Settings
        </h1>
        <p class="text-sm text-slate-500 mt-1">Set the grading thresholds and policies for basic education.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="border-b border-slate-200">
        <nav class="flex gap-1 -mb-px">
            <button type="button" @click="tab = 'threshold'"
                    :class="tab === 'threshold' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="px-4 py-2 text-sm font-semibold border-b-2 transition">
                Threshold
            </button>
            <button type="button" @click="tab = 'division'"
                    :class="tab === 'division' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="px-4 py-2 text-sm font-semibold border-b-2 transition">
                Division
            </button>
            {{-- Future threshold types will get their own tabs here. --}}
        </nav>
    </div>

    {{-- Threshold tab --}}
    <div x-show="tab === 'threshold'" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <form method="POST" action="{{ route('principal.settings.grades.update') }}" class="space-y-6">
            @csrf

            <div>
                <label for="passing_threshold" class="block text-sm font-bold text-slate-700 mb-1">Passing Threshold</label>
                <p class="text-xs text-slate-500 mb-2">
                    The minimum final grade a learner must reach to pass a learning area (DepEd default is 75).
                </p>
                <div class="flex items-center gap-2">
                    <input type="number" step="0.01" min="0" max="100"
                           id="passing_threshold" name="passing_threshold"
                           value="{{ old('passing_threshold', rtrim(rtrim(number_format((float) $settings->passing_threshold, 2, '.', ''), '0'), '.')) }}"
                           class="w-32 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <span class="text-sm text-slate-500">out of 100</span>
                </div>
            </div>

            <div>
                <label for="promotion_rule" class="block text-sm font-bold text-slate-700 mb-1">Promotion Rule</label>
                <p class="text-xs text-slate-500 mb-2">
                    How the Form 137 standing (Promoted / Retained) is decided.
                </p>
                <select id="promotion_rule" name="promotion_rule"
                        class="w-full max-w-md rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="average" @selected(old('promotion_rule', $settings->promotion_rule) === 'average')>
                        Promoted when the General Average meets the threshold
                    </option>
                    <option value="all_areas_pass" @selected(old('promotion_rule', $settings->promotion_rule) === 'all_areas_pass')>
                        Promoted only when the average meets it AND no learning area is failed
                    </option>
                </select>
            </div>

            <div class="pt-2 border-t border-slate-100 flex justify-end">
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Division tab: name the basic-ed grading periods. The period stays an
         ordinal (1..N) everywhere; this only changes how it reads. Capped at the
         grade pipeline's supported maximum. Alpine drives the editable list;
         display:none avoids a flash before Alpine boots. --}}
    <div x-show="tab === 'division'" style="display: none;"
         class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <form method="POST" action="{{ route('principal.settings.grades.division') }}" class="space-y-6"
              x-data="{
                  label: @js(old('period_label', $settings->periodLabel())),
                  periods: @js(array_values(old('period_names', array_values($settings->periods())))),
                  max: {{ \App\Models\GradeSetting::MAX_PERIODS }},
                  ordinal(i) { return ['1st','2nd','3rd','4th','5th','6th'][i] || (i + 1) + 'th'; },
                  add() { if (this.periods.length < this.max) this.periods.push(''); },
                  removeLast() { if (this.periods.length > 1) this.periods.pop(); },
              }">
            @csrf

            <p class="text-xs text-slate-500">
                Name the grading periods for basic education. This changes only how the period reads —
                teachers still grade the same periods in order. Shown wherever a period is picked
                (gradebook, homework, and more).
            </p>

            {{-- The collective noun --}}
            <div>
                <label for="period_label" class="block text-sm font-bold text-slate-700 mb-1">Grading period name</label>
                <p class="text-xs text-slate-500 mb-2">
                    The word your school uses for one grading period — e.g. Quarter, Term, or Grading Period.
                </p>
                <input type="text" id="period_label" name="period_label" x-model="label" maxlength="40"
                       placeholder="Quarter"
                       class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            {{-- The per-period names --}}
            <div>
                <div class="block text-sm font-bold text-slate-700 mb-1">Periods</div>
                <p class="text-xs text-slate-500 mb-3">
                    Up to <span x-text="max"></span> periods, in order — these are the options teachers and students see.
                    Rename any of them freely; add or remove periods from the end. You can't remove a period once grades
                    have been posted to it.
                </p>

                <div class="space-y-2">
                    <template x-for="(name, i) in periods" :key="i">
                        <div class="flex items-center gap-2">
                            <span class="w-8 shrink-0 text-xs font-semibold text-slate-400" x-text="ordinal(i)"></span>
                            <input type="text" name="period_names[]" x-model="periods[i]" maxlength="60"
                                   :placeholder="ordinal(i) + ' ' + (label || 'Quarter')"
                                   class="w-full max-w-sm rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            {{-- Removal is tail-only: dropping a middle period would
                                 renumber the ones after it and shift the labels of
                                 already-posted grades. Only the last row can be removed. --}}
                            <button type="button" @click="removeLast()"
                                    x-show="i === periods.length - 1 && periods.length > 1"
                                    class="shrink-0 rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                    title="Remove the last period" aria-label="Remove the last period">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 5v6m4-6v6"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <button type="button" @click="add()" x-show="periods.length < max"
                        class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-dashed border-slate-300 px-3 py-2 text-sm font-medium text-slate-600 hover:border-indigo-400 hover:text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Add period
                </button>
            </div>

            <div class="pt-2 border-t border-slate-100 flex justify-end">
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
