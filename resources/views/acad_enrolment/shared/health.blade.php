@extends('layouts.enrollment')

@section('content')
{{-- Alpine.js (Health form uses x-data/x-show for conditional fields). --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak] { display: none !important; }</style>
@php
    /**
     * Map a condition key → human label. Keep keys in sync with controller
     * validation list and Blade old() repopulation below.
     */
    $conditionOptions = [
        'asthma'              => 'Asthma',
        'diabetes'            => 'Diabetes',
        'heart_condition'     => 'Heart Condition',
        'epilepsy'            => 'Epilepsy / Seizures',
        'allergies'           => 'Allergies',
        'physical_disability' => 'Physical Disability',
        'adhd'                => 'ADHD',
        'autism'              => 'Autism Spectrum Disorder',
        'visual_impairment'   => 'Visual Impairment',
        'hearing_impairment'  => 'Hearing Impairment',
        'mental_health'       => 'Mental Health Condition',
        'others'              => 'Others',
    ];

    $bloodTypes = ['A+','A-','B+','B-','AB+','AB-','O+','O-','unknown'];

    $h = $health;

    // Helpers — coerce DB values to the strings expected by old('field').
    $boolStr = fn ($v) => is_null($v) ? null : ($v ? '1' : '0');

    // Selected conditions for Alpine init.
    $savedConditions = old('medical_conditions', $h?->medical_conditions ?? []);
@endphp

<div class="px-4 sm:px-8 py-6 max-w-4xl mx-auto"
     x-data="healthForm({
        hasCondition: @js(old('has_medical_condition', $boolStr($h?->has_medical_condition))),
        conditions:   @js($savedConditions),
        takesMed:     @js(old('takes_maintenance_medication', $boolStr($h?->takes_maintenance_medication))),
        isPwd:        @js(old('is_pwd', $boolStr($h?->is_pwd))),
     })">

    <div class="text-xs font-extrabold text-slate-500 tracking-widest mb-1">
        STEP 6 OF 9 — HEALTH INFORMATION
    </div>
    <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden mb-6">
        <div class="h-full bg-indigo-600" style="width:67%"></div>
    </div>

    <h1 class="text-2xl font-extrabold text-slate-800 mb-1">Health Information</h1>
    <p class="text-sm text-slate-500 mb-6">
        Help us care for the student in case of medical emergencies. All
        information is kept confidential and only accessible to authorised
        school personnel.
    </p>

    @if (session('status'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg p-3 mb-4 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-lg p-3 mb-4 text-sm">
            <div class="font-bold mb-1">Please correct the following:</div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('public.apply.health.store', $term->id) }}" class="space-y-3">
        @csrf

        {{-- ============================================================ --}}
        {{-- 1) Medical Condition Question                                 --}}
        {{-- ============================================================ --}}
        <section class="border border-slate-200 rounded-lg bg-white p-3 sm:p-4">
            <label class="block text-sm font-bold text-slate-800 mb-2">
                Does the student have any medical condition or health concern the school should know about?
                <span class="text-rose-500">*</span>
            </label>
            <div class="flex flex-wrap gap-2">
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-slate-300 cursor-pointer hover:bg-slate-50 text-xs"
                       :class="hasCondition === '1' ? 'border-indigo-500 bg-indigo-50' : ''">
                    <input type="radio" name="has_medical_condition" value="1"
                           x-model="hasCondition" class="w-3.5 h-3.5 text-indigo-600">
                    <span class="font-semibold">Yes</span>
                </label>
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-slate-300 cursor-pointer hover:bg-slate-50 text-xs"
                       :class="hasCondition === '0' ? 'border-indigo-500 bg-indigo-50' : ''">
                    <input type="radio" name="has_medical_condition" value="0"
                           x-model="hasCondition" class="w-3.5 h-3.5 text-indigo-600">
                    <span class="font-semibold">No</span>
                </label>
            </div>

            {{-- Conditional: Medical Conditions checkboxes + allergies --}}
            <div x-show="hasCondition === '1'" x-cloak x-transition class="mt-3 space-y-3">

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        Medical conditions <span class="font-normal normal-case text-slate-400">(check all that apply)</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-1.5">
                        @foreach ($conditionOptions as $key => $label)
                            <label class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs">
                                <input type="checkbox" name="medical_conditions[]"
                                       value="{{ $key }}"
                                       x-model="conditions"
                                       class="w-3.5 h-3.5 rounded text-indigo-600">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Other specify --}}
                <div x-show="conditions.includes('others')" x-cloak x-transition>
                    <label for="other_medical_condition" class="block text-xs font-semibold text-slate-700 mb-1">
                        Please specify
                    </label>
                    <input type="text" name="other_medical_condition" id="other_medical_condition"
                           value="{{ old('other_medical_condition', $h?->other_medical_condition) }}"
                           maxlength="255"
                           class="w-full px-2.5 py-1.5 rounded-md border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-xs">
                </div>

                {{-- Allergies free-text --}}
                <div>
                    <label for="allergies" class="block text-xs font-semibold text-slate-700 mb-1">
                        Allergies <span class="text-slate-400 font-normal">(food, medicine, environmental, etc.)</span>
                    </label>
                    <textarea name="allergies" id="allergies" rows="2"
                              class="w-full px-2.5 py-1.5 rounded-md border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-xs"
                              placeholder="e.g. Peanuts, penicillin, dust mites">{{ old('allergies', $h?->allergies) }}</textarea>
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 2) Maintenance Medication                                     --}}
        {{-- ============================================================ --}}
        <section class="border border-slate-200 rounded-lg bg-white p-3 sm:p-4">
            <label class="block text-sm font-bold text-slate-800 mb-2">
                Is the student taking maintenance medication?
                <span class="text-rose-500">*</span>
            </label>
            <div class="flex flex-wrap gap-2">
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-slate-300 cursor-pointer hover:bg-slate-50 text-xs"
                       :class="takesMed === '1' ? 'border-indigo-500 bg-indigo-50' : ''">
                    <input type="radio" name="takes_maintenance_medication" value="1"
                           x-model="takesMed" class="w-3.5 h-3.5 text-indigo-600">
                    <span class="font-semibold">Yes</span>
                </label>
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-slate-300 cursor-pointer hover:bg-slate-50 text-xs"
                       :class="takesMed === '0' ? 'border-indigo-500 bg-indigo-50' : ''">
                    <input type="radio" name="takes_maintenance_medication" value="0"
                           x-model="takesMed" class="w-3.5 h-3.5 text-indigo-600">
                    <span class="font-semibold">No</span>
                </label>
            </div>

            <div x-show="takesMed === '1'" x-cloak x-transition class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div>
                    <label for="medication_name" class="block text-xs font-semibold text-slate-700 mb-1">Medication name</label>
                    <input type="text" name="medication_name" id="medication_name"
                           value="{{ old('medication_name', $h?->medication_name) }}"
                           class="w-full px-2.5 py-1.5 rounded-md border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-xs">
                </div>
                <div>
                    <label for="medication_dosage" class="block text-xs font-semibold text-slate-700 mb-1">Dosage</label>
                    <input type="text" name="medication_dosage" id="medication_dosage"
                           value="{{ old('medication_dosage', $h?->medication_dosage) }}"
                           placeholder="e.g. 10 mg"
                           class="w-full px-2.5 py-1.5 rounded-md border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-xs">
                </div>
                <div>
                    <label for="medication_schedule" class="block text-xs font-semibold text-slate-700 mb-1">Schedule</label>
                    <input type="text" name="medication_schedule" id="medication_schedule"
                           value="{{ old('medication_schedule', $h?->medication_schedule) }}"
                           placeholder="e.g. Once daily, AM"
                           class="w-full px-2.5 py-1.5 rounded-md border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-xs">
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 3) Blood Type + Emergency Instructions                        --}}
        {{-- ============================================================ --}}
        <section class="border border-slate-200 rounded-lg bg-white p-3 sm:p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label for="blood_type" class="block text-xs font-bold text-slate-800 mb-1">Blood type</label>
                <select name="blood_type" id="blood_type"
                        class="w-full px-2.5 py-1.5 rounded-md border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-xs">
                    <option value="">— Select —</option>
                    @foreach ($bloodTypes as $bt)
                        @php $selected = old('blood_type', $h?->blood_type) === $bt; @endphp
                        <option value="{{ $bt }}" @selected($selected)>{{ $bt === 'unknown' ? 'Unknown' : $bt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label for="emergency_medical_instructions" class="block text-xs font-bold text-slate-800 mb-1">
                    Emergency medical instructions
                </label>
                <textarea name="emergency_medical_instructions" id="emergency_medical_instructions" rows="2"
                          placeholder="Enter important medical instructions in case of emergency"
                          class="w-full px-2.5 py-1.5 rounded-md border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-xs">{{ old('emergency_medical_instructions', $h?->emergency_medical_instructions) }}</textarea>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 4) PWD                                                        --}}
        {{-- ============================================================ --}}
        <section class="border border-slate-200 rounded-lg bg-white p-3 sm:p-4">
            <label class="block text-sm font-bold text-slate-800 mb-2">
                Is the student a Person With Disability (PWD)?
                <span class="text-rose-500">*</span>
            </label>
            <div class="flex flex-wrap gap-2">
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-slate-300 cursor-pointer hover:bg-slate-50 text-xs"
                       :class="isPwd === '1' ? 'border-indigo-500 bg-indigo-50' : ''">
                    <input type="radio" name="is_pwd" value="1"
                           x-model="isPwd" class="w-3.5 h-3.5 text-indigo-600">
                    <span class="font-semibold">Yes</span>
                </label>
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-slate-300 cursor-pointer hover:bg-slate-50 text-xs"
                       :class="isPwd === '0' ? 'border-indigo-500 bg-indigo-50' : ''">
                    <input type="radio" name="is_pwd" value="0"
                           x-model="isPwd" class="w-3.5 h-3.5 text-indigo-600">
                    <span class="font-semibold">No</span>
                </label>
            </div>

            <div x-show="isPwd === '1'" x-cloak x-transition class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div>
                    <label for="pwd_type" class="block text-xs font-semibold text-slate-700 mb-1">PWD type</label>
                    <input type="text" name="pwd_type" id="pwd_type"
                           value="{{ old('pwd_type', $h?->pwd_type) }}"
                           placeholder="e.g. Visual, Hearing, Mobility"
                           class="w-full px-2.5 py-1.5 rounded-md border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-xs">
                </div>
                <div>
                    <label for="pwd_id_number" class="block text-xs font-semibold text-slate-700 mb-1">
                        PWD ID number <span class="text-slate-400 font-normal">(optional)</span>
                    </label>
                    <input type="text" name="pwd_id_number" id="pwd_id_number"
                           value="{{ old('pwd_id_number', $h?->pwd_id_number) }}"
                           class="w-full px-2.5 py-1.5 rounded-md border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-xs">
                </div>
            </div>
        </section>

        {{-- Footer actions --}}
        <div class="flex items-center justify-between pt-2">
            @php
                $skipped = (bool) session("apply.academic_skipped.{$term->id}");
                $backRoute = $skipped
                    ? route('public.apply.pathway', $term->id)
                    : route('public.apply.academic', $term->id);
            @endphp
            <a href="{{ $backRoute }}"
               class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-bold">← Back</a>

            <button type="submit"
                    class="px-7 py-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold shadow-lg">
                Save &amp; Continue →
            </button>
        </div>
    </form>
</div>

<script>
    function healthForm({ hasCondition, conditions, takesMed, isPwd }) {
        return {
            hasCondition: hasCondition ?? null,
            conditions:   Array.isArray(conditions) ? conditions : [],
            takesMed:     takesMed ?? null,
            isPwd:        isPwd ?? null,
        };
    }
</script>
@endsection
