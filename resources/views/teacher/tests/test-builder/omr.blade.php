<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Answer Sheets — {{ $test->title }} — {{ $gradeLabel }}</title>
    <style>
        /* Self-contained print styles (build-independent). Sky-blue theme. */
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; color: #0f172a; }

        .toolbar { padding: 12px 16px; background: #f0f9ff; border-bottom: 1px solid #bae6fd; }
        .toolbar button { padding: 8px 16px; font-weight: 700; background: #0284c7; color: #fff; border: 0; border-radius: 6px; cursor: pointer; }
        .toolbar .hint { color: #475569; font-size: 13px; margin-left: 10px; }

        .omr-page { width: 8.0in; min-height: 10.5in; margin: 16px auto; padding: 0.5in; background: #fff; }
        .omr-page + .omr-page { border-top: 1px dashed #cbd5e1; }

        .lh { display: flex; align-items: center; gap: 14px; border-bottom: 2px solid #0f172a; padding-bottom: 8px; }
        .lh img { height: 60px; width: auto; }
        .lh .school { flex: 1; text-align: center; }
        .lh .school .name { font-size: 16px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase; }
        .lh .school .sub { font-size: 11px; color: #475569; }
        .lh .sy { font-size: 11px; font-weight: 700; white-space: nowrap; color: #075985; }

        .meta { text-align: center; font-size: 13px; font-weight: 800; margin: 8px 0 2px; }
        .meta .line { font-size: 11px; font-weight: 400; color: #334155; display: flex; justify-content: space-between; margin-top: 3px; }

        .student { display: flex; justify-content: space-between; gap: 16px; margin-top: 10px; border: 1px solid #0f172a; padding: 9px 11px; }
        .student .fields { flex: 1; font-size: 12px; line-height: 1.9; }
        .student .fields b { display: inline-block; min-width: 96px; }
        .student .qr { text-align: center; }
        .student .qr .cap { font-size: 8px; color: #64748b; margin-top: 2px; letter-spacing: .3px; }

        .howto { margin-top: 10px; background: #eff8fe; border: 1px solid #7dd3fc; border-radius: 5px; padding: 7px 10px; font-size: 10.5px; color: #075985; line-height: 1.55; }
        .howto .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #0f172a; vertical-align: -1px; }

        /* Scannable region: fixed size, corner fiducials, absolute items.
           Coordinates (0..1) map to .omr-grid, whose corners = fiducial centres,
           so the printed sheet and the stored bubble map agree exactly. */
        .omr-region { position: relative; width: 100%; margin-top: 12px; }
        .fid { position: absolute; width: 0.28in; height: 0.28in; background: #000; z-index: 3; }
        .fid.tl { left: 0; top: 0; } .fid.tr { right: 0; top: 0; }
        .fid.bl { left: 0; bottom: 0; } .fid.br { right: 0; bottom: 0; }
        .omr-grid { position: absolute; left: 0.14in; top: 0.14in; right: 0.14in; bottom: 0.14in; }

        .omr-band { position: absolute; left: 0; right: 0; background: #eff8fe; z-index: 0; }
        .omr-sec { position: absolute; left: 0; right: 0; transform: translateY(-50%); background: #0284c7; color: #fff; font-size: 10px; font-weight: 800; letter-spacing: .6px; padding: 2px 8px; border-radius: 3px; z-index: 2; }
        .omr-bub { position: absolute; transform: translate(-50%, -50%); width: 0.16in; height: 0.16in; border: 1.4px solid #0284c7; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 7px; color: #0284c7; z-index: 1; }
        .omr-num { position: absolute; transform: translateY(-50%); font-size: 9px; font-weight: 700; white-space: nowrap; z-index: 1; }
        .omr-wbox { position: absolute; border-bottom: 1.3px solid #475569; z-index: 1; }
        .omr-wnum { position: absolute; transform: translateY(-50%); font-size: 9px; font-weight: 700; z-index: 1; }
        .omr-region .ver { position: absolute; right: 0; bottom: -13px; font-size: 8px; color: #94a3b8; letter-spacing: .5px; }

        .empty-note { margin-top: 18px; padding: 14px; border: 1px dashed #94a3b8; color: #475569; font-size: 13px; text-align: center; }

        @media print {
            .toolbar { display: none; }
            .omr-page { margin: 0; padding: 0.5in; width: auto; min-height: auto; page-break-after: always; }
            .omr-page:last-child { page-break-after: auto; }
            .omr-page + .omr-page { border-top: 0; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button type="button" onclick="window.print()">Print / Save as PDF</button>
    <span class="hint">{{ count($sheets) }} answer sheet(s) — {{ $gradeLabel }}. One student per page.</span>
</div>

@forelse ($sheets as $sheet)
    <div class="omr-page">
        {{-- ===== Letterhead ===== --}}
        <div class="lh">
            @if ($profile?->school_logo)
                <img src="{{ asset('storage/'.$profile->school_logo) }}" alt="">
            @endif
            <div class="school">
                <div class="name">{{ $profile?->school_name ?? 'School' }}</div>
                <div class="sub">{{ $gradeLabel }}</div>
            </div>
            <div class="sy">S.Y. {{ $schoolYear ?? '—' }}</div>
        </div>

        {{-- ===== Test meta ===== --}}
        <div class="meta">
            {{ $test->subject?->name ?? 'Subject' }} — {{ $test->settings?->assessment_type ?? 'Assessment' }}
            <div class="line">
                <span>Teacher: {{ $test->teacher?->name ?: '—' }}</span>
                <span>Date: {{ optional($test->test_date ?? $test->created_at)->format('F d, Y') }}</span>
            </div>
        </div>

        {{-- ===== Student identity + QR ===== --}}
        <div class="student">
            <div class="fields">
                <div><b>Student Name:</b> {{ $sheet['name'] }}</div>
                <div><b>Student No.:</b> {{ $sheet['student_number'] ?? '' }}</div>
                @if (! empty($sheet['lrn']))
                    <div><b>LRN:</b> {{ $sheet['lrn'] }}</div>
                @endif
            </div>
            <div class="qr">
                <div class="qr-box" data-qr="{{ $sheet['qr'] }}"></div>
                <div class="cap">DO NOT MARK</div>
            </div>
        </div>

        {{-- ===== How to answer ===== --}}
        <div class="howto">
            <b>How to answer:</b> Shade the circle of your choice completely with a black or blue pen — like this <span class="dot"></span>. Mark only one per number. For write-in items, PRINT your answer in CAPITAL letters. Keep the four black corner squares clean.
        </div>

        {{-- ===== Scannable region: section headers + bubbles + write-in boxes ===== --}}
        @if ($itemCount > 0)
            <div class="omr-region" style="height: {{ $regionHeight }}in;">
                <span class="fid tl"></span><span class="fid tr"></span>
                <span class="fid bl"></span><span class="fid br"></span>
                <div class="omr-grid">
                    @foreach ($bands as $b)
                        <span class="omr-band" style="top: {{ $b['y'] * 100 }}%; height: {{ $b['h'] * 100 }}%;"></span>
                    @endforeach
                    @foreach ($headers as $h)
                        <span class="omr-sec" style="top: {{ $h['y'] * 100 }}%;">{{ $h['title'] }}</span>
                    @endforeach
                    @foreach ($grid as $item)
                        <span class="omr-num" style="left: {{ $item['num']['x'] * 100 }}%; top: {{ $item['num']['y'] * 100 }}%;">{{ $item['n'] }}</span>
                        @foreach ($item['options'] as $o)
                            <span class="omr-bub" style="left: {{ $o['x'] * 100 }}%; top: {{ $o['y'] * 100 }}%;">{{ $o['display'] ?? $o['label'] }}</span>
                        @endforeach
                    @endforeach
                    @foreach ($writes as $w)
                        <span class="omr-wnum" style="left: {{ $w['num']['x'] * 100 }}%; top: {{ $w['num']['y'] * 100 }}%;">{{ $w['n'] }}</span>
                        <span class="omr-wbox" style="left: {{ $w['box']['x'] * 100 }}%; top: {{ $w['box']['y'] * 100 }}%; width: {{ $w['box']['w'] * 100 }}%; height: {{ $w['box']['h'] * 100 }}%;"></span>
                    @endforeach
                </div>
                <span class="ver">OMR {{ $layoutVersion }}</span>
            </div>
        @else
            <div class="empty-note">This test has no auto-scannable items.</div>
        @endif
    </div>
@empty
    <div class="omr-page">
        <div class="empty-note">No enrolled students found for {{ $gradeLabel }}.</div>
    </div>
@endforelse

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    // Render each student's signed QR client-side (qrcodejs, the app convention).
    document.querySelectorAll('.qr-box').forEach(function (el) {
        new QRCode(el, {
            text: el.dataset.qr,
            width: 64,
            height: 64,
            correctLevel: QRCode.CorrectLevel.M,
        });
    });
</script>

</body>
</html>
