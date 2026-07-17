<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Answer Sheets — {{ $test->title }} — {{ $gradeLabel }}</title>
    <style>
        /* Self-contained print styles (build-independent). */
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; color: #111; }

        .toolbar { padding: 12px 16px; background: #f1f5f9; border-bottom: 1px solid #cbd5e1; }
        .toolbar button { padding: 8px 16px; font-weight: 700; background: #2563eb; color: #fff; border: 0; border-radius: 6px; cursor: pointer; }
        .toolbar .hint { color: #475569; font-size: 13px; margin-left: 10px; }

        .omr-page { width: 8.0in; min-height: 10.5in; margin: 16px auto; padding: 0.5in; background: #fff; }
        .omr-page + .omr-page { border-top: 1px dashed #cbd5e1; }

        .lh { display: flex; align-items: center; gap: 14px; border-bottom: 2px solid #111; padding-bottom: 8px; }
        .lh img { height: 64px; width: auto; }
        .lh .school { flex: 1; text-align: center; }
        .lh .school .name { font-size: 17px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; }
        .lh .school .addr { font-size: 11px; color: #333; }
        .lh .sy { font-size: 12px; font-weight: 700; white-space: nowrap; }

        .meta { margin-top: 10px; font-size: 12.5px; }
        .meta .test-title { text-align: center; font-size: 15px; font-weight: 800; margin-bottom: 6px; }
        .meta .row { display: flex; justify-content: space-between; gap: 16px; margin-top: 2px; }
        .meta .row span b { font-weight: 700; }

        .student { display: flex; justify-content: space-between; gap: 16px; margin-top: 12px; border: 1px solid #111; padding: 10px 12px; }
        .student .fields { flex: 1; font-size: 13px; line-height: 1.7; }
        .student .fields b { display: inline-block; min-width: 118px; }
        .student .qr { text-align: center; }
        .student .qr .cap { font-size: 9px; color: #555; margin-top: 2px; letter-spacing: .5px; }

        .grid-title { margin: 14px 0 6px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #111; padding-bottom: 3px; }

        /* Scannable region: fixed size, corner fiducials, absolute bubbles.
           Coordinates (0..1) map to .omr-grid, whose corners = fiducial centres,
           so the printed sheet and the stored bubble map agree exactly. */
        .omr-region { position: relative; width: 100%; }
        .fid { position: absolute; width: 0.28in; height: 0.28in; background: #000; }
        .fid.tl { left: 0; top: 0; } .fid.tr { right: 0; top: 0; }
        .fid.bl { left: 0; bottom: 0; } .fid.br { right: 0; bottom: 0; }
        .omr-grid { position: absolute; left: 0.14in; top: 0.14in; right: 0.14in; bottom: 0.14in; }
        .omr-bub { position: absolute; transform: translate(-50%, -50%); width: 0.19in; height: 0.19in; border: 1.3px solid #111; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #555; }
        .omr-num { position: absolute; transform: translate(-118%, -50%); font-size: 10px; font-weight: 700; white-space: nowrap; }
        .omr-region .ver { position: absolute; right: 0; bottom: -13px; font-size: 8px; color: #94a3b8; letter-spacing: .5px; }

        /* Write-in (identification / matching) — bounded boxes at fixed coords,
           in the same fiducial region as the bubbles so the camera can crop them. */
        .omr-wbox { position: absolute; border: 1.2px solid #111; border-radius: 4px; }
        .omr-wnum { position: absolute; transform: translate(-50%, -50%); font-size: 10px; font-weight: 700; }

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
                @if ($profile?->address)
                    <div class="addr">{{ $profile->address }}</div>
                @endif
            </div>
            <div class="sy">S.Y. {{ $schoolYear ?? '—' }}</div>
        </div>

        {{-- ===== Test meta ===== --}}
        <div class="meta">
            <div class="test-title">{{ $test->title }}</div>
            <div class="row">
                <span><b>Subject:</b> {{ $test->subject?->name ?? '—' }}</span>
                <span><b>Grade &amp; Section:</b> {{ $gradeLabel }}</span>
            </div>
            <div class="row">
                <span><b>Teacher:</b> {{ $test->teacher?->name ?: '—' }}</span>
                <span><b>Date:</b> {{ optional($test->test_date ?? $test->created_at)->format('F d, Y') }}</span>
            </div>
        </div>

        {{-- ===== Student identity + QR ===== --}}
        <div class="student">
            <div class="fields">
                <div><b>Student Name:</b> {{ $sheet['name'] }}</div>
                <div><b>Student Number:</b> {{ $sheet['student_number'] ?? '' }}</div>
                @if (! empty($sheet['lrn']))
                    <div><b>LRN:</b> {{ $sheet['lrn'] }}</div>
                @endif
            </div>
            <div class="qr">
                <div class="qr-box" data-qr="{{ $sheet['qr'] }}"></div>
                <div class="cap">DO NOT WRITE OVER THE CODE</div>
            </div>
        </div>

        {{-- ===== Scannable region: bubbles (top) + write-in boxes (below) ===== --}}
        @if ($itemCount > 0 || ! empty($written))
            <div class="grid-title">Answer Sheet — shade the letter for choices; PRINT write-in answers in CAPITALS. Keep the corner squares clean.</div>
            <div class="omr-region" style="height: {{ $regionHeight }}in;">
                <span class="fid tl"></span><span class="fid tr"></span>
                <span class="fid bl"></span><span class="fid br"></span>
                <div class="omr-grid">
                    @foreach ($grid as $item)
                        <span class="omr-num" style="left: {{ $item['num']['x'] * 100 }}%; top: {{ $item['num']['y'] * 100 }}%;">{{ $item['n'] }}</span>
                        @foreach ($item['options'] as $o)
                            <span class="omr-bub" style="left: {{ $o['x'] * 100 }}%; top: {{ $o['y'] * 100 }}%;">{{ $o['label'] }}</span>
                        @endforeach
                    @endforeach
                    @foreach ($written as $w)
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
            width: 104,
            height: 104,
            correctLevel: QRCode.CorrectLevel.M,
        });
    });
</script>

</body>
</html>
