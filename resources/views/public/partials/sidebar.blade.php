<style>
    .sidebar {
        width: 280px;
        min-width: 280px;
        padding: 40px 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background-color: #2c333d;
        height: 100vh;
        position: sticky;
        top: 0;
    }

    .logo-text {
        color: white;
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 35px;
        margin-top: 35px;
        margin-left: 3px;
        line-height: 1.2;
    }

    .nav-item {
        padding: 16px 20px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        background: #ffffff;
        color: #1e293b;
        border: none;
        transition: all 0.2s;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: left;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .nav-item.active {
        background: #5a57d6 !important;
        color: #ffffff !important;
        box-shadow: 0 10px 15px -3px rgba(90, 87, 214, 0.4);
    }

    .nav-item:hover:not(.active):not(.locked) {
        transform: translateX(5px);
        background: #f1f5f9;
    }

    .nav-item.locked {
        background: #3a414c;
        color: #94a3b8;
        cursor: not-allowed;
        opacity: 0.65;
        box-shadow: none;
    }
    .nav-item.locked:hover { transform: none; }

    .nav-item .lock-icon {
        font-size: 12px;
        opacity: 0.7;
    }
    .nav-item.complete::after {
        content: '✓';
        color: #10b981;
        font-size: 14px;
        font-weight: 800;
    }
</style>

@php
    /*
    |----------------------------------------------------------------------
    | UNIFIED 9-STEP ENROLMENT WIZARD
    |----------------------------------------------------------------------
    |  1. Personal Info        — student row created
    |  2. Contact Details      — student.mobile_number filled
    |  3. Family & Emergency   — session("apply.family.{term}")
    |  4. Learning Pathway     — session("apply.pathway_done.{term}")
    |  5. Academic Background  — session("apply.academic_done.{term}")
    |                             (skipped automatically when student_type=regular)
    |  6. Health Information   — session("apply.health_done.{term}")
    |  7. Financial Assessment — session("apply.financial_done.{term}")
    |  8. Review & Submit
    |  9. Confirmation         — only after submission
    */
    $student        = auth()->user()?->student;
    $tid            = $term->id;

    // If the student already has a submitted enrollment for this term, treat
    // every step as completed/unlocked so they can freely navigate and edit
    // any section without being blocked by missing session markers.
    $hasEnrollment  = $student
        ? \App\Models\StudentEnrollment::where('student_id', $student->id)
            ->where('term_id', $tid)->exists()
        : false;

    $step1Done      = (bool) $student;
    $step2Done      = $step1Done && filled($student?->mobile_number ?? $student?->phone ?? null);
    $familyDone     = $step2Done   && (bool) session("apply.family.{$tid}");
    $pathwayDone    = $familyDone  && (bool) session("apply.pathway_done.{$tid}");
    $academicSkip   = (bool) session("apply.academic_skipped.{$tid}");
    $academicDone   = $pathwayDone && ($academicSkip || (bool) session("apply.academic_done.{$tid}"));
    $healthDone     = $academicDone && (bool) session("apply.health_done.{$tid}");
    $diagnosticSkip = (bool) session("apply.diagnostic_skipped.{$tid}");
    $diagnosticDone = $healthDone && ($diagnosticSkip || (bool) session("apply.diagnostic_done.{$tid}"));
    $financialDone  = $diagnosticDone && (bool) session("apply.financial_done.{$tid}");
    $reviewDone     = $financialDone && (bool) session("apply.review_done.{$tid}");

    if ($hasEnrollment) {
        $step1Done = $step2Done = $familyDone = $pathwayDone = true;
        $academicDone = $healthDone = $diagnosticDone = $financialDone = $reviewDone = true;
    }

    $unlocked = [
        1 => true,
        2 => $step1Done,
        3 => $step2Done,
        4 => $familyDone,
        5 => $pathwayDone && ! $academicSkip,
        6 => $academicDone,
        7 => $healthDone && ! $diagnosticSkip,   // Diagnostic Test (conditional)
        8 => $diagnosticDone,                     // Financial Assessment requires Diagnostic done
        9 => $financialDone,                      // Review requires Financial done
        10 => $reviewDone,                        // Confirmation requires Review done
    ];
    $done = [
        1 => $step1Done,
        2 => $step2Done,
        3 => $familyDone,
        4 => $pathwayDone,
        5 => $academicDone,
        6 => $healthDone,
        7 => $diagnosticDone,
        8 => $financialDone,
        9 => $reviewDone,
        10 => false,
    ];

    $stepClasses = function ($key, $isActive) use ($unlocked, $done) {
        $classes = ['nav-item'];
        if ($isActive) $classes[] = 'active';
        if (! ($unlocked[$key] ?? false)) $classes[] = 'locked';
        elseif ($done[$key] ?? false)     $classes[] = 'complete';
        return implode(' ', $classes);
    };
@endphp

<div class="sidebar">
    <div class="logo-text">Enrolment Form</div>

    {{-- 1. Personal Info --}}
    <a href="{{ route('public.apply.show', $tid) }}"
       class="{{ $stepClasses(1, request()->routeIs('public.apply.show')) }}">
        <span>1. Personal Info</span>
    </a>

    {{-- 2. Contact Details --}}
    @if ($unlocked[2])
        <a href="{{ route('public.apply.step2', $tid) }}"
           class="{{ $stepClasses(2, request()->routeIs('public.apply.step2')) }}">
            <span>2. Contact Details</span>
        </a>
    @else
        <span class="nav-item locked"><span>2. Contact Details</span><span class="lock-icon">🔒</span></span>
    @endif

    {{-- 3. Family & Emergency --}}
    @if ($unlocked[3])
        <a href="{{ route('public.apply.family', $tid) }}"
           class="{{ $stepClasses(3, request()->routeIs('public.apply.family*')) }}">
            <span>3. Family &amp; Emergency</span>
        </a>
    @else
        <span class="nav-item locked"><span>3. Family &amp; Emergency</span><span class="lock-icon">🔒</span></span>
    @endif

    {{-- 4. Learning Pathway --}}
    @if ($unlocked[4])
        <a href="{{ route('public.apply.pathway', $tid) }}"
           class="{{ $stepClasses(4, request()->routeIs('public.apply.pathway*')) }}">
            <span>4. Learning Pathway</span>
        </a>
    @else
        <span class="nav-item locked"><span>4. Learning Pathway</span><span class="lock-icon">🔒</span></span>
    @endif

    {{-- 5. Academic Background (auto-skipped for regular students) --}}
    @if ($academicSkip)
        <span class="nav-item locked" title="Skipped — regular students reuse prior academic record.">
            <span>5. Academic Background</span><span class="lock-icon">⏭</span>
        </span>
    @elseif ($unlocked[5])
        <a href="{{ route('public.apply.academic', $tid) }}"
           class="{{ $stepClasses(5, request()->routeIs('public.apply.academic*')) }}">
            <span>5. Academic Background</span>
        </a>
    @else
        <span class="nav-item locked"><span>5. Academic Background</span><span class="lock-icon">🔒</span></span>
    @endif

    {{-- 6. Health Information --}}
    @if ($unlocked[6])
        <a href="{{ route('public.apply.health', $tid) }}"
           class="{{ $stepClasses(6, request()->routeIs('public.apply.health')) }}">
            <span>6. Health Information</span>
        </a>
    @else
        <span class="nav-item locked"><span>6. Health Information</span><span class="lock-icon">🔒</span></span>
    @endif

    {{-- 7. Diagnostic Test (auto-skipped when not required for this applicant) --}}
    @if ($diagnosticSkip)
        <span class="nav-item locked" title="Skipped — not required for your applicant type.">
            <span>7. Diagnostic Test</span><span class="lock-icon">⏭</span>
        </span>
    @elseif ($unlocked[7])
        <a href="{{ route('public.apply.diagnostic', $tid) }}"
           class="{{ $stepClasses(7, request()->routeIs('public.apply.diagnostic')) }}">
            <span>7. Diagnostic Test</span>
        </a>
    @else
        <span class="nav-item locked"><span>7. Diagnostic Test</span><span class="lock-icon">🔒</span></span>
    @endif

    {{-- 8. Financial Assessment --}}
    @if ($unlocked[8])
        <a href="{{ route('public.apply.financial', $tid) }}"
           class="{{ $stepClasses(8, request()->routeIs('public.apply.financial')) }}">
            <span>8. Financial Assessment</span>
        </a>
    @else
        <span class="nav-item locked"><span>8. Financial Assessment</span><span class="lock-icon">🔒</span></span>
    @endif

    {{-- 9. Review & Submit --}}
    @if ($unlocked[9])
        <a href="{{ route('public.apply.review', $tid) }}"
           class="{{ $stepClasses(9, request()->routeIs('public.apply.review')) }}">
            <span>9. Review &amp; Submit</span>
        </a>
    @else
        <span class="nav-item locked"><span>9. Review &amp; Submit</span><span class="lock-icon">🔒</span></span>
    @endif

    {{-- 10. Confirmation --}}
    @if ($unlocked[10])
        <span class="nav-item complete" style="cursor:default;">
            <span>10. Confirmation</span>
        </span>
    @else
        <span class="nav-item locked" style="cursor:default;">
            <span>10. Confirmation</span><span class="lock-icon">🔒</span>
        </span>
    @endif

    {{-- Save & Exit: persist all session draft data and return to dashboard --}}
    <a href="{{ route('public.apply.exit', $tid) }}"
       class="nav-item"
       style="margin-top:24px;background:#5a57d6;color:#fff;justify-content:center;text-transform:none;font-size:13px;letter-spacing:0;box-shadow:0 10px 15px -3px rgba(90,87,214,0.4);"
       title="Your progress will be saved. You can resume later.">
        <span>← Back to Dashboard</span>
    </a>
</div>
