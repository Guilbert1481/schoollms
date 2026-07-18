{{-- Renders in the portal shell by default, or the standalone Scanner app shell
     when reached via /scan (ScannerShell middleware shares $layout). --}}
@extends($layout ?? 'layouts.app')

@section('page-title', 'Scan OMR (Camera)')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Scan OMR Answer Sheets</h1>
        <p class="text-sm text-slate-500">
            {{ $test->title }} — point the camera at a sheet. Detection runs on this device; only the answers and
            confidence are sent. High-confidence sheets record automatically; unclear ones drop to review.
            <a href="{{ route('teacher.tests.omr.record', $test) }}" class="text-blue-600 underline">Enter manually instead</a>.
        </p>
    </div>

    <style>
        .omr-scan .card { background:#fff; border:1px solid #e6ebf3; border-radius:14px; padding:18px 20px; box-shadow:0 8px 22px rgba(0,0,0,.04); }
        .omr-scan label.lbl { display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; }
        .omr-scan select { width:100%; max-width:420px; padding:8px 10px; border:1px solid #d1d5db; border-radius:8px; background:#fff; font-size:14px; }
        .omr-scan .stage { position:relative; background:#0b1220; border-radius:12px; overflow:hidden; max-width:560px; }
        .omr-scan video { width:100%; display:block; }
        .omr-scan .who { font-size:13px; font-weight:700; color:#0f766e; min-height:18px; margin:8px 0; }
        .omr-scan .status { font-size:13px; color:#475569; margin:8px 0; }
        .omr-scan .btn { padding:9px 18px; border:0; border-radius:9px; font-weight:700; cursor:pointer; }
        .omr-scan .btn-primary { background:#2563eb; color:#fff; }
        .omr-scan .btn-primary:disabled { opacity:.5; cursor:not-allowed; }
        .omr-scan .rec-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(210px, 1fr)); gap:8px 22px; margin-top:12px; }
        .omr-scan .rec-row { display:flex; align-items:center; gap:8px; font-size:13px; }
        .omr-scan .rec-row .n { width:26px; text-align:right; font-weight:700; color:#334155; }
        .omr-scan .opt { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border:1.4px solid #cbd5e1; border-radius:50%; cursor:pointer; font-size:12px; color:#475569; }
        .omr-scan .opt.on { background:#1d4ed8; border-color:#1d4ed8; color:#fff; }
        .omr-scan .opt.low { border-color:#f59e0b; }
        .omr-scan .opt.on.low { background:#f59e0b; border-color:#f59e0b; }
        .omr-scan .flag { font-size:10px; font-weight:700; color:#b45309; background:#fef3c7; border-radius:999px; padding:1px 7px; }
        .omr-scan .res { margin-top:12px; font-size:14px; }
        .omr-scan .muted { color:#94a3b8; font-size:13px; }
    </style>

    <div class="omr-scan space-y-4">

        {{-- Section picker --}}
        <div class="card">
            <form method="GET" action="{{ route('teacher.tests.omr.scan-camera', $test) }}">
                <label class="lbl" for="section">Section</label>
                <select id="section" name="section_id" onchange="this.form.submit()">
                    <option value="">— Select a section —</option>
                    @foreach ($sections as $sec)
                        <option value="{{ $sec->id }}" @selected($sectionId == $sec->id)>
                            {{ $sec->name }}@if($sec->year_level) (Year {{ $sec->year_level }})@endif — {{ $sec->student_count }} student(s)
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        @if ($section && $itemCount > 0)
            <div class="card">
                <div class="stage">
                    <video id="omrVideo" playsinline muted></video>
                    <canvas id="omrCanvas" style="display:none;"></canvas>
                </div>
                <div class="who" id="omrWho"></div>
                <div class="status" id="omrStatus">Loading detector…</div>
                <button type="button" class="btn btn-primary" id="omrCapture" disabled>Capture &amp; detect</button>

                <div id="omrReview" style="display:none; margin-top:16px;">
                    <div class="muted">Detected answers — amber items need a look. Tap bubbles / edit text, then Record.</div>
                    <div class="rec-grid" id="omrGrid"></div>
                    <div id="omrWritten" style="margin-top:12px;"></div>
                    <div style="margin-top:14px;">
                        <button type="button" class="btn btn-primary" id="omrRecordBtn">Record</button>
                    </div>
                </div>

                <div class="res" id="omrResult"></div>
            </div>
        @elseif ($section)
            <div class="card muted">This test has no multiple-choice / true-false items to scan.</div>
        @endif
    </div>
</div>

@if ($section && $itemCount > 0)
    <script>
        window.OMR_SCAN = {
            grid: @json($grid),
            written: @json($written),
            roster: @json($roster),
            scanUrl: @json(route('teacher.tests.omr.scan')),
            csrf: document.querySelector('meta[name="csrf-token"]')?.content,
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
    <script async src="https://docs.opencv.org/4.10.0/opencv.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script src="{{ asset('js/tests/test-builder/omr-scan.js') }}"></script>
@endif
@endsection
