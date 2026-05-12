@extends('layouts.enrollment')

@section('content')
<div class="px-8 py-6 max-w-4xl">

    <div class="text-xs font-extrabold text-slate-500 tracking-widest mb-1">
        STEP 5 OF 7 — ACADEMIC BACKGROUND
    </div>
    <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden mb-6">
        <div class="h-full bg-indigo-600" style="width:71%"></div>
    </div>

    <h1 class="text-2xl font-extrabold text-slate-800 mb-1">Academic Background</h1>
    <p class="text-sm text-slate-500 mb-6">
        List the schools you previously attended. Add a row per level (elementary,
        junior high, senior high, college, etc.).
    </p>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 mb-4 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('public.apply.academic.store', $term->id) }}"
          x-data="academicBackgroundForm()" x-init="hydrate()">
        @csrf

        <div class="space-y-4" id="bgList">
            <template x-for="(row, idx) in rows" :key="idx">
                <div class="border border-slate-200 rounded-xl p-4 bg-white shadow-sm relative">
                    <button type="button" @click="rows.splice(idx,1)" x-show="rows.length > 1"
                            class="absolute top-2 right-3 text-red-500 text-sm font-bold hover:text-red-700">
                        Remove
                    </button>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-bold text-slate-600">Education Level *</label>
                            <select :name="`backgrounds[${idx}][education_level]`" x-model="row.education_level" required
                                    class="w-full rounded-lg border border-slate-300 p-2">
                                <option value="">— select —</option>
                                <option value="elementary">Elementary</option>
                                <option value="junior_high">Junior High School</option>
                                <option value="senior_high">Senior High School</option>
                                <option value="college">College</option>
                                <option value="graduate">Graduate Studies</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600">School Type</label>
                            <select :name="`backgrounds[${idx}][school_type]`" x-model="row.school_type"
                                    class="w-full rounded-lg border border-slate-300 p-2">
                                <option value="">—</option>
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                                <option value="home">Home School</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-bold text-slate-600">School Name *</label>
                            <input type="text" :name="`backgrounds[${idx}][school_name]`" x-model="row.school_name" required
                                   class="w-full rounded-lg border border-slate-300 p-2">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-bold text-slate-600">School Address</label>
                            <input type="text" :name="`backgrounds[${idx}][school_address]`" x-model="row.school_address"
                                   class="w-full rounded-lg border border-slate-300 p-2">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600">Last Grade Level</label>
                            <input type="text" :name="`backgrounds[${idx}][last_grade_level]`" x-model="row.last_grade_level"
                                   class="w-full rounded-lg border border-slate-300 p-2">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600">GPA</label>
                            <input type="number" step="0.01" min="0" max="100"
                                   :name="`backgrounds[${idx}][gpa]`" x-model="row.gpa"
                                   class="w-full rounded-lg border border-slate-300 p-2">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600">Year Started</label>
                            <input type="number" min="1950" max="2100"
                                   :name="`backgrounds[${idx}][year_started]`" x-model="row.year_started"
                                   class="w-full rounded-lg border border-slate-300 p-2">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600">Year Ended</label>
                            <input type="number" min="1950" max="2100"
                                   :name="`backgrounds[${idx}][year_ended]`" x-model="row.year_ended"
                                   class="w-full rounded-lg border border-slate-300 p-2">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-bold text-slate-600">Honors / Awards</label>
                            <input type="text" :name="`backgrounds[${idx}][honors]`" x-model="row.honors"
                                   class="w-full rounded-lg border border-slate-300 p-2">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <button type="button" @click="rows.push(blank())"
                class="mt-3 px-4 py-2 rounded-lg border-2 border-dashed border-indigo-300 text-indigo-700 font-bold hover:bg-indigo-50">
            + Add another school
        </button>

        <div class="flex items-center justify-between pt-6">
            <a href="{{ route('public.apply.pathway', $term->id) }}"
               class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-bold">← Back</a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold">
                Continue →
            </button>
        </div>
    </form>
</div>

<script>
function academicBackgroundForm() {
    return {
        rows: [],
        blank() {
            return {
                education_level: '', school_name: '', school_address: '',
                school_type: '', last_grade_level: '',
                year_started: '', year_ended: '', gpa: '', honors: '',
            };
        },
        hydrate() {
            const existing = @json($backgrounds->map(fn($b) => [
                'education_level'  => $b->education_level,
                'school_name'      => $b->school_name,
                'school_address'   => $b->school_address,
                'school_type'      => $b->school_type,
                'last_grade_level' => $b->last_grade_level,
                'year_started'     => $b->year_started,
                'year_ended'       => $b->year_ended,
                'gpa'              => $b->gpa,
                'honors'           => $b->honors,
            ])->values());
            this.rows = existing.length ? existing : [this.blank()];
        },
    };
}
</script>
@endsection
