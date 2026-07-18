@extends('layouts.scanner')

@section('page-title', 'Scan Answer Sheets')

@section('content')
    <style>
        .scn-card { display: block; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
                    padding: 14px 16px; margin-bottom: 10px; text-decoration: none; color: inherit;
                    box-shadow: 0 6px 16px rgba(15, 23, 42, .04); }
        .scn-card h2 { margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #0f172a; }
        .scn-meta { font-size: 12px; color: #64748b; }
        .scn-pill { float: right; background: #ccfbf1; color: #0f766e; border-radius: 999px;
                    padding: 3px 10px; font-size: 11px; font-weight: 700; }
        .scn-empty { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
                     padding: 24px 18px; text-align: center; color: #64748b; font-size: 13px; }
    </style>

    @if ($tests->isEmpty())
        <div class="scn-empty">
            <p style="margin:0 0 8px;font-weight:700;color:#0f172a;">Nothing to scan yet</p>
            <p style="margin:0;">
                Print OMR answer sheets for a test from the portal first — printed sheets are what this app scans.
            </p>
        </div>
    @else
        @foreach ($tests as $test)
            <a class="scn-card" href="{{ route('scanner.scan-camera', $test->id) }}">
                <span class="scn-pill">{{ (int) ($sheetCounts[$test->id] ?? 0) }} sheets</span>
                <h2>{{ $test->title }}</h2>
                <div class="scn-meta">
                    {{ $subjectNames[$test->subject_id] ?? 'No subject' }}
                    @if ($test->created_at)
                        &middot; {{ $test->created_at->format('M j, Y') }}
                    @endif
                </div>
            </a>
        @endforeach
    @endif
@endsection
