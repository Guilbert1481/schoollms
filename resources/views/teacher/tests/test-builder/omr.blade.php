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

        .grid-title { margin: 16px 0 8px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #111; padding-bottom: 3px; }
        .bubbles { column-count: 4; column-gap: 22px; }
        .brow { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; break-inside: avoid; font-size: 12px; }
        .brow .num { width: 26px; text-align: right; font-weight: 700; }
        .bub { width: 17px; height: 17px; border: 1.4px solid #111; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 9px; color: #333; }

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

        {{-- ===== Answer grid (multiple-choice / true-false only) ===== --}}
        @if ($itemCount > 0)
            <div class="grid-title">Answer Sheet — shade the letter of your answer</div>
            <div class="bubbles">
                @for ($i = 1; $i <= $itemCount; $i++)
                    <div class="brow">
                        <span class="num">{{ $i }}.</span>
                        @foreach (['A', 'B', 'C', 'D', 'E'] as $l)
                            <span class="bub">{{ $l }}</span>
                        @endforeach
                    </div>
                @endfor
            </div>
        @else
            <div class="empty-note">
                This test has no multiple-choice / true-or-false items, so there is nothing to bubble.
                Answers are written directly on the test paper.
            </div>
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
