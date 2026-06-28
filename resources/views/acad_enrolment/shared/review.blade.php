@extends('layouts.enrollment')

@section('content')
<div class="px-8 py-6 max-w-4xl">

    <div class="text-xs font-extrabold text-slate-500 tracking-widest mb-1">
        STEP 7 OF 8 — REVIEW &amp; SUBMIT
    </div>
    <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden mb-6">
        <div class="h-full bg-indigo-600" style="width:88%"></div>
    </div>

    <h1 class="text-2xl font-extrabold text-slate-800 mb-1">Review your application</h1>
    <p class="text-sm text-slate-500 mb-6">
        Please double-check the details below before final submission.
    </p>

    @if (session('status'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg p-3 mb-4 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @php
        $box = 'border border-slate-200 rounded-xl bg-white p-4 mb-4';
        $hd  = 'text-xs font-extrabold uppercase tracking-widest text-slate-500 mb-2';
        $row = 'flex justify-between border-b border-slate-100 py-1.5 text-sm';
        $lbl = 'text-slate-500';
        $val = 'font-semibold text-slate-800';
    @endphp

    {{-- Personal --}}
    <div class="{{ $box }}">
        <div class="flex items-center justify-between mb-2">
            <div class="{{ $hd }}">Personal Information</div>
            <a href="{{ route('public.apply.show', $term->id) }}" class="text-xs font-bold text-indigo-600 hover:underline">Edit</a>
        </div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Full Name</span>
            <span class="{{ $val }}">{{ trim(($student->first_name ?? '').' '.($student->middle_name ?? '').' '.($student->last_name ?? '')) ?: '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Date of Birth</span>
            <span class="{{ $val }}">{{ $student->date_of_birth ? \Illuminate\Support\Carbon::parse($student->date_of_birth)->format('M d, Y') : '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Gender</span>
            <span class="{{ $val }}">{{ ucfirst($student->gender ?? '—') }}</span></div>
    </div>

    {{-- Contact --}}
    <div class="{{ $box }}">
        <div class="flex items-center justify-between mb-2">
            <div class="{{ $hd }}">Contact Details</div>
            <a href="{{ route('public.apply.step2', $term->id) }}" class="text-xs font-bold text-indigo-600 hover:underline">Edit</a>
        </div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Email</span>
            <span class="{{ $val }}">{{ $student->email ?? $student->user->email ?? '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Mobile</span>
            <span class="{{ $val }}">{{ $student->mobile_number ?? $student->phone ?? '—' }}</span></div>
        @php
            $addr1     = $student->address_line_1 ?? null;
            $addr2     = $student->address_line_2 ?? null;
            $barangay  = $student->barangay ?? null;
            $city      = $student->city_municipality ?? null;
            $province  = $student->province ?? null;
            $region    = $student->region ?? null;
            $zip       = $student->zip_code ?? null;
            $country   = $student->country ?? null;
            $addrParts = array_values(array_filter([$addr1, $addr2, $barangay, $city, $province, $region, $zip, $country], fn ($v) => filled($v)));
            $fullAddr  = $addrParts ? implode(', ', $addrParts) : ($student->address ?? null);
        @endphp
        <div class="{{ $row }}"><span class="{{ $lbl }}">Address Line 1</span>
            <span class="{{ $val }}">{{ $addr1 ?: '—' }}</span></div>
        @if ($addr2)
        <div class="{{ $row }}"><span class="{{ $lbl }}">Address Line 2</span>
            <span class="{{ $val }}">{{ $addr2 }}</span></div>
        @endif
        <div class="{{ $row }}"><span class="{{ $lbl }}">Barangay</span>
            <span class="{{ $val }}">{{ $barangay ?: '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">City / Municipality</span>
            <span class="{{ $val }}">{{ $city ?: '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Province</span>
            <span class="{{ $val }}">{{ $province ?: '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Region</span>
            <span class="{{ $val }}">{{ $region ?: '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Zip Code</span>
            <span class="{{ $val }}">{{ $zip ?: '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Country</span>
            <span class="{{ $val }}">{{ $country ?: '—' }}</span></div>
    </div>

    {{-- Family & Emergency --}}
    <div class="{{ $box }}">
        <div class="flex items-center justify-between mb-2">
            <div class="{{ $hd }}">Family &amp; Emergency Contact</div>
            <a href="{{ route('public.apply.family', $term->id) }}" class="text-xs font-bold text-indigo-600 hover:underline">Edit</a>
        </div>

        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mt-1 mb-1">Parents / Guardians</div>
        @if ($parents->isEmpty())
            <p class="text-sm text-slate-500 italic">No parents or guardians on record.</p>
        @else
            <div class="space-y-2">
                @foreach ($parents as $p)
                    <div class="border border-slate-100 rounded-lg p-2 text-sm">
                        <div class="font-bold">
                            {{ trim(($p->first_name ?? '').' '.($p->middle_name ?? '').' '.($p->last_name ?? '')) ?: '—' }}
                            @if ($p->relationship)
                                <span class="text-xs text-slate-500 font-normal">({{ ucfirst($p->relationship) }})</span>
                            @endif
                            @if ($p->is_primary)
                                <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 font-bold">PRIMARY</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-500">
                            @if ($p->occupation) {{ $p->occupation }} @endif
                            @if ($p->employer) · {{ $p->employer }} @endif
                            @if ($p->mobile_number) · {{ $p->mobile_number }} @endif
                            @if ($p->email) · {{ $p->email }} @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mt-3 mb-1">Emergency Contact</div>
        @if (! $emergencyContact)
            <p class="text-sm text-slate-500 italic">No emergency contact on record.</p>
        @else
            <div class="{{ $row }}"><span class="{{ $lbl }}">Name</span>
                <span class="{{ $val }}">{{ trim(($emergencyContact->first_name ?? '').' '.($emergencyContact->last_name ?? '')) ?: '—' }}</span></div>
            <div class="{{ $row }}"><span class="{{ $lbl }}">Relationship</span>
                <span class="{{ $val }}">{{ ucfirst($emergencyContact->relationship ?? '—') }}</span></div>
            <div class="{{ $row }}"><span class="{{ $lbl }}">Mobile</span>
                <span class="{{ $val }}">{{ $emergencyContact->mobile_number ?? '—' }}</span></div>
            @if ($emergencyContact->email)
            <div class="{{ $row }}"><span class="{{ $lbl }}">Email</span>
                <span class="{{ $val }}">{{ $emergencyContact->email }}</span></div>
            @endif
            @if ($emergencyContact->address)
            <div class="{{ $row }}"><span class="{{ $lbl }}">Address</span>
                <span class="{{ $val }}">{{ $emergencyContact->address }}</span></div>
            @endif
        @endif
    </div>

    {{-- Pathway --}}
    <div class="{{ $box }}">
        <div class="flex items-center justify-between mb-2">
            <div class="{{ $hd }}">Learning Pathway</div>
            <a href="{{ route('public.apply.pathway', $term->id) }}" class="text-xs font-bold text-indigo-600 hover:underline">Edit</a>
        </div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Education Level</span>
            <span class="{{ $val }}">{{ $rootLevel?->name ?? '—' }}</span></div>
        @if (! empty($pathLabel))
        <div class="{{ $row }}"><span class="{{ $lbl }}">Path</span>
            <span class="{{ $val }}">{{ $pathLabel }}</span></div>
        @endif
        @if ($program)
        <div class="{{ $row }}"><span class="{{ $lbl }}">Programme</span>
            <span class="{{ $val }}">{{ ($program->code ? $program->code.' — ' : '').$program->name }}</span></div>
        @endif
        @if (! empty($pathway['year_level']))
        <div class="{{ $row }}"><span class="{{ $lbl }}">Year Level</span>
            <span class="{{ $val }}">{{ $pathway['year_level'] }}</span></div>
        @endif
        <div class="{{ $row }}"><span class="{{ $lbl }}">Modality</span>
            <span class="{{ $val }}">{{ $modality?->name ?? '—' }}</span></div>
        <div class="{{ $row }}"><span class="{{ $lbl }}">Student Type</span>
            <span class="{{ $val }}">
                @if ($modality && $modality->code === 'async_online')
                    <em class="text-slate-400 font-normal">N/A — Asynchronous Online</em>
                @else
                    {{ ucfirst($pathway['student_type'] ?? '—') }}
                @endif
            </span>
        </div>
    </div>

    {{-- Academic Background --}}
    <div class="{{ $box }}">
        <div class="flex items-center justify-between mb-2">
            <div class="{{ $hd }}">Academic Background</div>
            @if (! $skipped)
                <a href="{{ route('public.apply.academic', $term->id) }}" class="text-xs font-bold text-indigo-600 hover:underline">Edit</a>
            @endif
        </div>
        @if ($skipped)
            <p class="text-sm text-slate-500 italic">Skipped — Regular students reuse their prior academic record.</p>
        @elseif ($backgrounds->isEmpty())
            <p class="text-sm text-slate-500 italic">No academic backgrounds entered.</p>
        @else
            <div class="space-y-2">
                @foreach ($backgrounds as $bg)
                    <div class="border border-slate-100 rounded-lg p-2 text-sm">
                        <div class="font-bold">{{ $bg->school_name }}
                            <span class="text-xs text-slate-500 font-normal">({{ ucfirst(str_replace('_',' ', $bg->education_level)) }})</span>
                        </div>
                        <div class="text-xs text-slate-500">
                            {{ $bg->year_started ?? '?' }} – {{ $bg->year_ended ?? '?' }}
                            @if ($bg->school_address) · {{ $bg->school_address }} @endif
                            @if ($bg->gpa) · GPA: {{ $bg->gpa }} @endif
                            @if ($bg->honors) · {{ $bg->honors }} @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Health Information --}}
    @php
        $conditionLabelsRv = [
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
        $conditionsList = collect($health?->medical_conditions ?? [])
            ->map(fn ($k) => $conditionLabelsRv[$k] ?? ucfirst(str_replace('_',' ', $k)))
            ->implode(', ');
    @endphp
    <div class="{{ $box }}">
        <div class="flex items-center justify-between mb-2">
            <div class="{{ $hd }}">Health Information</div>
            <a href="{{ route('public.apply.health', $term->id) }}" class="text-xs font-bold text-indigo-600 hover:underline">Edit</a>
        </div>
        @if (! $health)
            <p class="text-sm text-slate-500 italic">No health information on record.</p>
        @else
            <div class="{{ $row }}"><span class="{{ $lbl }}">Medical condition</span>
                <span class="{{ $val }}">{{ $health->has_medical_condition ? 'Yes' : 'No' }}</span></div>
            @if ($health->has_medical_condition && $conditionsList)
                <div class="{{ $row }}"><span class="{{ $lbl }}">Conditions</span>
                    <span class="{{ $val }} text-right">{{ $conditionsList }}</span></div>
            @endif
            @if ($health->other_medical_condition)
                <div class="{{ $row }}"><span class="{{ $lbl }}">Other condition</span>
                    <span class="{{ $val }}">{{ $health->other_medical_condition }}</span></div>
            @endif
            @if ($health->allergies)
                <div class="{{ $row }}"><span class="{{ $lbl }}">Allergies</span>
                    <span class="{{ $val }} text-right">{{ $health->allergies }}</span></div>
            @endif
            <div class="{{ $row }}"><span class="{{ $lbl }}">Maintenance medication</span>
                <span class="{{ $val }}">{{ $health->takes_maintenance_medication ? 'Yes' : 'No' }}</span></div>
            @if ($health->takes_maintenance_medication)
                @if ($health->medication_name)
                <div class="{{ $row }}"><span class="{{ $lbl }}">Medication</span>
                    <span class="{{ $val }}">{{ $health->medication_name }}{{ $health->medication_dosage ? ' — '.$health->medication_dosage : '' }}{{ $health->medication_schedule ? ' ('.$health->medication_schedule.')' : '' }}</span></div>
                @endif
            @endif
            @if ($health->blood_type)
                <div class="{{ $row }}"><span class="{{ $lbl }}">Blood type</span>
                    <span class="{{ $val }}">{{ $health->blood_type === 'unknown' ? 'Unknown' : $health->blood_type }}</span></div>
            @endif
            @if ($health->emergency_medical_instructions)
                <div class="{{ $row }}"><span class="{{ $lbl }}">Emergency instructions</span>
                    <span class="{{ $val }} text-right">{{ $health->emergency_medical_instructions }}</span></div>
            @endif
            <div class="{{ $row }}"><span class="{{ $lbl }}">PWD</span>
                <span class="{{ $val }}">{{ $health->is_pwd ? 'Yes' : 'No' }}</span></div>
            @if ($health->is_pwd)
                @if ($health->pwd_type)
                <div class="{{ $row }}"><span class="{{ $lbl }}">PWD type</span>
                    <span class="{{ $val }}">{{ $health->pwd_type }}</span></div>
                @endif
                @if ($health->pwd_id_number)
                <div class="{{ $row }}"><span class="{{ $lbl }}">PWD ID</span>
                    <span class="{{ $val }}">{{ $health->pwd_id_number }}</span></div>
                @endif
            @endif
        @endif
    </div>

    <form method="POST" action="{{ route('public.apply.submit', $term->id) }}" class="flex items-center justify-between pt-2">
        @csrf
        <a href="{{ route('public.apply.health', $term->id) }}"
           class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-bold">← Back</a>

        <div class="flex items-center gap-2">
            <button type="button"
                    onclick="openEnrolmentPreview()"
                    class="px-5 py-3 rounded-lg border border-indigo-300 bg-white hover:bg-indigo-50 text-indigo-700 font-bold inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1 1 0 010-.644C3.423 7.51 7.36 4.5 12 4.5s8.577 3.01 9.964 7.178a1 1 0 010 .644C20.577 16.49 16.64 19.5 12 19.5S3.423 16.49 2.036 12.322z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Preview
            </button>
            <button type="submit"
                    class="px-7 py-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold shadow-lg">
                Submit Application ✓
            </button>
        </div>
    </form>
</div>

{{-- ============================================================== --}}
{{-- Enrolment PDF-style Preview Modal                                --}}
{{-- ============================================================== --}}
<div id="enrolmentPreviewModal"
     class="hidden fixed inset-0 z-[9999] bg-slate-900/70 backdrop-blur-sm overflow-y-auto py-8 px-4"
     onclick="if(event.target===this) closeEnrolmentPreview()">

    <div class="mx-auto bg-slate-100 rounded-xl shadow-2xl overflow-hidden"
         style="width: max-content; max-width: 100%;">

        {{-- Modal header --}}
        <div class="flex items-center justify-between px-5 py-3 bg-white border-b border-slate-200 sticky top-0 z-10">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 7V3.5L18.5 9H13z"/>
                </svg>
                <div class="font-extrabold text-slate-800 text-sm">Application Preview</div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="printEnrolmentPreview()"
                        class="text-xs font-bold text-indigo-600 hover:bg-indigo-50 px-3 py-1.5 rounded-md inline-flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/>
                    </svg>
                    Print / Save as PDF
                </button>
                <button type="button" onclick="closeEnrolmentPreview()"
                        class="text-slate-400 hover:text-slate-700 p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- A4-styled paper --}}
        <div class="p-6">
            <div id="enrolmentPreviewPaper" class="enrolment-paper bg-white shadow-md rounded-md text-slate-800" style="font-family: 'Georgia', 'Times New Roman', serif;">

                <table class="enrolment-print-table" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr><td>
                {{-- Letterhead (repeats on every printed page via <thead>) --}}
                <div class="text-center border-b-2 border-slate-800 pb-4 mb-5">
                    @if (! empty($school?->logo))
                        <img src="{{ asset('storage/'.$school->logo) }}" class="h-14 mx-auto mb-2" alt="logo">
                    @endif
                    <div class="text-xl font-extrabold tracking-wide uppercase">{{ $school->name ?? 'School' }}</div>
                    @if (! empty($school?->address))
                        <div class="text-xs text-slate-600">{{ $school->address }}</div>
                    @endif
                    <div class="mt-3 text-base font-bold uppercase tracking-widest">Application for Enrolment</div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        {{ $term->name ?? 'Term' }}
                    </div>
                </div>
                    </td></tr>
                </thead>
                <tbody>
                    <tr><td>

                @php
                    $pHd  = 'text-[11px] font-extrabold uppercase tracking-widest text-slate-500 border-b border-slate-300 pb-1 mb-2 mt-5';
                    $pRow = 'flex justify-between text-sm py-1 border-b border-dotted border-slate-200';
                    $pLbl = 'text-slate-500';
                    $pVal = 'font-semibold text-slate-800 text-right';
                @endphp

                {{-- Personal Information --}}
                <div class="pdf-section">
                <div class="{{ $pHd }}">I. Personal Information</div>
                <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Full Name</span>
                    <span class="{{ $pVal }}">{{ trim(($student->first_name ?? '').' '.($student->middle_name ?? '').' '.($student->last_name ?? '')) ?: '—' }}</span></div>
                <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Date of Birth</span>
                    <span class="{{ $pVal }}">{{ $student->date_of_birth ? \Illuminate\Support\Carbon::parse($student->date_of_birth)->format('M d, Y') : '—' }}</span></div>
                <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Gender</span>
                    <span class="{{ $pVal }}">{{ ucfirst($student->gender ?? '—') }}</span></div>

                </div>

                {{-- Contact --}}
                <div class="pdf-section">
                <div class="{{ $pHd }}">II. Contact Details</div>
                <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Email</span>
                    <span class="{{ $pVal }}">{{ $student->email ?? $student->user->email ?? '—' }}</span></div>
                <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Mobile</span>
                    <span class="{{ $pVal }}">{{ $student->mobile_number ?? $student->phone ?? '—' }}</span></div>
                <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Address</span>
                    <span class="{{ $pVal }}">{{ $fullAddr ?: '—' }}</span></div>

                </div>

                {{-- Family --}}
                <div class="pdf-section">
                <div class="{{ $pHd }}">III. Family &amp; Emergency Contact</div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Parents / Guardians</div>
                @if ($parents->isEmpty())
                    <p class="text-xs italic text-slate-500">No parents or guardians on record.</p>
                @else
                    @foreach ($parents as $p)
                        <div class="text-sm py-1 border-b border-dotted border-slate-200">
                            <span class="font-semibold">{{ trim(($p->first_name ?? '').' '.($p->last_name ?? '')) ?: '—' }}</span>
                            @if ($p->relationship) <span class="text-xs text-slate-500">({{ ucfirst($p->relationship) }})</span>@endif
                            @if ($p->is_primary) <span class="text-[10px] font-bold text-indigo-700">[PRIMARY]</span>@endif
                            <div class="text-xs text-slate-500">
                                @if ($p->occupation) {{ $p->occupation }} @endif
                                @if ($p->mobile_number) · {{ $p->mobile_number }} @endif
                                @if ($p->email) · {{ $p->email }} @endif
                            </div>
                        </div>
                    @endforeach
                @endif

                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-3 mb-1">Emergency Contact</div>
                @if (! $emergencyContact)
                    <p class="text-xs italic text-slate-500">No emergency contact on record.</p>
                @else
                    <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Name</span>
                        <span class="{{ $pVal }}">{{ trim(($emergencyContact->first_name ?? '').' '.($emergencyContact->last_name ?? '')) ?: '—' }}</span></div>
                    <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Relationship</span>
                        <span class="{{ $pVal }}">{{ ucfirst($emergencyContact->relationship ?? '—') }}</span></div>
                    <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Mobile</span>
                        <span class="{{ $pVal }}">{{ $emergencyContact->mobile_number ?? '—' }}</span></div>
                    @if ($emergencyContact->email)
                    <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Email</span>
                        <span class="{{ $pVal }}">{{ $emergencyContact->email }}</span></div>
                    @endif
                @endif

                </div>

                {{-- Pathway --}}
                <div class="pdf-section">
                <div class="{{ $pHd }}">IV. Learning Pathway</div>
                <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Education Level</span>
                    <span class="{{ $pVal }}">{{ $rootLevel?->name ?? '—' }}</span></div>
                @if (! empty($pathLabel))
                <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Path</span>
                    <span class="{{ $pVal }}">{{ $pathLabel }}</span></div>
                @endif
                @if ($program)
                <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Programme</span>
                    <span class="{{ $pVal }}">{{ ($program->code ? $program->code.' — ' : '').$program->name }}</span></div>
                @endif
                @if (! empty($pathway['year_level']))
                <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Year Level</span>
                    <span class="{{ $pVal }}">{{ $pathway['year_level'] }}</span></div>
                @endif
                <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Modality</span>
                    <span class="{{ $pVal }}">{{ $modality?->name ?? '—' }}</span></div>
                <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Student Type</span>
                    <span class="{{ $pVal }}">
                        @if ($modality && $modality->code === 'async_online')
                            N/A — Asynchronous Online
                        @else
                            {{ ucfirst($pathway['student_type'] ?? '—') }}
                        @endif
                    </span>
                </div>

                </div>

                {{-- Academic --}}
                <div class="pdf-section">
                <div class="{{ $pHd }}">V. Academic Background</div>
                @if ($skipped)
                    <p class="text-xs italic text-slate-500">Skipped — Regular students reuse their prior academic record.</p>
                @elseif ($backgrounds->isEmpty())
                    <p class="text-xs italic text-slate-500">No academic backgrounds entered.</p>
                @else
                    @foreach ($backgrounds as $bg)
                        <div class="text-sm py-1 border-b border-dotted border-slate-200">
                            <div><span class="font-semibold">{{ $bg->school_name }}</span>
                                <span class="text-xs text-slate-500">({{ ucfirst(str_replace('_',' ', $bg->education_level)) }})</span></div>
                            <div class="text-xs text-slate-500">
                                {{ $bg->year_started ?? '?' }} – {{ $bg->year_ended ?? '?' }}
                                @if ($bg->school_address) · {{ $bg->school_address }} @endif
                                @if ($bg->gpa) · GPA: {{ $bg->gpa }} @endif
                                @if ($bg->honors) · {{ $bg->honors }} @endif
                            </div>
                        </div>
                    @endforeach
                @endif

                </div>

                {{-- Health Information --}}
                <div class="pdf-section">
                <div class="{{ $pHd }}">VI. Health Information</div>
                @if (! $health)
                    <p class="text-xs italic text-slate-500">No health information on record.</p>
                @else
                    <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Medical condition</span>
                        <span class="{{ $pVal }}">{{ $health->has_medical_condition ? 'Yes' : 'No' }}</span></div>
                    @if ($health->has_medical_condition && $conditionsList)
                        <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Conditions</span>
                            <span class="{{ $pVal }}">{{ $conditionsList }}</span></div>
                    @endif
                    @if ($health->other_medical_condition)
                        <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Other condition</span>
                            <span class="{{ $pVal }}">{{ $health->other_medical_condition }}</span></div>
                    @endif
                    @if ($health->allergies)
                        <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Allergies</span>
                            <span class="{{ $pVal }}">{{ $health->allergies }}</span></div>
                    @endif
                    <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Maintenance medication</span>
                        <span class="{{ $pVal }}">{{ $health->takes_maintenance_medication ? 'Yes' : 'No' }}</span></div>
                    @if ($health->takes_maintenance_medication && $health->medication_name)
                        <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Medication</span>
                            <span class="{{ $pVal }}">{{ $health->medication_name }}{{ $health->medication_dosage ? ' — '.$health->medication_dosage : '' }}{{ $health->medication_schedule ? ' ('.$health->medication_schedule.')' : '' }}</span></div>
                    @endif
                    @if ($health->blood_type)
                        <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Blood type</span>
                            <span class="{{ $pVal }}">{{ $health->blood_type === 'unknown' ? 'Unknown' : $health->blood_type }}</span></div>
                    @endif
                    @if ($health->emergency_medical_instructions)
                        <div class="{{ $pRow }}"><span class="{{ $pLbl }}">Emergency instructions</span>
                            <span class="{{ $pVal }}">{{ $health->emergency_medical_instructions }}</span></div>
                    @endif
                    <div class="{{ $pRow }}"><span class="{{ $pLbl }}">PWD</span>
                        <span class="{{ $pVal }}">{{ $health->is_pwd ? 'Yes' : 'No' }}</span></div>
                    @if ($health->is_pwd && $health->pwd_type)
                        <div class="{{ $pRow }}"><span class="{{ $pLbl }}">PWD type</span>
                            <span class="{{ $pVal }}">{{ $health->pwd_type }}{{ $health->pwd_id_number ? ' · ID '.$health->pwd_id_number : '' }}</span></div>
                    @endif
                @endif
                </div>

                <div class="pdf-section mt-10 pt-8 grid grid-cols-2 gap-8 text-xs">
                    <div>
                        <div class="h-5 text-center font-semibold leading-5 uppercase">
                            {{ trim(($student->first_name ?? '').' '.($student->middle_name ?? '').' '.($student->last_name ?? '')) ?: '' }}
                        </div>
                        <div class="border-t border-slate-800 pt-1 text-center font-semibold">Applicant Name</div>
                    </div>
                    <div>
                        <div class="h-5 text-center font-semibold leading-5" id="enrolmentPreviewSignDate">{{ now()->format('F d, Y') }}</div>
                        <div class="border-t border-slate-800 pt-1 text-center font-semibold">Date</div>
                    </div>
                </div>
                    </td></tr>
                </tbody>
                </table>
            </div>
        </div>

        {{-- Modal footer with Cancel + Submit --}}
        <div class="flex items-center justify-end gap-3 px-5 py-4 bg-white border-t border-slate-200 sticky bottom-0">
            <button type="button" onclick="closeEnrolmentPreview()"
                    class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-bold hover:bg-slate-50">
                Cancel
            </button>
            <form method="POST" action="{{ route('public.apply.submit', $term->id) }}" class="m-0">
                @csrf
                <button type="submit"
                        class="px-7 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold shadow-lg inline-flex items-center gap-2">
                    Submit Application ✓
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* A4 paper preview (on-screen) */
.enrolment-paper {
    width: 210mm;
    min-height: 297mm;
    padding: 18mm 16mm;
    margin: 0 auto;
    box-sizing: border-box;
}
/* Keep each section together; if it won't fit, push it to the next page */
.pdf-section {
    break-inside: avoid;
    page-break-inside: avoid;
}

/* Table-based layout so <thead> letterhead repeats on every printed page */
.enrolment-print-table { width: 100%; border-collapse: collapse; }
.enrolment-print-table > thead { display: table-header-group; }
.enrolment-print-table > tbody { display: table-row-group; }
.enrolment-print-table td { padding: 0; vertical-align: top; }

/* Print container only used while printing (cloned at print time) */
#enrolmentPrintArea { display: none; }

@media print {
    @page { size: A4; margin: 12mm; }
    html, body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    /* Hide the whole page; show only the print container */
    body.printing-enrolment > *:not(#enrolmentPrintArea) {
        display: none !important;
    }
    body.printing-enrolment #enrolmentPrintArea {
        display: block !important;
        position: static !important;
        width: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
    }
    body.printing-enrolment #enrolmentPrintArea .enrolment-paper {
        width: 100% !important;
        min-height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        background: #fff !important;
    }
    .pdf-section {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
}
</style>

{{-- Body-level print container (populated at print time) --}}
<div id="enrolmentPrintArea" aria-hidden="true"></div>

<script>
    function openEnrolmentPreview() {
        // Auto-fill the "Date" line above the signature block with today's date
        var signDate = document.getElementById('enrolmentPreviewSignDate');
        if (signDate) {
            var now = new Date();
            var months = ['January','February','March','April','May','June',
                          'July','August','September','October','November','December'];
            var dd = String(now.getDate()).padStart(2, '0');
            signDate.textContent = months[now.getMonth()] + ' ' + dd + ', ' + now.getFullYear();
        }
        document.getElementById('enrolmentPreviewModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeEnrolmentPreview() {
        document.getElementById('enrolmentPreviewModal').classList.add('hidden');
        document.body.style.overflow = '';
    }
    function printEnrolmentPreview() {
        var paper = document.getElementById('enrolmentPreviewPaper');
        if (!paper) { window.print(); return; }

        // Ensure the print area is a DIRECT child of <body> so the print CSS
        // can hide every other top-level element.
        var area = document.getElementById('enrolmentPrintArea');
        if (!area) {
            area = document.createElement('div');
            area.id = 'enrolmentPrintArea';
        }
        if (area.parentNode !== document.body) {
            document.body.appendChild(area);
        }
        area.innerHTML = '';
        area.appendChild(paper.cloneNode(true));

        // Save & clear body overflow (we set it to 'hidden' when opening the modal,
        // which prevents the print engine from paginating).
        var prevOverflow = document.body.style.overflow;
        var prevHtmlOverflow = document.documentElement.style.overflow;
        document.body.style.overflow = 'visible';
        document.documentElement.style.overflow = 'visible';

        document.body.classList.add('printing-enrolment');

        var cleanup = function () {
            document.body.classList.remove('printing-enrolment');
            area.innerHTML = '';
            document.body.style.overflow = prevOverflow;
            document.documentElement.style.overflow = prevHtmlOverflow;
            window.removeEventListener('afterprint', cleanup);
        };
        window.addEventListener('afterprint', cleanup);
        setTimeout(function () { window.print(); }, 50);
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeEnrolmentPreview();
    });
</script>
@endsection
