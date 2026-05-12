@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;

    $profile = $school?->profile ?? optional(\App\Models\SchoolProfile::where('school_id', $school?->id)->first());

    $logoPath = $profile?->school_logo;
    $logoSrc  = null;
    if ($logoPath) {
        // dompdf needs an absolute file path or data URI to embed images.
        $abs = Storage::disk('public')->exists($logoPath)
            ? Storage::disk('public')->path($logoPath)
            : public_path($logoPath);
        if (is_file($abs)) {
            $mime = function_exists('mime_content_type') ? mime_content_type($abs) : 'image/png';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
        }
    }

    $schoolName    = $profile?->school_name ?: $school?->school_name ?: config('app.name');
    $schoolAddress = $profile?->address    ?: ($school?->address    ?? '');
    $schoolPhone   = $profile?->contact_number ?: $profile?->mobile_number ?: ($school?->mobile_number ?? '');
    $schoolEmail   = $profile?->email      ?: ($school?->email      ?? '');
    $schoolWebsite = $profile?->website    ?: '';

    $chain = [];
    $cursor = $node;
    while ($cursor) {
        array_unshift($chain, $cursor);
        $cursor = $cursor->parent_id ? \App\Models\EducationNode::find($cursor->parent_id) : null;
    }
    $rootLevel = $chain[0] ?? null;
    $pathLabel = collect($chain)->skip(1)->pluck('name')->implode(' › ');
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Enrolment Application — {{ $student->first_name }} {{ $student->last_name }}</title>
<style>
    @page { margin: 30px 36px; size: A4; }
    body  { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
    .header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 14px; }
    .header table { width: 100%; }
    .header td.logo { width: 70px; vertical-align: top; }
    .header td.logo img { width: 64px; height: 64px; object-fit: contain; }
    .header td.text { vertical-align: top; padding-left: 10px; }
    .header .sname { font-size: 16px; font-weight: 700; color: #1e293b; }
    .header .sline { font-size: 10px; color: #475569; line-height: 1.45; }
    .header td.meta { vertical-align: top; text-align: right; font-size: 10px; color: #475569; }
    .header td.meta .ref { font-size: 9px; color: #94a3b8; font-family: DejaVu Sans Mono, monospace; }

    h1.doc-title { font-size: 13px; font-weight: 700; text-align: center; margin: 4px 0 16px;
                   letter-spacing: 2px; text-transform: uppercase; color: #4f46e5; }

    .section { border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 10px; }
    .section .hd { background: #f1f5f9; padding: 5px 9px; font-size: 9.5px;
                   font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: #475569;
                   border-bottom: 1px solid #e2e8f0; }
    .section .bd { padding: 8px 10px; }

    table.kv { width: 100%; border-collapse: collapse; }
    table.kv td { padding: 3px 4px; vertical-align: top; }
    table.kv td.lbl { color: #64748b; width: 32%; }
    table.kv td.val { color: #0f172a; font-weight: 600; }
    table.kv tr + tr td { border-top: 1px dotted #e2e8f0; }

    .twocol { width: 100%; border-collapse: collapse; }
    .twocol td { width: 50%; vertical-align: top; padding: 0; }
    .twocol td.left { padding-right: 6px; }
    .twocol td.right { padding-left: 6px; }

    .person { border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 8px; margin-bottom: 5px; }
    .person .nm { font-weight: 700; color: #0f172a; font-size: 11px; }
    .person .meta { color: #64748b; font-size: 10px; margin-top: 2px; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 3px;
             font-size: 8.5px; font-weight: 700; background: #eef2ff; color: #4338ca;
             margin-left: 4px; letter-spacing: 0.5px; }

    .footer { margin-top: 16px; text-align: center; color: #94a3b8; font-size: 9px;
              border-top: 1px solid #e2e8f0; padding-top: 8px; }

    .sig { margin-top: 24px; }
    .sig table { width: 100%; }
    .sig td { vertical-align: bottom; padding: 0 8px; width: 50%; }
    .sig .line { border-top: 1px solid #94a3b8; padding-top: 4px; font-size: 9.5px;
                 color: #64748b; text-align: center; }
    .muted { color: #94a3b8; font-style: italic; }
</style>
</head>
<body>

<div class="header">
    <table>
        <tr>
            <td class="logo">
                @if ($logoSrc)
                    <img src="{{ $logoSrc }}" alt="logo">
                @endif
            </td>
            <td class="text">
                <div class="sname">{{ strtoupper($schoolName) }}</div>
                <div class="sline">
                    @if ($schoolAddress){{ $schoolAddress }}<br>@endif
                    @if ($schoolPhone) Tel: {{ $schoolPhone }} @endif
                    @if ($schoolEmail) · {{ $schoolEmail }} @endif
                    @if ($schoolWebsite) · {{ $schoolWebsite }} @endif
                </div>
            </td>
            <td class="meta">
                <div><strong>Application Date</strong><br>{{ Carbon::now()->format('M d, Y') }}</div>
                <div style="margin-top:4px;">
                    <strong>Reference</strong><br>
                    <span class="ref">ENR-{{ str_pad($enrollment->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
            </td>
        </tr>
    </table>
</div>

<h1 class="doc-title">Student Enrolment Application</h1>

{{-- ============= PERSONAL ============= --}}
<div class="section">
    <div class="hd">Personal Information</div>
    <div class="bd">
        <table class="twocol">
            <tr>
                <td class="left">
                    <table class="kv">
                        <tr><td class="lbl">Full Name</td>
                            <td class="val">{{ trim(($student->first_name ?? '').' '.($student->middle_name ?? '').' '.($student->last_name ?? '')) ?: '—' }}</td></tr>
                        <tr><td class="lbl">Date of Birth</td>
                            <td class="val">{{ $student->date_of_birth ? Carbon::parse($student->date_of_birth)->format('M d, Y') : '—' }}</td></tr>
                        <tr><td class="lbl">Gender</td>
                            <td class="val">{{ ucfirst($student->gender ?? '—') }}</td></tr>
                    </table>
                </td>
                <td class="right">
                    <table class="kv">
                        <tr><td class="lbl">Civil Status</td>
                            <td class="val">{{ ucfirst($student->civil_status ?? '—') }}</td></tr>
                        <tr><td class="lbl">Religion</td>
                            <td class="val">{{ $student->religion ?? '—' }}</td></tr>
                        <tr><td class="lbl">Nationality</td>
                            <td class="val">{{ $student->nationality ?? '—' }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- ============= CONTACT ============= --}}
<div class="section">
    <div class="hd">Contact Details</div>
    <div class="bd">
        <table class="twocol">
            <tr>
                <td class="left">
                    <table class="kv">
                        <tr><td class="lbl">Email</td>
                            <td class="val">{{ $student->email ?? $student->user->email ?? '—' }}</td></tr>
                        <tr><td class="lbl">Mobile</td>
                            <td class="val">{{ $student->mobile_number ?? '—' }}</td></tr>
                        <tr><td class="lbl">Address Line 1</td>
                            <td class="val">{{ $student->address_line_1 ?? '—' }}</td></tr>
                        @if ($student->address_line_2)
                        <tr><td class="lbl">Address Line 2</td>
                            <td class="val">{{ $student->address_line_2 }}</td></tr>
                        @endif
                        <tr><td class="lbl">Barangay</td>
                            <td class="val">{{ $student->barangay ?? '—' }}</td></tr>
                    </table>
                </td>
                <td class="right">
                    <table class="kv">
                        <tr><td class="lbl">City / Municipality</td>
                            <td class="val">{{ $student->city_municipality ?? '—' }}</td></tr>
                        <tr><td class="lbl">Province</td>
                            <td class="val">{{ $student->province ?? '—' }}</td></tr>
                        <tr><td class="lbl">Region</td>
                            <td class="val">{{ $student->region ?? '—' }}</td></tr>
                        <tr><td class="lbl">Zip Code</td>
                            <td class="val">{{ $student->zip_code ?? '—' }}</td></tr>
                        <tr><td class="lbl">Country</td>
                            <td class="val">{{ $student->country ?? '—' }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- ============= FAMILY ============= --}}
<div class="section">
    <div class="hd">Family &amp; Emergency Contact</div>
    <div class="bd">
        <div style="font-size:9.5px; font-weight:700; color:#64748b; letter-spacing:0.8px;
                    text-transform:uppercase; margin-bottom:4px;">Parents / Guardians</div>
        @if ($parents->isEmpty())
            <div class="muted" style="font-size:10px;">No parents or guardians on record.</div>
        @else
            @foreach ($parents as $p)
                <div class="person">
                    <div class="nm">
                        {{ trim(($p->first_name ?? '').' '.($p->middle_name ?? '').' '.($p->last_name ?? '')) ?: '—' }}
                        @if ($p->relationship)<span style="font-size:10px;color:#64748b;font-weight:400;">({{ ucfirst($p->relationship) }})</span>@endif
                        @if ($p->is_primary)<span class="badge">PRIMARY</span>@endif
                    </div>
                    <div class="meta">
                        @if ($p->occupation){{ $p->occupation }}@endif
                        @if ($p->employer) · {{ $p->employer }}@endif
                        @if ($p->mobile_number) · {{ $p->mobile_number }}@endif
                        @if ($p->email) · {{ $p->email }}@endif
                    </div>
                </div>
            @endforeach
        @endif

        <div style="font-size:9.5px; font-weight:700; color:#64748b; letter-spacing:0.8px;
                    text-transform:uppercase; margin:8px 0 4px;">Emergency Contact</div>
        @if (! $emergencyContact)
            <div class="muted" style="font-size:10px;">No emergency contact on record.</div>
        @else
            <table class="kv">
                <tr><td class="lbl">Name</td>
                    <td class="val">{{ trim(($emergencyContact->first_name ?? '').' '.($emergencyContact->last_name ?? '')) ?: '—' }}</td></tr>
                <tr><td class="lbl">Relationship</td>
                    <td class="val">{{ ucfirst($emergencyContact->relationship ?? '—') }}</td></tr>
                <tr><td class="lbl">Mobile</td>
                    <td class="val">{{ $emergencyContact->mobile_number ?? '—' }}</td></tr>
                @if ($emergencyContact->email)
                <tr><td class="lbl">Email</td>
                    <td class="val">{{ $emergencyContact->email }}</td></tr>
                @endif
                @if ($emergencyContact->address)
                <tr><td class="lbl">Address</td>
                    <td class="val">{{ $emergencyContact->address }}</td></tr>
                @endif
            </table>
        @endif
    </div>
</div>

{{-- ============= LEARNING PATHWAY ============= --}}
<div class="section">
    <div class="hd">Learning Pathway</div>
    <div class="bd">
        <table class="twocol">
            <tr>
                <td class="left">
                    <table class="kv">
                        <tr><td class="lbl">Education Level</td>
                            <td class="val">{{ $rootLevel?->name ?? '—' }}</td></tr>
                        @if (! empty($pathLabel))
                        <tr><td class="lbl">Path</td>
                            <td class="val">{{ $pathLabel }}</td></tr>
                        @endif
                        <tr><td class="lbl">Programme</td>
                            <td class="val">{{ $program ? ($program->code ? $program->code.' — ' : '').$program->name : '—' }}</td></tr>
                    </table>
                </td>
                <td class="right">
                    <table class="kv">
                        <tr><td class="lbl">Year Level</td>
                            <td class="val">{{ $pathway['year_level'] ?? '—' }}</td></tr>
                        <tr><td class="lbl">Modality</td>
                            <td class="val">{{ $modality?->name ?? '—' }}</td></tr>
                        <tr><td class="lbl">Student Type</td>
                            <td class="val">
                                @if ($modality && $modality->code === 'async_online')
                                    N/A — Asynchronous Online
                                @else
                                    {{ ucfirst($pathway['student_type'] ?? '—') }}
                                @endif
                            </td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- ============= ACADEMIC BACKGROUND ============= --}}
<div class="section">
    <div class="hd">Academic Background</div>
    <div class="bd">
        @if ($skipped)
            <div class="muted" style="font-size:10px;">Skipped — Regular students reuse their prior academic record.</div>
        @elseif ($backgrounds->isEmpty())
            <div class="muted" style="font-size:10px;">No academic backgrounds entered.</div>
        @else
            @foreach ($backgrounds as $bg)
                <div class="person">
                    <div class="nm">
                        {{ $bg->school_name }}
                        <span style="font-size:10px;color:#64748b;font-weight:400;">
                            ({{ ucfirst(str_replace('_',' ', $bg->education_level)) }})
                        </span>
                    </div>
                    <div class="meta">
                        {{ $bg->year_started ?? '?' }} – {{ $bg->year_ended ?? '?' }}
                        @if ($bg->school_address) · {{ $bg->school_address }} @endif
                        @if ($bg->last_grade_level) · {{ $bg->last_grade_level }} @endif
                        @if ($bg->gpa) · GPA: {{ $bg->gpa }} @endif
                        @if ($bg->honors) · {{ $bg->honors }} @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

{{-- ============= SIGNATURE ============= --}}
<div class="sig">
    <table>
        <tr>
            <td>
                <div class="line">Applicant Signature</div>
            </td>
            <td>
                <div class="line">Parent / Guardian Signature</div>
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    This document was generated automatically on {{ Carbon::now()->format('M d, Y g:i A') }}.
    Reference: ENR-{{ str_pad($enrollment->id, 6, '0', STR_PAD_LEFT) }}.
    Please keep a copy for your records.
</div>

</body>
</html>
