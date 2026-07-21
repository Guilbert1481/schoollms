{{-- Principal → Settings → Grades. Tabbed home for grading thresholds/policies.
     First tab: the passing threshold + promotion rule that Form 137 uses. --}}

@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6" x-data="{ tab: '{{ session('grades_tab', 'threshold') }}' }">

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
            <button type="button" @click="tab = 'student_grade'"
                    :class="tab === 'student_grade' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="px-4 py-2 text-sm font-semibold border-b-2 transition">
                Student Grade
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

    {{-- Student Grade tab: which grade views basic-ed students can open.
         Each switch hides the sidebar item AND blocks the page (route middleware).
         Both default OFF. display:none avoids a flash before Alpine boots. --}}
    <div x-show="tab === 'student_grade'" style="display: none;"
         class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <form method="POST" action="{{ route('principal.settings.grades.student-visibility') }}" class="space-y-6">
            @csrf

            <p class="text-xs text-slate-500">
                Choose which grade views basic-education students can see. When a switch is off, the
                item is hidden from the student's sidebar and the page is blocked. Both are off by default.
            </p>

            <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <div class="text-sm font-bold text-slate-700">Grades</div>
                    <p class="text-xs text-slate-500">Student sidebar → Academics → “Grades” (report card).</p>
                </div>
                <label class="grade-switch shrink-0">
                    <input type="checkbox" name="show_student_grades" value="1"
                           @checked(old('show_student_grades', $settings->show_student_grades))>
                    <span class="grade-slider"></span>
                </label>
            </div>

            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="text-sm font-bold text-slate-700">Form 137</div>
                    <p class="text-xs text-slate-500">Student sidebar → Academics → “Form 137” (transcript / permanent record).</p>
                </div>
                <label class="grade-switch shrink-0">
                    <input type="checkbox" name="show_student_form137" value="1"
                           @checked(old('show_student_form137', $settings->show_student_form137))>
                    <span class="grade-slider"></span>
                </label>
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

{{-- Scoped toggle-switch styling — inline so it renders regardless of the
     Tailwind build state. --}}
<style>
    .grade-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
    .grade-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
    .grade-switch .grade-slider { position: absolute; inset: 0; cursor: pointer;
        background-color: #cbd5e1; border-radius: 9999px; transition: background-color .2s; }
    .grade-switch .grade-slider::before { content: ""; position: absolute; height: 18px; width: 18px;
        left: 3px; top: 3px; background-color: #fff; border-radius: 50%; transition: transform .2s;
        box-shadow: 0 1px 2px rgba(0,0,0,.2); }
    .grade-switch input:checked + .grade-slider { background-color: #4f46e5; }
    .grade-switch input:checked + .grade-slider::before { transform: translateX(20px); }
    .grade-switch input:focus-visible + .grade-slider { outline: 2px solid #6366f1; outline-offset: 2px; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
