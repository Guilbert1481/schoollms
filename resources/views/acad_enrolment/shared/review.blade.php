@extends('layouts.enrollment')

@section('content')
<style>
    /* Build-independent layout (compiled Tailwind build is stale). */
    .rv-page    { padding: 1.25rem 1.5rem; }
    .rv-header  { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.1rem; }
    .rv-htitle  { display:flex; align-items:center; gap:0.8rem; }
    .rv-hicon   { width:46px; height:46px; border-radius:13px; background:#eef2ff; color:#4f46e5; display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
    .rv-almost  { background:#eef2ff; border:1px solid #e0e7ff; border-radius:13px; padding:0.6rem 0.9rem; max-width:22rem; }

    .rv-scroll  { overflow-y:auto; max-height: calc(100vh - 250px); padding-right:0.3rem; }

    .rv-card    { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1.05rem 1.2rem; }
    .rv-chead   { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.55rem; }
    .rv-ctitle  { display:flex; align-items:center; gap:0.55rem; font-weight:800; color:#1e293b; font-size:0.92rem; }
    .rv-cicon   { width:30px; height:30px; border-radius:9px; background:#eef2ff; color:#4f46e5; display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
    .rv-edit    { color:#4f46e5; font-size:0.78rem; font-weight:700; text-decoration:none; }
    .rv-edit:hover { text-decoration:underline; }

    .rv-row     { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:0.42rem 0; border-bottom:1px solid #f1f5f9; font-size:0.84rem; }
    .rv-row:last-child { border-bottom:0; }
    .rv-lbl     { color:#64748b; }
    .rv-val     { font-weight:600; color:#1e293b; text-align:right; }
    .rv-sub     { font-size:0.68rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:#94a3b8; margin:0.5rem 0 0.3rem; }
    .rv-mini    { border:1px solid #f1f5f9; border-radius:9px; padding:0.5rem 0.6rem; font-size:0.82rem; }

    .rv-top     { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
    .rv-col     { display:flex; flex-direction:column; gap:1rem; }
    .rv-three   { display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem; margin-bottom:1rem; }
    .rv-fin     { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
    @media (max-width: 1000px) { .rv-top, .rv-three, .rv-fin { grid-template-columns:1fr; } }

    .rv-footer  { display:flex; align-items:center; justify-content:space-between; gap:0.75rem; padding-top:1rem; margin-top:0.6rem; border-top:1px solid #e2e8f0; }
    .rv-ico     { width:17px; height:17px; }
</style>

@php
    // Address (shared by the cards + the print preview further below).
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

    $conditionLabelsRv = [
        'asthma' => 'Asthma', 'diabetes' => 'Diabetes', 'heart_condition' => 'Heart Condition',
        'epilepsy' => 'Epilepsy / Seizures', 'allergies' => 'Allergies', 'physical_disability' => 'Physical Disability',
        'adhd' => 'ADHD', 'autism' => 'Autism Spectrum Disorder', 'visual_impairment' => 'Visual Impairment',
        'hearing_impairment' => 'Hearing Impairment', 'mental_health' => 'Mental Health Condition', 'others' => 'Others',
    ];
    $conditionsList = collect($health?->medical_conditions ?? [])
        ->map(fn ($k) => $conditionLabelsRv[$k] ?? ucfirst(str_replace('_', ' ', $k)))->implode(', ');

    $peso = fn ($v) => '₱'.number_format((float) $v, 2);

    // Printable letterhead (school branding/contact from School Settings → School tab).
    $logoUrl    = ($profile && $profile->school_logo) ? asset('storage/'.$profile->school_logo) : null;
    // Uploaded letterhead images. When present they replace the default coloured
    // bands and scale proportionally to the paper.
    $headerUrl  = ($profile && $profile->school_header)     ? asset('storage/'.$profile->school_header)     : null;
    $footerUrl  = ($profile && $profile->school_footer)     ? asset('storage/'.$profile->school_footer)     : null;
    $bgUrl      = ($profile && $profile->school_background) ? asset('storage/'.$profile->school_background) : null;
    // Reserved header/footer space on the paper (inches). Falls back to the
    // defaults if unset.
    $headerSpace = ($profile && is_numeric($profile->header_space)) ? (float) $profile->header_space : 1.0;
    $footerSpace = ($profile && is_numeric($profile->footer_space)) ? (float) $profile->footer_space : 0.5;
    $schoolName = $profile->school_name ?? 'School';
    $motto      = $profile->motto ?? null;
    $schoolAddr = $profile->address ?? null;
    $schoolPhone= $profile->contact_number ?: ($profile->mobile_number ?? null);
    $schoolMail = $profile->email ?? null;
    $schoolWeb  = $profile->website ?? null;
    $ayLabel    = optional($term->academicYear)->name ?? ($term->academic_year ?? $term->name);

    $studentFull   = trim(($student->first_name ?? '').' '.($student->middle_name ?? '').' '.($student->last_name ?? '')) ?: '—';
    $primaryParent = $parents->firstWhere('is_primary', true) ?? $parents->first();
    $guardianName  = $primaryParent ? (trim(($primaryParent->first_name ?? '').' '.($primaryParent->last_name ?? '')) ?: 'Guardian') : 'Guardian';

    // Acknowledgement / Data-Privacy / Electronic-Confirmation copy (reused on the
    // review page and the printable preview). {school} is the configured school name.
    $ackP1pre  = 'I, ';
    $ackP1post = ', hereby certify that all information, declarations, documents, and supporting records provided in this application are true, complete, accurate, and correct to the best of my knowledge and belief.';

    // Guardian-mode acknowledgement. When a legal guardian certifies, the wording
    // states the guardianship and the relationship to the student — gendered from
    // the student's gender and resolved from the primary guardian's recorded role.
    // ('grand' is matched before 'father'/'mother' so "grandfather" ≠ "father".)
    $studentGender = strtolower(trim((string) ($student->gender ?? '')));
    $pickGender    = fn ($m, $f, $n) => str_starts_with($studentGender, 'm') ? $m : (str_starts_with($studentGender, 'f') ? $f : $n);
    $guardianRel   = strtolower(trim((string) ($primaryParent->relationship ?? '')));
    $childTerm     = match (true) {
        str_contains($guardianRel, 'grand')
            => $pickGender('grandson', 'granddaughter', 'grandchild'),
        str_contains($guardianRel, 'father'), str_contains($guardianRel, 'mother'), str_contains($guardianRel, 'parent')
            => $pickGender('son', 'daughter', 'child'),
        str_contains($guardianRel, 'uncle'), str_contains($guardianRel, 'aunt')
            => $pickGender('nephew', 'niece', 'nephew or niece'),
        str_contains($guardianRel, 'brother'), str_contains($guardianRel, 'sister'), str_contains($guardianRel, 'sibling')
            => $pickGender('brother', 'sister', 'sibling'),
        str_contains($guardianRel, 'cousin') => 'cousin',
        default => 'ward',
    };
    $emph = fn ($t) => '<span style="font-weight:700; color:#16223e;">'.e($t).'</span>';
    $ackGuardianInner = 'I, '.$emph($guardianName).', hereby certify that I am the legal guardian of '.$emph($studentFull)
        .', and that I am completing and submitting this application on behalf of my '.$emph($childTerm)
        .', and that all information, declarations, documents, and supporting records provided in this application are true, complete, accurate, and correct to the best of my knowledge and belief.';
    $ackParas = [
        "I understand that any false, misleading, incomplete, or fraudulent information may result in the denial of my application, cancellation of admission or enrollment, withdrawal of privileges, or other disciplinary and administrative actions in accordance with the policies of {$schoolName} and applicable laws and regulations.",
        'I further acknowledge that I am responsible for ensuring that all information submitted remains accurate and that I will promptly notify the School of any changes to my personal, academic, or contact information.',
    ];
    $ackCheck = 'I have read, understood, and voluntarily agree to the above Acknowledgement of Accuracy and Truthfulness.';

    $privacyParas = [
        "In accordance with the Data Privacy Act of 2012 (Republic Act No. 10173) and its Implementing Rules and Regulations, I voluntarily authorize {$schoolName} to collect, process, store, use, share, and retain my personal information and, where applicable, sensitive personal information solely for legitimate educational, administrative, legal, financial, security, health, communication, and student support purposes.",
        'I understand that my personal information shall be processed only to the extent necessary for admission, enrollment, academic records management, student services, billing and payment processing, scholarship administration, regulatory reporting, alumni relations, campus safety, and other lawful school operations.',
        'I acknowledge that the School shall implement appropriate organizational, physical, and technical security measures to safeguard my personal information and shall process such information in accordance with applicable data privacy laws and regulations.',
        'I understand that I may exercise my rights as a data subject, including the right to access, correct, withdraw consent where applicable, or inquire about the processing of my personal data, subject to legal and institutional requirements.',
    ];
    $privacyCheck = "I have read, understood, and voluntarily consent to the collection and processing of my personal information in accordance with the School's Data Privacy Policy and the Data Privacy Act of 2012 (RA 10173).";

    $elecConfirm = 'By clicking "Submit Application", I acknowledge that my electronic confirmation shall have the same legal force and effect as my handwritten signature for purposes of this application and that I voluntarily agree to the statements above.';
@endphp

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none!important;}</style>

<div x-data="{ ack:false, privacy:false, certifier:'', studentName: @js($studentFull), guardianName: @js($guardianName), get certifierName(){ return this.certifier==='guardian' ? this.guardianName : (this.certifier==='student' ? this.studentName : '________________'); }, get certifierLabel(){ return this.certifier==='guardian' ? 'Parent or Legal Guardian' : 'Student Applicant'; } }">

<div class="rv-page">

    {{-- Header --}}
    <div class="rv-header">
        <div class="rv-htitle">
            <span class="rv-hicon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            </span>
            <div>
                <h1 style="font-size:1.4rem; font-weight:800; color:#1e293b; line-height:1.2;">Review Your Application</h1>
                <p style="font-size:0.84rem; color:#64748b; margin-top:0.15rem;">Please review all the information below. You can edit any section if needed before final submission.</p>
            </div>
        </div>
        <div class="rv-almost">
            <div style="display:flex; align-items:center; gap:0.4rem; font-weight:800; color:#4338ca; font-size:0.85rem;">
                <svg class="rv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                Almost there!
            </div>
            <p style="font-size:0.78rem; color:#6366f1; margin-top:0.15rem;">Please confirm that all details are correct.</p>
        </div>
    </div>

    @if (session('status'))
        <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; border-radius:10px; padding:0.55rem 0.8rem; margin-bottom:0.9rem; font-size:0.84rem;">{{ session('status') }}</div>
    @endif

    {{-- Scrollable body --}}
    <div class="rv-scroll">

        {{-- Top: Personal + Family (left) | Contact (right) --}}
        <div class="rv-top">
            <div class="rv-col">

                {{-- Personal --}}
                <div class="rv-card">
                    <div class="rv-chead">
                        <div class="rv-ctitle">
                            <span class="rv-cicon"><svg class="rv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                            Personal Information
                        </div>
                        <a href="{{ route('public.apply.show', $term->id) }}" class="rv-edit">Edit</a>
                    </div>
                    <div class="rv-row"><span class="rv-lbl">Full Name</span><span class="rv-val">{{ $studentFull }}</span></div>
                    <div class="rv-row"><span class="rv-lbl">Date of Birth</span><span class="rv-val">{{ $student->date_of_birth ? \Illuminate\Support\Carbon::parse($student->date_of_birth)->format('M d, Y') : '—' }}</span></div>
                    <div class="rv-row"><span class="rv-lbl">Gender</span><span class="rv-val">{{ ucfirst($student->gender ?? '—') }}</span></div>
                </div>

                {{-- Family & Emergency --}}
                <div class="rv-card">
                    <div class="rv-chead">
                        <div class="rv-ctitle">
                            <span class="rv-cicon"><svg class="rv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                            Family &amp; Emergency Contact
                        </div>
                        <a href="{{ route('public.apply.family', $term->id) }}" class="rv-edit">Edit</a>
                    </div>

                    <div class="rv-sub">Parents / Guardians</div>
                    @if ($parents->isEmpty())
                        <p style="font-size:0.82rem; color:#94a3b8; font-style:italic;">No parents or guardians on record.</p>
                    @else
                        <div style="display:flex; flex-direction:column; gap:0.4rem;">
                            @foreach ($parents as $p)
                                <div class="rv-mini">
                                    <div style="font-weight:700;">
                                        {{ trim(($p->first_name ?? '').' '.($p->middle_name ?? '').' '.($p->last_name ?? '')) ?: '—' }}
                                        @if ($p->relationship)<span style="font-size:0.72rem; color:#64748b; font-weight:400;">({{ ucfirst($p->relationship) }})</span>@endif
                                        @if ($p->is_primary)<span style="margin-left:0.25rem; font-size:0.62rem; padding:0.1rem 0.35rem; border-radius:5px; background:#e0e7ff; color:#4338ca; font-weight:800;">PRIMARY</span>@endif
                                    </div>
                                    @php $pmeta = array_values(array_filter([$p->occupation ?? null, $p->employer ?? null, $p->mobile_number ?? null, $p->email ?? null], fn ($v) => filled($v))); @endphp
                                    <div style="font-size:0.72rem; color:#64748b;">{{ $pmeta ? implode(' · ', $pmeta) : '—' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="rv-sub">Emergency Contact</div>
                    @if (! $emergencyContact)
                        <p style="font-size:0.82rem; color:#94a3b8; font-style:italic;">No emergency contact on record.</p>
                    @else
                        <div class="rv-row"><span class="rv-lbl">Name</span><span class="rv-val">{{ trim(($emergencyContact->first_name ?? '').' '.($emergencyContact->last_name ?? '')) ?: '—' }}</span></div>
                        <div class="rv-row"><span class="rv-lbl">Relationship</span><span class="rv-val">{{ ucfirst($emergencyContact->relationship ?? '—') }}</span></div>
                        <div class="rv-row"><span class="rv-lbl">Mobile</span><span class="rv-val">{{ $emergencyContact->mobile_number ?? '—' }}</span></div>
                        @if ($emergencyContact->email)<div class="rv-row"><span class="rv-lbl">Email</span><span class="rv-val">{{ $emergencyContact->email }}</span></div>@endif
                        @if ($emergencyContact->address)<div class="rv-row"><span class="rv-lbl">Address</span><span class="rv-val">{{ $emergencyContact->address }}</span></div>@endif
                    @endif
                </div>
            </div>

            <div class="rv-col">
                {{-- Contact --}}
                <div class="rv-card">
                    <div class="rv-chead">
                        <div class="rv-ctitle">
                            <span class="rv-cicon"><svg class="rv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
                            Contact Details
                        </div>
                        <a href="{{ route('public.apply.step2', $term->id) }}" class="rv-edit">Edit</a>
                    </div>
                    <div class="rv-row"><span class="rv-lbl">Email</span><span class="rv-val">{{ $student->email ?? $student->user->email ?? '—' }}</span></div>
                    <div class="rv-row"><span class="rv-lbl">Mobile</span><span class="rv-val">{{ $student->mobile_number ?? $student->phone ?? '—' }}</span></div>
                    <div class="rv-row"><span class="rv-lbl">Address</span><span class="rv-val">{{ $addr1 ?: '—' }}</span></div>
                    @if ($addr2)<div class="rv-row"><span class="rv-lbl">Address Line 2</span><span class="rv-val">{{ $addr2 }}</span></div>@endif
                    <div class="rv-row"><span class="rv-lbl">Barangay</span><span class="rv-val">{{ $barangay ?: '—' }}</span></div>
                    <div class="rv-row"><span class="rv-lbl">City / Municipality</span><span class="rv-val">{{ $city ?: '—' }}</span></div>
                    <div class="rv-row"><span class="rv-lbl">Province</span><span class="rv-val">{{ $province ?: '—' }}</span></div>
                    <div class="rv-row"><span class="rv-lbl">Region</span><span class="rv-val">{{ $region ?: '—' }}</span></div>
                    <div class="rv-row"><span class="rv-lbl">Zip Code</span><span class="rv-val">{{ $zip ?: '—' }}</span></div>
                    <div class="rv-row"><span class="rv-lbl">Country</span><span class="rv-val">{{ $country ?: '—' }}</span></div>
                </div>
            </div>
        </div>

        {{-- Three: Pathway | Academic | Health --}}
        <div class="rv-three">

            {{-- Learning Pathway --}}
            <div class="rv-card">
                <div class="rv-chead">
                    <div class="rv-ctitle">
                        <span class="rv-cicon"><svg class="rv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="19" r="3"/><circle cx="18" cy="5" r="3"/><path d="M9 19h6a4 4 0 0 0 0-8H9a4 4 0 0 1 0-8h6"/></svg></span>
                        Learning Pathway
                    </div>
                    <a href="{{ route('public.apply.pathway', $term->id) }}" class="rv-edit">Edit</a>
                </div>
                <div class="rv-row"><span class="rv-lbl">Education Level</span><span class="rv-val">{{ $rootLevel?->name ?? '—' }}</span></div>
                @if (! empty($pathLabel))<div class="rv-row"><span class="rv-lbl">Path</span><span class="rv-val">{{ $pathLabel }}</span></div>@endif
                @if ($program)<div class="rv-row"><span class="rv-lbl">Programme</span><span class="rv-val">{{ ($program->code ? $program->code.' — ' : '').$program->name }}</span></div>@endif
                @if (! empty($pathway['year_level']))<div class="rv-row"><span class="rv-lbl">Year Level</span><span class="rv-val">{{ $pathway['year_level'] }}</span></div>@endif
                <div class="rv-row"><span class="rv-lbl">Modality</span><span class="rv-val">{{ $modality?->name ?? '—' }}</span></div>
                <div class="rv-row"><span class="rv-lbl">Student Type</span><span class="rv-val">@if ($modality && $modality->code === 'async_online')<em style="color:#94a3b8; font-weight:400;">N/A — Async</em>@else{{ ucfirst($pathway['student_type'] ?? '—') }}@endif</span></div>
            </div>

            {{-- Academic Background --}}
            <div class="rv-card">
                <div class="rv-chead">
                    <div class="rv-ctitle">
                        <span class="rv-cicon"><svg class="rv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span>
                        Academic Background
                    </div>
                    @if (! $skipped)<a href="{{ route('public.apply.academic', $term->id) }}" class="rv-edit">Edit</a>@endif
                </div>
                @if ($skipped)
                    <p style="font-size:0.82rem; color:#94a3b8; font-style:italic;">Skipped — Regular students reuse their prior record.</p>
                @elseif ($backgrounds->isEmpty())
                    <p style="font-size:0.82rem; color:#94a3b8; font-style:italic;">No academic backgrounds entered.</p>
                @else
                    <div style="display:flex; flex-direction:column; gap:0.4rem;">
                        @foreach ($backgrounds as $bg)
                            @php $bmeta = array_values(array_filter([
                                ($bg->year_started ?? '?').' – '.($bg->year_ended ?? '?'),
                                $bg->school_address ?? null,
                                $bg->gpa ? 'GPA: '.$bg->gpa : null,
                                $bg->honors ?? null,
                            ], fn ($v) => filled($v))); @endphp
                            <div class="rv-mini">
                                <div style="font-weight:700;">{{ $bg->school_name }} <span style="font-size:0.72rem; color:#64748b; font-weight:400;">({{ ucfirst(str_replace('_', ' ', $bg->education_level)) }})</span></div>
                                <div style="font-size:0.72rem; color:#64748b;">{{ implode(' · ', $bmeta) }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Health Information --}}
            <div class="rv-card">
                <div class="rv-chead">
                    <div class="rv-ctitle">
                        <span class="rv-cicon"><svg class="rv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg></span>
                        Health Information
                    </div>
                    <a href="{{ route('public.apply.health', $term->id) }}" class="rv-edit">Edit</a>
                </div>
                @if (! $health)
                    <div style="display:flex; align-items:center; gap:0.5rem; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:9px; padding:0.5rem 0.7rem; font-size:0.82rem; color:#065f46;">
                        <svg class="rv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                        No health information on record.
                    </div>
                @else
                    <div class="rv-row"><span class="rv-lbl">Medical condition</span><span class="rv-val">{{ $health->has_medical_condition ? 'Yes' : 'No' }}</span></div>
                    @if ($health->has_medical_condition && $conditionsList)<div class="rv-row"><span class="rv-lbl">Conditions</span><span class="rv-val">{{ $conditionsList }}</span></div>@endif
                    @if ($health->allergies)<div class="rv-row"><span class="rv-lbl">Allergies</span><span class="rv-val">{{ $health->allergies }}</span></div>@endif
                    <div class="rv-row"><span class="rv-lbl">Maintenance medication</span><span class="rv-val">{{ $health->takes_maintenance_medication ? 'Yes' : 'No' }}</span></div>
                    @if ($health->blood_type)<div class="rv-row"><span class="rv-lbl">Blood type</span><span class="rv-val">{{ $health->blood_type === 'unknown' ? 'Unknown' : $health->blood_type }}</span></div>@endif
                    <div class="rv-row"><span class="rv-lbl">PWD</span><span class="rv-val">{{ $health->is_pwd ? 'Yes' : 'No' }}</span></div>
                @endif
            </div>
        </div>

        {{-- Confirmed Financial Assessment: Total School Fees + Live Computation --}}
        <div class="rv-fin">
            <div class="rv-card">
                <div class="rv-chead">
                    <div class="rv-ctitle"><span class="rv-cicon"><svg class="rv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg></span> Total School Fees</div>
                </div>
                @forelse ($fees['items'] as $it)
                    <div class="rv-row"><span class="rv-lbl">{{ $it->label }}</span><span class="rv-val">{{ $peso($it->amount) }}</span></div>
                @empty
                    <p style="font-size:0.82rem; color:#94a3b8; font-style:italic;">No fees configured for this program/grade yet.</p>
                @endforelse
                <div class="rv-row" style="border-top:2px solid #e2e8f0; margin-top:0.2rem;"><span style="font-weight:800; color:#1e293b;">TOTAL</span><span style="font-weight:800; color:#4338ca; font-size:1.02rem;">{{ $peso($fees['total']) }}</span></div>
                @if ($scholarship ?? null)
                    <div class="rv-row"><span class="rv-lbl" style="color:#047857;">{{ $scholarship['label'] }} ({{ rtrim(rtrim(number_format($scholarship['percent'], 2), '0'), '.') }}% {{ $scholarship['apply_to'] === 'tuition' ? 'of tuition' : 'of total' }})</span><span class="rv-val" style="color:#047857;">− {{ $peso($scholarship['amount']) }}</span></div>
                    <div class="rv-row" style="border-top:2px solid #e2e8f0; margin-top:0.2rem;"><span style="font-weight:800; color:#1e293b;">NET PAYABLE</span><span style="font-weight:800; color:#047857; font-size:1.02rem;">{{ $peso($netTotal) }}</span></div>
                @endif
            </div>

            <div class="rv-card">
                <div class="rv-chead">
                    <div class="rv-ctitle"><span class="rv-cicon"><svg class="rv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/></svg></span> Live Computation</div>
                </div>
                @if ($financialComp)
                    <div style="background:#eef2ff; border:1px solid #e0e7ff; border-radius:9px; padding:0.45rem 0.7rem; margin-bottom:0.5rem; font-weight:700; color:#4338ca; font-size:0.84rem;">Selected Plan: {{ $financialComp['title'] }}</div>
                    <div class="rv-row"><span class="rv-lbl">Total School Fees</span><span class="rv-val">{{ $peso($fees['total']) }}</span></div>
                    @if (($scholarship['amount'] ?? 0) > 0)<div class="rv-row"><span class="rv-lbl" style="color:#047857;">Scholarship</span><span class="rv-val" style="color:#047857;">− {{ $peso($scholarship['amount']) }}</span></div>@endif
                    <div class="rv-row"><span class="rv-lbl">Cash Discount</span><span class="rv-val">{{ $peso($financialComp['discount']) }}</span></div>
                    <div class="rv-row"><span class="rv-lbl">Interest</span><span class="rv-val">{{ $peso($financialComp['interest']) }}</span></div>
                    <div class="rv-row"><span class="rv-lbl">Downpayment</span><span class="rv-val" style="color:#4338ca;">{{ $peso($financialComp['downpayment']) }}</span></div>
                    <div class="rv-row"><span class="rv-lbl">Remaining Balance</span><span class="rv-val" style="color:#4338ca;">{{ $peso($financialComp['remaining']) }}</span></div>
                    <div class="rv-row"><span class="rv-lbl" style="font-weight:700; color:#334155;">{{ $financialComp['num_installments'] > 1 ? $financialComp['frequency_label'].' Installment' : 'Single Installment' }}</span><span class="rv-val" style="color:#4338ca; font-weight:800;">{{ $peso($financialComp['per_installment']) }}</span></div>
                    <div class="rv-row"><span class="rv-lbl">No. of Installments</span><span class="rv-val">{{ $financialComp['num_installments'] }}</span></div>
                    <div class="rv-row"><span style="font-weight:800; color:#1e293b;">Total Amount Due</span><span style="font-weight:800; color:#4338ca;">{{ $peso($financialComp['total_due']) }}</span></div>
                @else
                    <p style="font-size:0.82rem; color:#94a3b8; font-style:italic;">No payment plan selected.</p>
                @endif
            </div>
        </div>

        @if ($financialComp && count($financialComp['schedule']))
        <div class="rv-card" style="margin-bottom:1rem;">
            <div class="rv-chead">
                <div class="rv-ctitle"><span class="rv-cicon"><svg class="rv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span> Payment Schedule</div>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:0.82rem;">
                    <thead><tr style="background:#4f46e5; color:#fff;">
                        <th style="text-align:left; padding:0.5rem 0.6rem; border-radius:0.4rem 0 0 0.4rem;">Due Date</th>
                        <th style="text-align:left; padding:0.5rem 0.6rem;">Description</th>
                        <th style="text-align:right; padding:0.5rem 0.6rem; border-radius:0 0.4rem 0.4rem 0;">Amount (PHP)</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($financialComp['schedule'] as $r)
                            <tr style="border-bottom:1px solid #eef2f7;">
                                <td style="padding:0.45rem 0.6rem; color:#475569;">{{ $r['due_date'] }}</td>
                                <td style="padding:0.45rem 0.6rem; color:#334155;">{{ $r['description'] }}</td>
                                <td style="padding:0.45rem 0.6rem; text-align:right; font-weight:600; color:#1e293b;">{{ $peso($r['amount']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ===== Certification & Legality ===== --}}
        <div class="rv-card" style="margin-bottom:1rem;">
            <div class="rv-chead"><div class="rv-ctitle"><span class="rv-cicon"><svg class="rv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg></span> Certification &amp; Legality</div></div>

            {{-- Applicant selector: who is certifying. Mutually exclusive; drives
                 the name shown in the certification text and on the signature page. --}}
            <div style="display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap; margin:0.2rem 0 1rem; font-size:0.85rem; color:#16223e; font-weight:700;">
                <span>Applicant:</span>
                <label style="display:inline-flex; align-items:center; gap:0.4rem; cursor:pointer; font-weight:600;">
                    <input type="checkbox" :checked="certifier==='student'" @change="certifier = (certifier==='student' ? '' : 'student')" style="width:1rem; height:1rem;"> Student
                </label>
                <label style="display:inline-flex; align-items:center; gap:0.4rem; cursor:pointer; font-weight:600;">
                    <input type="checkbox" :checked="certifier==='guardian'" @change="certifier = (certifier==='guardian' ? '' : 'guardian')" style="width:1rem; height:1rem;"> Legal guardian
                </label>
                <input type="hidden" name="certified_by" form="reviewSubmitForm" :value="certifier">
            </div>

            <div class="rv-sub" style="color:#16223e;">Acknowledgement of Accuracy and Truthfulness</div>
            <p x-show="certifier !== 'guardian'" style="font-size:0.82rem; color:#475569; line-height:1.5; margin-bottom:0.5rem;">
                {{ $ackP1pre }}<span x-text="certifierName" style="font-weight:700; color:#16223e;"></span>{{ $ackP1post }}
            </p>
            <p x-show="certifier === 'guardian'" x-cloak style="font-size:0.82rem; color:#475569; line-height:1.5; margin-bottom:0.5rem;">{!! $ackGuardianInner !!}</p>
            @foreach ($ackParas as $para)
                <p style="font-size:0.82rem; color:#475569; line-height:1.5; margin-bottom:0.5rem;">{{ $para }}</p>
            @endforeach
            <label style="display:flex; align-items:flex-start; gap:0.5rem; font-size:0.82rem; color:#334155; margin:0.3rem 0 0.8rem; cursor:pointer;">
                <input type="checkbox" x-model="ack" name="acknowledged_accuracy" value="1" form="reviewSubmitForm" style="margin-top:0.2rem; width:1rem; height:1rem; flex:0 0 auto;"> {{ $ackCheck }}
            </label>

            <div class="rv-sub" style="color:#16223e; border-top:1px solid #f1f5f9; padding-top:0.7rem;">Data Privacy Consent (RA 10173)</div>
            @foreach ($privacyParas as $para)
                <p style="font-size:0.82rem; color:#475569; line-height:1.5; margin-bottom:0.5rem;">{{ $para }}</p>
            @endforeach
            <label style="display:flex; align-items:flex-start; gap:0.5rem; font-size:0.82rem; color:#334155; margin:0.3rem 0 0.8rem; cursor:pointer;">
                <input type="checkbox" x-model="privacy" name="data_privacy_consent" value="1" form="reviewSubmitForm" style="margin-top:0.2rem; width:1rem; height:1rem; flex:0 0 auto;"> {{ $privacyCheck }}
            </label>

            <div class="rv-sub" style="color:#16223e; border-top:1px solid #f1f5f9; padding-top:0.7rem;">Electronic Confirmation</div>
            <p style="font-size:0.82rem; color:#475569; line-height:1.5;">{{ $elecConfirm }}</p>

            <div style="display:flex; gap:2rem; margin-top:1.8rem; flex-wrap:wrap;">
                <div style="flex:1; min-width:9rem; text-align:center;">
                    <div style="min-height:1.3rem; font-weight:700; color:#16223e;">{{ $studentFull }}</div>
                    <div style="border-top:1px solid #16223e; margin-top:0.2rem; padding-top:0.25rem; font-size:0.76rem; color:#64748b;">Student Applicant</div>
                </div>
                <div style="flex:1; min-width:9rem; text-align:center;">
                    <div style="min-height:1.3rem; font-weight:700; color:#16223e;"><span x-show="certifier==='guardian'">{{ $guardianName }}</span></div>
                    <div x-show="certifier === 'guardian'" x-cloak style="border-top:1px solid #16223e; margin-top:0.2rem; padding-top:0.25rem; font-size:0.76rem; color:#64748b;">Guardian</div>
                </div>
                <div style="flex:1; min-width:9rem; text-align:center;">
                    <div style="min-height:1.3rem; color:#16223e;">{{ now()->format('F d, Y') }}</div>
                    <div style="border-top:1px solid #16223e; margin-top:0.2rem; padding-top:0.25rem; font-size:0.76rem; color:#64748b;">Date</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="rv-footer">
        <a href="{{ route('public.apply.health', $term->id) }}" style="padding:0.6rem 1.2rem; border-radius:10px; border:1px solid #cbd5e1; color:#334155; font-weight:700; text-decoration:none;">← Back</a>
        <div style="display:flex; align-items:center; gap:0.6rem;">
            <button type="button" onclick="openEnrolmentPreview()" style="padding:0.62rem 1.2rem; border-radius:10px; border:1px solid #c7d2fe; background:#fff; color:#4338ca; font-weight:700; display:inline-flex; align-items:center; gap:0.4rem; cursor:pointer;">
                <svg class="rv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                Preview Application
            </button>
            <form method="POST" action="{{ route('public.apply.submit', $term->id) }}" id="reviewSubmitForm" style="margin:0;">
                @csrf
                <button type="submit" x-bind:disabled="!certifier || !ack || !privacy" :style="(!certifier || !ack || !privacy) ? 'opacity:0.5; cursor:not-allowed;' : ''" title="Please select an applicant and agree to the Acknowledgement and Data Privacy Consent first" style="padding:0.7rem 1.5rem; border-radius:10px; background:#059669; color:#fff; font-weight:800; border:none; box-shadow:0 8px 16px -6px rgba(5,150,105,.5); cursor:pointer;">Submit Application ✓</button>
            </form>
        </div>
    </div>
</div>

{{-- ============================================================== --}}
{{-- Application Preview Modal (2-page printable letterhead)          --}}
{{-- ============================================================== --}}
<div id="enrolmentPreviewModal"
     class="hidden fixed inset-0 z-[9999] bg-slate-900/70 backdrop-blur-sm overflow-y-auto py-8 px-4"
     onclick="if(event.target===this) closeEnrolmentPreview()">

    <div class="mx-auto bg-slate-100 rounded-xl shadow-2xl overflow-hidden" style="width: max-content; max-width: 100%;">

        {{-- Modal toolbar --}}
        <div class="flex items-center justify-between px-5 py-3 bg-white border-b border-slate-200 sticky top-0 z-10">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6"/></svg>
                <div class="font-extrabold text-slate-800 text-sm">Application Preview</div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="printEnrolmentPreview()" class="text-xs font-bold text-slate-700 hover:bg-slate-100 px-3 py-1.5 rounded-md inline-flex items-center gap-1.5 border border-slate-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/></svg>
                    Print
                </button>
                <button type="button" onclick="printEnrolmentPreview()" class="text-xs font-bold text-slate-700 hover:bg-slate-100 px-3 py-1.5 rounded-md inline-flex items-center gap-1.5 border border-slate-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
                    Download PDF
                </button>
                <button type="button" onclick="closeEnrolmentPreview()" class="text-slate-400 hover:text-slate-700 p-1">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- A4-styled paper --}}
        <div class="p-6">
            <div id="enrolmentPreviewPaper" class="enrolment-paper bg-white shadow-md rounded-md"
                 @if ($bgUrl) style="background-image:url('{{ $bgUrl }}'); background-size:100% auto; background-repeat:no-repeat; background-position:top center; -webkit-print-color-adjust:exact; print-color-adjust:exact;" @endif>
                <div class="overflow-x-auto">
                <table class="enrolment-print-table" style="width:100%; border-collapse:collapse;">

                    {{-- Repeating header (school branding from School Settings → School tab).
                         An uploaded header image takes over the full paper width and
                         scales by ratio; otherwise the default coloured band shows. --}}
                    <thead><tr><td style="padding:0;">
                        @if ($headerUrl)
                            {{-- Image scales (ratio preserved) to fit the reserved header space --}}
                            <div style="height:{{ $headerSpace }}in; width:100%;">
                                <img src="{{ $headerUrl }}" alt="header" style="display:block; width:100%; height:100%; object-fit:contain; object-position:center;">
                            </div>
                        @else
                            <div class="lh-bar lh-header" style="min-height:{{ $headerSpace }}in;">
                                @if ($logoUrl)<img src="{{ $logoUrl }}" alt="logo" style="height:62px; width:auto; flex:0 0 auto;">@endif
                                <div class="lh-text" style="line-height:1.2;">
                                    <div style="font-family:Georgia,'Times New Roman',serif; font-size:1.35rem; font-weight:800;">{{ $schoolName }}</div>
                                    @if ($motto)<div style="color:#cda434; font-style:italic; font-size:0.9rem; margin-top:0.15rem;">{{ $motto }}</div>@endif
                                </div>
                            </div>
                        @endif
                        {{-- Breathing room so body content never kisses the repeated header --}}
                        <div style="height:7mm;"></div>
                    </td></tr></thead>

                    {{-- Repeating footer (school contact). An uploaded footer image
                         takes over the full paper width; otherwise the default band shows. --}}
                    <tfoot><tr><td style="padding:0;">
                        <div style="height:7mm;"></div>
                        @if ($footerUrl)
                            {{-- Image scales (ratio preserved) to fit the reserved footer space --}}
                            <div style="height:{{ $footerSpace }}in; width:100%;">
                                <img src="{{ $footerUrl }}" alt="footer" style="display:block; width:100%; height:100%; object-fit:contain; object-position:center;">
                            </div>
                        @else
                            <div class="lh-bar lh-footer lh-text" style="min-height:{{ $footerSpace }}in;">
                                @if ($schoolAddr)<span class="lh-fitem"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>{{ $schoolAddr }}</span>@endif
                                @if ($schoolPhone)<span class="lh-fitem"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.08 4.18 2 2 0 0 1 5.06 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>{{ $schoolPhone }}</span>@endif
                                @if ($schoolMail)<span class="lh-fitem"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>{{ $schoolMail }}</span>@endif
                                @if ($schoolWeb)<span class="lh-fitem"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></svg>{{ $schoolWeb }}</span>@endif
                            </div>
                        @endif
                    </td></tr></tfoot>

                    <tbody><tr><td style="padding:0;">

                        {{-- ================= PAGE 1 ================= --}}
                        <div class="pp-body">

                            {{-- Title + Ready box --}}
                            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem;">
                                <div>
                                    <h2 style="font-size:1.5rem; font-weight:800; color:#16223e; letter-spacing:0.02em;">APPLICATION PREVIEW</h2>
                                    <p style="font-size:0.82rem; color:#64748b; margin-top:0.2rem; max-width:30rem;">Please review all the information below carefully. Ensure all details are correct before submitting your application.</p>
                                    <div style="font-size:0.72rem; font-weight:800; color:#16223e; text-transform:uppercase; letter-spacing:0.06em; margin-top:0.55rem;">Application Summary <span style="color:#64748b; font-weight:600; text-transform:none;">· Academic Year {{ $ayLabel }}</span></div>
                                </div>
                                <div style="background:#eef2ff; border:1px solid #e0e7ff; border-radius:12px; padding:0.7rem 0.9rem; max-width:18rem; display:flex; gap:0.55rem; flex:0 0 auto;">
                                    <span style="width:26px; height:26px; border-radius:50%; background:#4f46e5; color:#fff; display:flex; align-items:center; justify-content:center; flex:0 0 auto;"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg></span>
                                    <div>
                                        <div style="font-weight:800; color:#3730a3; font-size:0.82rem;">Ready to submit?</div>
                                        <p style="font-size:0.74rem; color:#6366f1; margin-top:0.1rem;">If everything looks good, you can now submit your application.</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Cards: Personal + Family (left) | Contact (right) --}}
                            <div class="pp-top">
                                <div class="pp-col">
                                    <div class="pp-card">
                                        <div class="pp-ctitle"><span class="pp-cicon"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span> 1. PERSONAL INFORMATION</div>
                                        <div class="pp-row"><span class="pp-lbl">Full Name</span><span class="pp-val">{{ $studentFull }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">Date of Birth</span><span class="pp-val">{{ $student->date_of_birth ? \Illuminate\Support\Carbon::parse($student->date_of_birth)->format('M d, Y') : '—' }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">Gender</span><span class="pp-val">{{ ucfirst($student->gender ?? '—') }}</span></div>
                                    </div>

                                    <div class="pp-card">
                                        <div class="pp-ctitle"><span class="pp-cicon"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span> 3. FAMILY &amp; EMERGENCY CONTACT</div>
                                        <div class="pp-sub">Parents / Guardians</div>
                                        @forelse ($parents as $p)
                                            <div style="margin-bottom:0.4rem;">
                                                <div class="pp-row"><span class="pp-lbl">Name</span><span class="pp-val">{{ trim(($p->first_name ?? '').' '.($p->last_name ?? '')) ?: '—' }}@if ($p->relationship) <span style="color:#94a3b8; font-weight:400;">({{ ucfirst($p->relationship) }})</span>@endif @if ($p->is_primary)<span style="font-size:0.6rem; color:#4338ca; font-weight:800;">[PRIMARY]</span>@endif</span></div>
                                                @if ($p->mobile_number)<div class="pp-row"><span class="pp-lbl">Contact</span><span class="pp-val">{{ $p->mobile_number }}</span></div>@endif
                                                @if ($p->email)<div class="pp-row"><span class="pp-lbl">Email</span><span class="pp-val">{{ $p->email }}</span></div>@endif
                                                @php $po = array_values(array_filter([$p->occupation ?? null, $p->employer ?? null], fn ($v) => filled($v))); @endphp
                                                @if ($po)<div class="pp-row"><span class="pp-lbl">Occupation</span><span class="pp-val">{{ implode(' · ', $po) }}</span></div>@endif
                                            </div>
                                        @empty
                                            <p style="font-size:0.78rem; color:#94a3b8; font-style:italic;">No parents or guardians on record.</p>
                                        @endforelse

                                        <div class="pp-sub">Emergency Contact</div>
                                        @if (! $emergencyContact)
                                            <p style="font-size:0.78rem; color:#94a3b8; font-style:italic;">No emergency contact on record.</p>
                                        @else
                                            <div class="pp-row"><span class="pp-lbl">Name</span><span class="pp-val">{{ trim(($emergencyContact->first_name ?? '').' '.($emergencyContact->last_name ?? '')) ?: '—' }}</span></div>
                                            <div class="pp-row"><span class="pp-lbl">Relationship</span><span class="pp-val">{{ ucfirst($emergencyContact->relationship ?? '—') }}</span></div>
                                            <div class="pp-row"><span class="pp-lbl">Mobile</span><span class="pp-val">{{ $emergencyContact->mobile_number ?? '—' }}</span></div>
                                            @if ($emergencyContact->email)<div class="pp-row"><span class="pp-lbl">Email</span><span class="pp-val">{{ $emergencyContact->email }}</span></div>@endif
                                            @if ($emergencyContact->address)<div class="pp-row"><span class="pp-lbl">Address</span><span class="pp-val">{{ $emergencyContact->address }}</span></div>@endif
                                        @endif
                                    </div>
                                </div>

                                <div class="pp-col">
                                    <div class="pp-card">
                                        <div class="pp-ctitle"><span class="pp-cicon"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.08 4.18 2 2 0 0 1 5.06 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/></svg></span> 2. CONTACT DETAILS</div>
                                        <div class="pp-row"><span class="pp-lbl">Email</span><span class="pp-val">{{ $student->email ?? $student->user->email ?? '—' }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">Mobile</span><span class="pp-val">{{ $student->mobile_number ?? $student->phone ?? '—' }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">Address</span><span class="pp-val">{{ $addr1 ?: '—' }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">Barangay</span><span class="pp-val">{{ $barangay ?: '—' }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">City / Municipality</span><span class="pp-val">{{ $city ?: '—' }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">Province</span><span class="pp-val">{{ $province ?: '—' }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">Region</span><span class="pp-val">{{ $region ?: '—' }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">Zip Code</span><span class="pp-val">{{ $zip ?: '—' }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">Country</span><span class="pp-val">{{ $country ?: '—' }}</span></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Cards 4-6 --}}
                            <div class="pp-three">
                                <div class="pp-card">
                                    <div class="pp-ctitle"><span class="pp-cicon"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span> 4. LEARNING PATHWAY</div>
                                    <div class="pp-row"><span class="pp-lbl">Education Level</span><span class="pp-val">{{ $rootLevel?->name ?? '—' }}</span></div>
                                    @if (! empty($pathLabel))<div class="pp-row"><span class="pp-lbl">Path</span><span class="pp-val">{{ $pathLabel }}</span></div>@endif
                                    @if ($program)<div class="pp-row"><span class="pp-lbl">Programme</span><span class="pp-val">{{ ($program->code ? $program->code.' — ' : '').$program->name }}</span></div>@endif
                                    @if (! empty($pathway['year_level']))<div class="pp-row"><span class="pp-lbl">Year Level</span><span class="pp-val">{{ $pathway['year_level'] }}</span></div>@endif
                                    <div class="pp-row"><span class="pp-lbl">Modality</span><span class="pp-val">{{ $modality?->name ?? '—' }}</span></div>
                                    <div class="pp-row"><span class="pp-lbl">Student Type</span><span class="pp-val">{{ ($modality && $modality->code === 'async_online') ? 'N/A — Async' : ucfirst($pathway['student_type'] ?? '—') }}</span></div>
                                </div>
                                <div class="pp-card">
                                    <div class="pp-ctitle"><span class="pp-cicon"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></span> 5. ACADEMIC BACKGROUND</div>
                                    @if ($skipped)
                                        <p style="font-size:0.78rem; color:#94a3b8; font-style:italic;">Skipped — reuse prior record.</p>
                                    @elseif ($backgrounds->isEmpty())
                                        <p style="font-size:0.78rem; color:#94a3b8; font-style:italic;">No academic backgrounds entered.</p>
                                    @else
                                        @foreach ($backgrounds as $bg)
                                            @php $bm = array_values(array_filter([($bg->year_started ?? '?').' – '.($bg->year_ended ?? '?'), $bg->school_address ?? null, $bg->gpa ? 'GPA: '.$bg->gpa : null, $bg->honors ?? null], fn ($v) => filled($v))); @endphp
                                            <div style="margin-top:0.25rem;">
                                                <div style="font-weight:700; color:#1e293b; font-size:0.82rem;">{{ $bg->school_name }}</div>
                                                <div style="font-size:0.72rem; color:#64748b;">({{ ucfirst(str_replace('_', ' ', $bg->education_level)) }})</div>
                                                <div style="font-size:0.72rem; color:#64748b; margin-top:0.15rem;">{{ implode(' · ', $bm) }}</div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                <div class="pp-card">
                                    <div class="pp-ctitle"><span class="pp-cicon"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg></span> 6. HEALTH INFORMATION</div>
                                    @if (! $health)
                                        <div style="display:flex; align-items:center; gap:0.5rem; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:9px; padding:0.5rem 0.7rem; font-size:0.78rem; color:#065f46; margin-top:0.3rem;"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg> No health information on record.</div>
                                    @else
                                        <div class="pp-row"><span class="pp-lbl">Medical condition</span><span class="pp-val">{{ $health->has_medical_condition ? 'Yes' : 'No' }}</span></div>
                                        @if ($health->has_medical_condition && $conditionsList)<div class="pp-row"><span class="pp-lbl">Conditions</span><span class="pp-val">{{ $conditionsList }}</span></div>@endif
                                        <div class="pp-row"><span class="pp-lbl">Maintenance medication</span><span class="pp-val">{{ $health->takes_maintenance_medication ? 'Yes' : 'No' }}</span></div>
                                        @if ($health->blood_type)<div class="pp-row"><span class="pp-lbl">Blood type</span><span class="pp-val">{{ $health->blood_type === 'unknown' ? 'Unknown' : $health->blood_type }}</span></div>@endif
                                        <div class="pp-row"><span class="pp-lbl">PWD</span><span class="pp-val">{{ $health->is_pwd ? 'Yes' : 'No' }}</span></div>
                                    @endif
                                </div>
                            </div>

                            {{-- Important reminders --}}
                            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:0.9rem 1.1rem; margin-top:0.5rem;" class="pp-card">
                                <div style="display:flex; align-items:center; gap:0.5rem; font-weight:800; color:#16223e; font-size:0.8rem; letter-spacing:0.04em; margin-bottom:0.5rem;"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg> IMPORTANT REMINDERS</div>
                                @foreach ([
                                    'Please ensure that all information provided is true and correct.',
                                    'Any false information may result in the rejection of your application.',
                                    'You can go back to edit your information if needed.',
                                ] as $rem)
                                    <div style="display:flex; gap:0.45rem; font-size:0.78rem; color:#475569; padding:0.12rem 0;"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex:0 0 auto;"><path d="M20 6 9 17l-5-5"/></svg>{{ $rem }}</div>
                                @endforeach
                            </div>
                        </div>

                        {{-- ================= PAGE 2 ================= --}}
                        <div class="pp-pagebreak"></div>
                        <div class="pp-body">

                            <h2 style="font-size:1.3rem; font-weight:800; color:#16223e; letter-spacing:0.02em; margin-bottom:0.8rem;">FINANCIAL ASSESSMENT</h2>

                            <div class="pp-top" style="margin-bottom:0.8rem;">
                                {{-- Total School Fees --}}
                                <div class="pp-card">
                                    <div class="pp-ctitle"><span class="pp-cicon"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg></span> TOTAL SCHOOL FEES</div>
                                    @forelse ($fees['items'] as $it)
                                        <div class="pp-row"><span class="pp-lbl">{{ $it->label }}</span><span class="pp-val">{{ $peso($it->amount) }}</span></div>
                                    @empty
                                        <p style="font-size:0.78rem; color:#94a3b8; font-style:italic;">No fees configured.</p>
                                    @endforelse
                                    <div class="pp-row" style="border-top:2px solid #e2e8f0;"><span style="font-weight:800; color:#16223e;">TOTAL</span><span style="font-weight:800; color:#4338ca;">{{ $peso($fees['total']) }}</span></div>
                                    @if ($scholarship ?? null)
                                        <div class="pp-row"><span class="pp-lbl" style="color:#047857;">{{ $scholarship['label'] }} ({{ rtrim(rtrim(number_format($scholarship['percent'], 2), '0'), '.') }}%)</span><span class="pp-val" style="color:#047857;">− {{ $peso($scholarship['amount']) }}</span></div>
                                        <div class="pp-row" style="border-top:2px solid #e2e8f0;"><span style="font-weight:800; color:#16223e;">NET PAYABLE</span><span style="font-weight:800; color:#047857;">{{ $peso($netTotal) }}</span></div>
                                    @endif
                                </div>

                                {{-- Live Computation --}}
                                <div class="pp-card">
                                    <div class="pp-ctitle"><span class="pp-cicon"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/></svg></span> LIVE COMPUTATION</div>
                                    @if ($financialComp)
                                        <div style="background:#eef2ff; border:1px solid #e0e7ff; border-radius:8px; padding:0.4rem 0.6rem; margin:0.3rem 0; font-weight:700; color:#4338ca; font-size:0.8rem;">Selected Plan: {{ $financialComp['title'] }}</div>
                                        <div class="pp-row"><span class="pp-lbl">Total School Fees</span><span class="pp-val">{{ $peso($fees['total']) }}</span></div>
                                        @if (($scholarship['amount'] ?? 0) > 0)<div class="pp-row"><span class="pp-lbl" style="color:#047857;">Scholarship</span><span class="pp-val" style="color:#047857;">− {{ $peso($scholarship['amount']) }}</span></div>@endif
                                        <div class="pp-row"><span class="pp-lbl">Cash Discount</span><span class="pp-val">{{ $peso($financialComp['discount']) }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">Interest</span><span class="pp-val">{{ $peso($financialComp['interest']) }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">Downpayment</span><span class="pp-val">{{ $peso($financialComp['downpayment']) }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">Remaining Balance</span><span class="pp-val">{{ $peso($financialComp['remaining']) }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">{{ $financialComp['num_installments'] > 1 ? $financialComp['frequency_label'].' Installment' : 'Single Installment' }}</span><span class="pp-val">{{ $peso($financialComp['per_installment']) }}</span></div>
                                        <div class="pp-row"><span class="pp-lbl">No. of Installments</span><span class="pp-val">{{ $financialComp['num_installments'] }}</span></div>
                                        <div class="pp-row" style="border-top:2px solid #e2e8f0;"><span style="font-weight:800; color:#16223e;">Total Amount Due</span><span style="font-weight:800; color:#4338ca;">{{ $peso($financialComp['total_due']) }}</span></div>
                                    @else
                                        <p style="font-size:0.78rem; color:#94a3b8; font-style:italic;">No payment plan selected.</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Payment Schedule --}}
                            @if ($financialComp && count($financialComp['schedule']))
                            <div class="pp-card" style="margin-bottom:0.8rem;">
                                <div class="pp-ctitle"><span class="pp-cicon"><svg class="lh-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span> PAYMENT SCHEDULE</div>
                                <div class="overflow-x-auto">
                                <table style="width:100%; border-collapse:collapse; font-size:0.78rem; margin-top:0.4rem;">
                                    <thead><tr style="background:#16223e; color:#fff;">
                                        <th style="text-align:left; padding:0.4rem 0.55rem;">Due Date</th>
                                        <th style="text-align:left; padding:0.4rem 0.55rem;">Description</th>
                                        <th style="text-align:right; padding:0.4rem 0.55rem;">Amount (PHP)</th>
                                    </tr></thead>
                                    <tbody>
                                        @foreach ($financialComp['schedule'] as $r)
                                            <tr style="border-bottom:1px solid #eef2f7;">
                                                <td style="padding:0.35rem 0.55rem; color:#475569;">{{ $r['due_date'] }}</td>
                                                <td style="padding:0.35rem 0.55rem; color:#334155;">{{ $r['description'] }}</td>
                                                <td style="padding:0.35rem 0.55rem; text-align:right; font-weight:600; color:#1e293b;">{{ $peso($r['amount']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                </div>
                            </div>
                            @endif

                        </div>

                        {{-- ================= PAGE 3 — CERTIFICATION & LEGALITY ================= --}}
                        <div class="pp-pagebreak"></div>
                        <div class="pp-body">
                            <h2 style="font-size:1.3rem; font-weight:800; color:#16223e; letter-spacing:0.02em; margin-bottom:0.8rem;">CERTIFICATION AND LEGALITY</h2>

                            <div class="pp-legal-h">Acknowledgement of Accuracy and Truthfulness</div>
                            <p class="pp-legal-p" x-show="certifier !== 'guardian'">{{ $ackP1pre }}<span x-text="certifierName" style="font-weight:700; color:#16223e;"></span>{{ $ackP1post }}</p>
                            <p class="pp-legal-p" x-show="certifier === 'guardian'" x-cloak>{!! $ackGuardianInner !!}</p>
                            @foreach ($ackParas as $para)<p class="pp-legal-p">{{ $para }}</p>@endforeach
                            <div class="pp-legal-check"><input type="checkbox" disabled :checked="ack" style="width:0.85rem; height:0.85rem; margin-top:0.12rem; flex:0 0 auto;"> <span>{{ $ackCheck }}</span></div>

                            <div class="pp-legal-h">Data Privacy Consent (RA 10173)</div>
                            @foreach ($privacyParas as $para)<p class="pp-legal-p">{{ $para }}</p>@endforeach
                            <div class="pp-legal-check"><input type="checkbox" disabled :checked="privacy" style="width:0.85rem; height:0.85rem; margin-top:0.12rem; flex:0 0 auto;"> <span>{{ $privacyCheck }}</span></div>

                            <div class="pp-legal-h">Electronic Confirmation</div>
                            <p class="pp-legal-p">{{ $elecConfirm }}</p>

                            {{-- Signatures: name above the line, title below --}}
                            <div style="display:flex; gap:2rem; margin-top:2.4rem;">
                                <div style="flex:1; text-align:center;">
                                    <div style="min-height:1.4rem; font-weight:700; color:#16223e;">{{ $studentFull }}</div>
                                    <div style="border-top:1px solid #16223e; margin-top:0.2rem; padding-top:0.3rem; font-size:0.76rem; color:#64748b;">Student Applicant</div>
                                </div>
                                <div style="flex:1; text-align:center;">
                                    <div style="min-height:1.4rem; font-weight:700; color:#16223e;"><span x-show="certifier==='guardian'">{{ $guardianName }}</span></div>
                                    <div x-show="certifier === 'guardian'" x-cloak style="border-top:1px solid #16223e; margin-top:0.2rem; padding-top:0.3rem; font-size:0.76rem; color:#64748b;">Guardian</div>
                                </div>
                                <div style="flex:1; text-align:center;">
                                    <div id="enrolmentPreviewSignDate" style="min-height:1.4rem; color:#16223e;">{{ now()->format('F d, Y') }}</div>
                                    <div style="border-top:1px solid #16223e; margin-top:0.2rem; padding-top:0.3rem; font-size:0.76rem; color:#64748b;">Date</div>
                                </div>
                            </div>
                        </div>

                    </td></tr></tbody>
                </table>
                </div>
            </div>
        </div>

        {{-- Modal footer with Cancel + Submit --}}
        <div class="flex items-center justify-end gap-3 px-5 py-4 bg-white border-t border-slate-200 sticky bottom-0">
            <button type="button" onclick="closeEnrolmentPreview()" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-bold hover:bg-slate-50">Cancel</button>
            <button type="submit" form="reviewSubmitForm" x-bind:disabled="!certifier || !ack || !privacy" :style="(!certifier || !ack || !privacy) ? 'opacity:0.5; cursor:not-allowed;' : ''" title="Please select an applicant and agree to the Acknowledgement and Data Privacy Consent first" class="px-7 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold shadow-lg inline-flex items-center gap-2">Submit Application ✓</button>
        </div>
    </div>
</div>
</div>{{-- end x-data legal wrapper --}}

<style>
/* A4 paper preview (on-screen) — full-bleed header/footer bars. */
.enrolment-paper {
    width: 210mm;
    min-height: 297mm;
    padding: 0;
    margin: 0 auto;
    box-sizing: border-box;
    font-family: ui-sans-serif, system-ui, 'Segoe UI', Arial, sans-serif;
    overflow: hidden;
}
.lh-bar { background:#16223e; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
.lh-header { display:flex; align-items:center; gap:1.1rem; padding:1.1rem 1.4rem; }
.lh-footer { display:flex; flex-wrap:wrap; align-items:center; gap:0.4rem 1.4rem; padding:0.85rem 1.4rem; font-size:0.76rem; color:#e2e8f0; }
.lh-fitem { display:inline-flex; align-items:center; gap:0.35rem; }
.lh-ico { width:15px; height:15px; flex:0 0 auto; }

.pp-body { padding: 6mm 11mm; }
.pp-pagebreak { break-before:page; page-break-before:always; height:0; }

.pp-card { border:1px solid #e2e8f0; border-radius:11px; padding:0.55rem 0.8rem; background:#fff; }
.pp-ctitle { display:flex; align-items:center; gap:0.5rem; font-weight:800; color:#16223e; font-size:0.73rem; letter-spacing:0.03em; margin-bottom:0.35rem; }
.pp-cicon { width:24px; height:24px; border-radius:7px; background:#eef2ff; color:#4f46e5; display:flex; align-items:center; justify-content:center; flex:0 0 auto; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
.pp-row { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:0.2rem 0; border-bottom:1px solid #f1f5f9; font-size:0.72rem; }
.pp-row:last-child { border-bottom:0; }
.pp-lbl { color:#64748b; }
.pp-val { font-weight:600; color:#1e293b; text-align:right; }
.pp-sub { font-size:0.62rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:#94a3b8; margin:0.35rem 0 0.2rem; }
.pp-top { display:grid; grid-template-columns:1fr 1fr; gap:0.7rem; margin-bottom:0.7rem; }
.pp-col { display:flex; flex-direction:column; gap:0.7rem; }
.pp-three { display:grid; grid-template-columns:1fr 1fr 1fr; gap:0.7rem; margin-bottom:0.7rem; }
.pp-legal-h { font-size:0.78rem; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:#16223e; margin:0.7rem 0 0.35rem; }
.pp-legal-p { font-size:0.78rem; color:#475569; line-height:1.5; margin-bottom:0.45rem; }
.pp-legal-check { display:flex; align-items:flex-start; gap:0.45rem; font-size:0.78rem; color:#334155; font-weight:600; margin:0.35rem 0 0.6rem; }

/* Print container only used while printing (cloned at print time) */
#enrolmentPrintArea { display: none; }

@media print {
    @page { size: A4; margin: 0; }
    html, body { background:#fff !important; margin:0 !important; padding:0 !important; }
    body.printing-enrolment > *:not(#enrolmentPrintArea) { display:none !important; }
    body.printing-enrolment #enrolmentPrintArea {
        display:block !important; position:static !important; width:auto !important; margin:0 !important; padding:0 !important; background:#fff !important;
    }
    body.printing-enrolment #enrolmentPrintArea .enrolment-paper {
        width:100% !important; min-height:0 !important; padding:0 !important; margin:0 !important; box-shadow:none !important; border-radius:0 !important; background:#fff !important;
    }
    .enrolment-print-table > thead { display: table-header-group; }
    .enrolment-print-table > tfoot { display: table-footer-group; }
    .pp-card { break-inside: avoid; page-break-inside: avoid; }
    .lh-bar, .pp-cicon { -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }
}
</style>

{{-- Body-level print container (populated at print time) --}}
<div id="enrolmentPrintArea" aria-hidden="true"></div>

<script>
    function openEnrolmentPreview() {
        var signDate = document.getElementById('enrolmentPreviewSignDate');
        if (signDate) {
            var now = new Date();
            var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
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
        var area = document.getElementById('enrolmentPrintArea');
        if (!area) { area = document.createElement('div'); area.id = 'enrolmentPrintArea'; }
        if (area.parentNode !== document.body) { document.body.appendChild(area); }
        area.innerHTML = '';
        area.appendChild(paper.cloneNode(true));

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
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeEnrolmentPreview(); });
</script>
@endsection
