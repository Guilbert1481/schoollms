@php
    use App\Models\SchoolProfile;
    use App\Support\EducationLevels;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Storage;

    $cur = 'PHP';
    $student = $invoice->student;
    $studentName = $student ? trim(($student->first_name ?? '').' '.($student->last_name ?? '')) : '—';

    // ---- School letterhead ----------------------------------------------
    // Branding lives on school_profiles (NOT schools — schools has no logo or
    // address columns). Same pattern as acad_enrolment/pdf/enrollment.blade.php:
    // dompdf needs the logo as an absolute path or data URI, so embed it base64.
    $profile = $school?->profile ?? SchoolProfile::where('school_id', $school?->id)->first();

    $logoSrc  = null;
    $logoPath = $profile?->school_logo;
    if ($logoPath) {
        $abs = Storage::disk('public')->exists($logoPath)
            ? Storage::disk('public')->path($logoPath)
            : public_path($logoPath);
        if (is_file($abs)) {
            $mime = function_exists('mime_content_type') ? (mime_content_type($abs) ?: 'image/png') : 'image/png';
            $logoSrc = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($abs));
        }
    }

    $schoolName    = $profile?->school_name ?: ($school->school_name ?? 'School');
    $schoolAddress = $profile?->address ?: '';
    $schoolPhone   = $profile?->contact_number ?: ($profile?->mobile_number ?: '');
    $schoolEmail   = $profile?->email ?: '';
    $schoolWebsite = $profile?->website ?: '';

    // ---- Billed-to details -----------------------------------------------
    // invoices.student_id = users.id; guardians hang off the student PROFILE
    // row (students.user_id -> students.id -> guardians.student_id). Primary
    // parent first.
    $guardian = null;
    if ($student) {
        $profileRowId = DB::table('students')->where('user_id', $student->id)->value('id');
        if ($profileRowId) {
            $guardian = DB::table('guardians')
                ->where('student_id', $profileRowId)
                ->where('type', 'parent')
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->first(['first_name', 'last_name', 'email']);
        }
    }
    $guardianName  = $guardian ? trim(($guardian->first_name ?? '').' '.($guardian->last_name ?? '')) : null;
    $guardianEmail = $guardian->email ?? null;

    // Level = root of the enrolment's education node (falling back to the
    // program's node); Program shows only for non-Basic-Ed students.
    $enr     = $invoice->enrollment;
    $program = $enr?->program;
    $nodeId  = $enr?->education_node_id ?: ($program->education_node_id ?? null);

    $rootName = null;
    if ($nodeId) {
        $rootId   = EducationLevels::nodeRootMap()[$nodeId] ?? null;
        $rootName = $rootId ? DB::table('education_nodes')->where('id', $rootId)->value('name') : null;
    }
    $isBasic = EducationLevels::isBasic($rootName);

    $yl = $enr?->year_level;
    $gradeYear = ($yl === null || $yl === '')
        ? '—'
        : (is_numeric($yl) ? ($isBasic ? 'Grade ' : 'Year ').(int) $yl : (string) $yl);

    $programLabel = $program ? trim(($program->code ? $program->code.' — ' : '').$program->name) : '—';

    $sectionName = $enr?->section_id ? DB::table('sections')->where('id', $enr->section_id)->value('name') : null;
    $ayName      = $enr?->academic_year_id ? DB::table('academic_years')->where('id', $enr->academic_year_id)->value('name') : null;
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 32px 36px; size: A4; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
    .header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 16px; }
    table.lh { width: 100%; border-collapse: collapse; }
    table.lh td { vertical-align: top; }
    td.lh-logo { width: 70px; padding-right: 12px; }
    td.lh-logo img { width: 60px; height: 60px; object-fit: contain; }
    .lh-name { font-size: 15px; font-weight: bold; color: #111827; }
    .lh-line { font-size: 9.5px; color: #6b7280; line-height: 1.45; }
    td.lh-doc { text-align: right; width: 30%; }
    td.lh-doc .title { font-size: 18px; font-weight: bold; color: #4f46e5; letter-spacing: 1px; }
    .meta td { padding: 2px 0; vertical-align: top; }
    .meta .label { color: #6b7280; padding-right: 8px; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 14px; }
    table.items th { background: #f3f4f6; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #374151; border-bottom: 1px solid #e5e7eb; }
    table.items td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; }
    /* Numeric headers must line up with their right-aligned cells —
       `table.items th` outranks a bare `.right`, so target th.right explicitly. */
    table.items th.right { text-align: right; }
    .right { text-align: right; }
    .totals { width: 40%; margin-left: 60%; margin-top: 12px; }
    .totals td { padding: 3px 0; }
    .totals .grand { border-top: 1px solid #e5e7eb; font-weight: bold; }
    .balance { color: #b91c1c; font-weight: bold; }
    .badge { display: inline-block; padding: 2px 10px; border-radius: 9999px; font-size: 10px; font-weight: bold; }
    .footer { margin-top: 26px; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
</style>
</head>
<body>
    {{-- School letterhead: logo + identity left, document id right. --}}
    <div class="header">
        <table class="lh">
            <tr>
                @if($logoSrc)
                    <td class="lh-logo"><img src="{{ $logoSrc }}" alt=""></td>
                @endif
                <td>
                    <div class="lh-name">{{ $schoolName }}</div>
                    @if($schoolAddress !== '')
                        <div class="lh-line">{{ $schoolAddress }}</div>
                    @endif
                    @if($schoolPhone !== '' || $schoolEmail !== '' || $schoolWebsite !== '')
                        <div class="lh-line">{{ collect([$schoolPhone, $schoolEmail, $schoolWebsite])->filter(fn ($v) => $v !== '')->implode(' · ') }}</div>
                    @endif
                </td>
                <td class="lh-doc">
                    <div class="title">INVOICE</div>
                    <div>{{ $invoice->invoice_number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta" style="width:100%;">
        <tr>
            <td style="width:55%;">
                <table class="meta">
                    <tr><td class="label">Billed To</td><td><strong>{{ $studentName }}</strong></td></tr>
                    <tr><td class="label">Email</td><td>{{ $student->email ?? '—' }}</td></tr>
                    <tr><td class="label">Guardian</td><td>{{ $guardianName ?: '—' }}</td></tr>
                    <tr><td class="label">Guardian Email</td><td>{{ $guardianEmail ?: '—' }}</td></tr>
                    <tr><td class="label">Level</td><td>{{ $rootName ?: '—' }}</td></tr>
                    @if(! $isBasic)
                        <tr><td class="label">Program</td><td>{{ $programLabel }}</td></tr>
                    @endif
                    <tr><td class="label">Year/Grade</td><td>{{ $gradeYear }}</td></tr>
                    <tr><td class="label">Section</td><td>{{ $sectionName ?: '—' }}</td></tr>
                    <tr><td class="label">Academic Year</td><td>{{ $ayName ?: '—' }}</td></tr>
                    <tr><td class="label">Term</td><td>{{ $invoice->enrollment?->term?->name ?? '—' }}</td></tr>
                </table>
            </td>
            <td style="width:45%;">
                <table class="meta">
                    <tr><td class="label">Issue Date</td><td>{{ optional($invoice->issue_date)->format('M d, Y') ?? '—' }}</td></tr>
                    <tr><td class="label">Due Date</td><td>{{ optional($invoice->due_date)->format('M d, Y') ?? '—' }}</td></tr>
                    <tr><td class="label">Status</td><td>{{ strtoupper($invoice->status) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th>Basis</th>
                <th class="right">Qty</th>
                <th class="right">Unit ({{ $cur }})</th>
                <th class="right">Amount ({{ $cur }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->billing_basis === 'per_unit' ? 'Per unit' : 'Fixed' }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td class="right">{{ number_format((float) $item->unit_amount, 2) }}</td>
                    <td class="right">{{ number_format((float) $item->net_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ $cur }} {{ number_format((float) $invoice->subtotal_amount, 2) }}</td></tr>
        <tr><td>Discount</td><td class="right">{{ $cur }} {{ number_format((float) $invoice->discount_amount, 2) }}</td></tr>
        <tr class="grand"><td>Total</td><td class="right">{{ $cur }} {{ number_format((float) $invoice->total_amount, 2) }}</td></tr>
        <tr><td>Paid</td><td class="right">{{ $cur }} {{ number_format((float) $invoice->paid_amount, 2) }}</td></tr>
        <tr><td>Balance Due</td><td class="right balance">{{ $cur }} {{ number_format((float) $invoice->balance, 2) }}</td></tr>
    </table>

    <div class="footer">
        Generated {{ now()->format('M d, Y H:i') }} &middot; {{ $schoolName }}
    </div>
</body>
</html>
