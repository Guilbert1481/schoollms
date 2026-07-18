@extends('layouts.app')

@section('content')
@php $activeTab = $activeTab ?? 'enrollment'; @endphp
<div class="p-6">

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-2 text-sm">
            <div class="font-bold mb-1">Please fix the following:</div>
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="mb-6 border-b border-slate-200">
        <nav class="flex gap-1 -mb-px">
            <a href="{{ route('admission.enrollment-settings.index') }}"
               class="px-4 py-2 text-sm font-bold border-b-2 transition
                      {{ $activeTab === 'enrollment'
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                Enrollment
            </a>
            <a href="{{ route('admission.enrollment-settings.admission-exam') }}"
               class="px-4 py-2 text-sm font-bold border-b-2 transition
                      {{ $activeTab === 'admission-exam'
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                Admission Exam
            </a>
        </nav>
    </div>

@if($activeTab === 'enrollment')
    <div class="grid grid-cols-3 gap-6">

        {{-- LEFT TABLE --}}
        <section class="col-span-1 rounded-2xl border bg-white p-6">
            <h2 class="text-sm font-extrabold mb-4">
                Upcoming Session
            </h2>

            <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Start</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($upcomingTerms as $term)
                    <tr class="border-t">
                        <td class="py-2">
                            <button
                                onclick="openSessionFromTerm(
                                    {{ $term->id }},
                                    {{ $term->academic_year_id }},
                                    @js($term->name),
                                    @js($term->start_date),
                                    @js($term->end_date),
                                    @js($term->enrollment_type)
                                )"
                                class="text-indigo-600 font-bold hover:underline">
                                {{ $term->name }}
                            </button>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($term->start_date)->format('Y-m-d') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </section>


        {{-- RIGHT TABLE --}}
        <section class="col-span-2 rounded-2xl border bg-white p-6">
            <h2 class="text-sm font-extrabold mb-4">
                Enrollment Settings
            </h2>

            <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2 pr-4">Session Title</th>
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Start</th>
                        <th class="py-2 pr-4">End</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2 pr-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($settings as $s)
                <tr>
                    <td class="py-3 pr-4 font-semibold">{{ $s->title }}</td>
                    <td class="py-3 pr-4">{{ $s->name }}</td>
                    <td class="py-3 pr-4">{{ $s->start_date }}</td>
                    <td class="py-3 pr-4">{{ $s->end_date }}</td>

                    <td class="py-3 pr-4">
                        @if($s->status === 'Active')
                            <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-800 px-2 py-1 text-xs font-extrabold">
                                Active
                            </span>
                        @elseif($s->status === 'Upcoming')
                            <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 px-2 py-1 text-xs font-extrabold">
                                Upcoming
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-slate-200 text-slate-800 px-2 py-1 text-xs font-extrabold">
                                Closed
                            </span>
                        @endif
                    </td>

                    <td class="py-3 pr-4 align-middle">
                        <div class="flex items-center gap-2">

                            {{-- Edit --}}
                            @php
                                $editPayload = [
                                    'id' => $s->id,
                                    'title' => $s->title,
                                    'start_date' => $s->start_date,
                                    'end_date' => $s->end_date,
                                    'price' => $s->price,
                                    'instructor_title' => $s->instructor_title,
                                    'instructor_name' => $s->instructor_name,
                                    'course_details' => $s->course_details,
                                    'cover_image' => $s->cover_image ? asset('storage/' . $s->cover_image) : null,
                                ];
                            @endphp
                            <button
                                onclick='openEditSessionModal(@json($editPayload))'
                                class="h-8 px-3 rounded-lg text-xs font-extrabold border border-indigo-200 bg-indigo-50 text-indigo-800 flex items-center">
                                Edit
                            </button>

                            {{-- Generate QR --}}
                            @php
                                $qrPayload = [
                                    'title' => $s->title,
                                    'url'   => route('public.apply.qr', $s->term_id),
                                ];
                            @endphp
                            <button type="button"
                                onclick='openQrModal(@json($qrPayload))'
                                class="h-8 px-3 rounded-lg text-xs font-extrabold border border-slate-200 bg-slate-50 text-slate-800 flex items-center gap-1">
                                <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                                QR
                            </button>

                            {{-- Delete --}}
                            <form method="POST"
                                action="{{ route('admission.enrollment-settings.delete', $s->id) }}"
                                class="m-0 p-0 flex items-center">
                                @csrf
                                @method('DELETE')
                                <button
                                    class="h-8 px-3 rounded-lg text-xs font-extrabold border border-red-200 bg-red-50 text-red-800 flex items-center">
                                    Delete
                                </button>
                            </form>

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-slate-500">
                        No enrollment sessions found.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </section>

    </div>
@else
    {{-- ============================================================== --}}
    {{--  ADMISSION EXAM TAB                                              --}}
    {{-- ============================================================== --}}
    @php
        $exam = $examSetting ?? null;
    @endphp
    <form method="POST" action="{{ route('admission.enrollment-settings.admission-exam.update') }}"
          class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        @method('PUT')

        {{-- Who must take the exam --}}
        <section class="lg:col-span-1 rounded-2xl border bg-white p-6">
            <h2 class="text-sm font-extrabold mb-1">Who must take an admission exam?</h2>
            <p class="text-xs text-slate-500 mb-4">
                Choose which applicant types are routed through the entrance / placement exam.
            </p>

            <div class="space-y-3 text-sm">
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="require_for_new_student" value="1"
                        {{ old('require_for_new_student', $exam->require_for_new_student) ? 'checked' : '' }}
                        class="mt-1 h-4 w-4 rounded border-slate-300">
                    <span>
                        <span class="font-bold block">New students</span>
                        <span class="text-xs text-slate-500">General entrance exam.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3">
                    <input type="checkbox" name="require_for_transferee" value="1"
                        {{ old('require_for_transferee', $exam->require_for_transferee) ? 'checked' : '' }}
                        class="mt-1 h-4 w-4 rounded border-slate-300">
                    <span>
                        <span class="font-bold block">Transferees</span>
                        <span class="text-xs text-slate-500">Program-specific qualifying exam.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3">
                    <input type="checkbox" name="require_for_returnee" value="1"
                        {{ old('require_for_returnee', $exam->require_for_returnee) ? 'checked' : '' }}
                        class="mt-1 h-4 w-4 rounded border-slate-300">
                    <span>
                        <span class="font-bold block">Returnees</span>
                        <span class="text-xs text-slate-500">Returning after a leave of absence.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3">
                    <input type="checkbox" name="require_for_shiftee" value="1"
                        {{ old('require_for_shiftee', $exam->require_for_shiftee) ? 'checked' : '' }}
                        class="mt-1 h-4 w-4 rounded border-slate-300">
                    <span>
                        <span class="font-bold block">Shiftees</span>
                        <span class="text-xs text-slate-500">Switching to another program.</span>
                    </span>
                </label>
            </div>
        </section>

        {{-- Purpose & scoring --}}
        <section class="lg:col-span-2 rounded-2xl border bg-white p-6"
                 x-data="{
                    purpose: @js(old('exam_purpose', in_array($exam->exam_purpose, ['diagnostic_only','admission_requirement'], true) ? $exam->exam_purpose : 'diagnostic_only')),
                    scholarship: @js((bool) old('grants_scholarship', $exam->grants_scholarship ?? false)),
                    bands: @js(old('scholarship_bands', $exam->scholarship_bands ?: [])),
                    addBand() { this.bands.push({ label: '', percent: '', apply_to: 'total', min_score: '' }); },
                    removeBand(i) { this.bands.splice(i, 1); },
                 }"
                 x-init="if (scholarship && bands.length === 0) addBand()">
            <style>
                [x-cloak]{display:none!important;}
                .ae-lbl{display:block;font-size:0.66rem;font-weight:800;letter-spacing:0.03em;text-transform:uppercase;color:#64748b;margin-bottom:0.3rem;}
                .ae-input{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:0.6rem 0.75rem;font-size:0.85rem;color:#1e293b;background:#fff;line-height:1.2;}
                .ae-input:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15);}
                .ae-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:0.8rem;align-items:end;}
                .ae-band{display:grid;grid-template-columns:2.4fr 1fr 1.6fr 1fr auto;gap:0.6rem;align-items:end;border:1px solid #cbd5e1;border-radius:10px;padding:0.85rem;}
                @media (max-width:640px){.ae-band{grid-template-columns:1fr 1fr;}}
            </style>

            <h2 class="text-sm font-extrabold mb-1">Exam purpose &amp; scoring</h2>
            <p class="text-xs text-slate-500 mb-4">
                Pick one gating stance (diagnostic or admission requirement). <strong>Scholarship grants</strong> can be added on top of either.
            </p>

            <div class="space-y-3 mb-5">
                {{-- Gating stance (radio: exactly one) --}}
                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer"
                       :class="purpose === 'diagnostic_only' ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200'">
                    <input type="radio" name="exam_purpose" value="diagnostic_only" x-model="purpose" class="mt-1">
                    <span>
                        <span class="font-bold block">Diagnostic only</span>
                        <span class="text-xs text-slate-500">Result is used for placement / advising. Applicant proceeds regardless of score.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer"
                       :class="purpose === 'admission_requirement' ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200'">
                    <input type="radio" name="exam_purpose" value="admission_requirement" x-model="purpose" class="mt-1">
                    <span>
                        <span class="font-bold block">Admission requirement</span>
                        <span class="text-xs text-slate-500">Applicant must reach the passing score to advance from <em>Applicant</em> to <em>Assessed</em>.</span>
                    </span>
                </label>

                {{-- Scholarship grants (independent add-on: combinable with either gating stance) --}}
                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer"
                       :class="scholarship ? 'border-emerald-400 bg-emerald-50' : 'border-slate-200'">
                    <input type="checkbox" name="grants_scholarship" value="1" x-model="scholarship"
                           @change="if (scholarship && bands.length === 0) addBand()" class="mt-1">
                    <span>
                        <span class="font-bold block">Scholarship grants <span class="text-[10px] font-extrabold text-emerald-600 uppercase">add-on</span></span>
                        <span class="text-xs text-slate-500">The exam score grants a tuition / assessment discount. Configure the score bands below.</span>
                    </span>
                </label>
            </div>

            {{-- Scoring fields — only for "Admission requirement" (one row, taller, bordered).
                 Kept in the DOM via x-show so max_score / max_attempts always submit. --}}
            <div x-show="purpose === 'admission_requirement'" x-cloak class="ae-grid">
                <div>
                    <label class="ae-lbl">Maximum score</label>
                    <input type="number" name="max_score" min="1" max="1000" value="{{ old('max_score', $exam->max_score) }}" class="ae-input">
                </div>
                <div>
                    <label class="ae-lbl">Passing score</label>
                    <input type="number" name="passing_score" min="0" value="{{ old('passing_score', $exam->passing_score) }}" class="ae-input" placeholder="Required">
                </div>
                <div>
                    <label class="ae-lbl">Max attempts</label>
                    <input type="number" name="max_attempts" min="1" max="10" value="{{ old('max_attempts', $exam->max_attempts) }}" class="ae-input">
                </div>
                <div>
                    <label class="ae-lbl">Retake cooldown (days)</label>
                    <input type="number" name="retake_cooldown_days" min="0" max="365" value="{{ old('retake_cooldown_days', $exam->retake_cooldown_days) }}" class="ae-input" placeholder="None">
                </div>
                <div>
                    <label class="ae-lbl">Result validity (months)</label>
                    <input type="number" name="result_validity_months" min="0" max="120" value="{{ old('result_validity_months', $exam->result_validity_months) }}" class="ae-input" placeholder="Indefinite">
                </div>
            </div>

            {{-- Scholarship bands — only when the Scholarship grants add-on is on.
                 Each band is one row (taller, bordered) via inline .ae-band. --}}
            <div x-show="scholarship" x-cloak class="mt-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-extrabold text-slate-600 uppercase tracking-wide">Scholarship bands</h3>
                    <button type="button" @click="addBand()"
                            class="text-xs font-bold text-indigo-600 hover:text-indigo-700">+ Add scholarship</button>
                </div>
                <p class="text-[11px] text-slate-400 mb-3">The highest minimum-score band the applicant reaches wins (no stacking).</p>

                <template x-if="bands.length === 0">
                    <p class="text-xs text-slate-400 italic py-2">No bands yet — click “Add scholarship”.</p>
                </template>

                <div class="space-y-3">
                    <template x-for="(band, i) in bands" :key="i">
                        <div class="ae-band">
                            <div>
                                <label class="ae-lbl">Scholarship type</label>
                                <input type="text" :name="`scholarship_bands[${i}][label]`" x-model="band.label" placeholder="e.g. 100% Full Scholarship" class="ae-input">
                            </div>
                            <div>
                                <label class="ae-lbl">Discount %</label>
                                <input type="number" min="0" max="100" step="0.01" :name="`scholarship_bands[${i}][percent]`" x-model="band.percent" placeholder="100" class="ae-input">
                            </div>
                            <div>
                                <label class="ae-lbl">Apply to</label>
                                <select :name="`scholarship_bands[${i}][apply_to]`" x-model="band.apply_to" class="ae-input">
                                    <option value="tuition">Tuition Fee only</option>
                                    <option value="total">Full Assessment fee</option>
                                </select>
                            </div>
                            <div>
                                <label class="ae-lbl">Min. score</label>
                                <input type="number" min="0" :name="`scholarship_bands[${i}][min_score]`" x-model="band.min_score" placeholder="90" class="ae-input">
                            </div>
                            <div style="display:flex;align-items:flex-end;padding-bottom:0.35rem;">
                                <button type="button" @click="removeBand(i)" title="Remove band" style="color:#f43f5e;font-size:1.3rem;font-weight:700;line-height:1;padding:0 0.35rem;">&times;</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Save (submits the whole Admission-Exam form) --}}
            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold">
                    Save settings
                </button>
            </div>
        </section>

        {{-- Workflow toggles + instructions --}}
        <section class="lg:col-span-3 rounded-2xl border bg-white p-6">
            <h2 class="text-sm font-extrabold mb-4">Workflow &amp; communication</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="allow_program_head_waiver" value="1"
                        {{ old('allow_program_head_waiver', $exam->allow_program_head_waiver) ? 'checked' : '' }}
                        class="mt-1 h-4 w-4 rounded border-slate-300">
                    <span>
                        <span class="font-bold block">Allow program-head waiver</span>
                        <span class="text-xs text-slate-500">Program head can exempt an applicant.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="notify_applicant_on_schedule" value="1"
                        {{ old('notify_applicant_on_schedule', $exam->notify_applicant_on_schedule) ? 'checked' : '' }}
                        class="mt-1 h-4 w-4 rounded border-slate-300">
                    <span>
                        <span class="font-bold block">Notify applicant on schedule</span>
                        <span class="text-xs text-slate-500">Email when an exam slot is booked.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="auto_assess_after_pass" value="1"
                        {{ old('auto_assess_after_pass', $exam->auto_assess_after_pass) ? 'checked' : '' }}
                        class="mt-1 h-4 w-4 rounded border-slate-300">
                    <span>
                        <span class="font-bold block">Auto-advance after pass</span>
                        <span class="text-xs text-slate-500">Move enrollment to <em>Assessed</em> automatically.</span>
                    </span>
                </label>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Instructions for applicants</label>
                <textarea name="instructions" rows="4"
                          class="w-full rounded-lg border-slate-300 text-sm"
                          placeholder="What to bring, exam coverage, dress code, etc.">{{ old('instructions', $exam->instructions) }}</textarea>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="submit"
                        class="h-10 px-5 rounded-lg text-sm font-extrabold bg-indigo-600 text-white hover:bg-indigo-700">
                    Save settings
                </button>
            </div>
        </section>
    </form>
@endif

</div>

@include('admission.settings.partials.create_session_from_term_modal')
@include('admission.settings.partials.edit_session_modal')
@include('admission.settings.partials.qr_modal')

@endsection

<script src="{{ asset('js/staff/admissions/enrollment-settings.js') }}?v={{ time() }}"></script>