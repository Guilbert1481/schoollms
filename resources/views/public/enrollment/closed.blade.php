<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $isUpcoming = ($setting->status ?? 'Closed') === 'Upcoming';
        $sessionTitle = $setting->title ?: ($setting->name ?: 'this session');
        $endLabel   = $setting->end_date   ? \Illuminate\Support\Carbon::parse($setting->end_date)->format('F d, Y')   : null;
        $startLabel = $setting->start_date ? \Illuminate\Support\Carbon::parse($setting->start_date)->format('F d, Y') : null;
    @endphp
    <title>{{ $isUpcoming ? 'Enrollment Not Yet Open' : 'Enrollment Closed' }} — {{ $schoolName ?? 'School Portal' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',sans-serif;}
        body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1f2733 0%,#2c333d 100%);padding:24px;}
        .lock-card{width:100%;max-width:520px;background:#ffffff;border-radius:18px;box-shadow:0 24px 60px -20px rgba(0,0,0,.5);overflow:hidden;}
        .lock-top{background:{{ $isUpcoming ? '#1e3a8a' : '#7f1d1d' }};padding:34px 32px 28px;text-align:center;color:#fff;}
        .lock-badge{width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;}
        .lock-badge svg{width:36px;height:36px;stroke:#fff;}
        .lock-school{font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;opacity:.8;margin-bottom:6px;}
        .lock-title{font-size:1.5rem;font-weight:800;}
        .lock-body{padding:28px 32px 32px;text-align:center;}
        .lock-msg{font-size:1rem;color:#334155;line-height:1.6;}
        .lock-msg strong{color:#0f172a;}
        .lock-pill{display:inline-flex;align-items:center;gap:8px;margin-top:18px;padding:8px 14px;border-radius:999px;background:#f1f5f9;color:#475569;font-size:.82rem;font-weight:600;}
        .lock-note{margin-top:20px;padding-top:18px;border-top:1px solid #eef2f7;font-size:.82rem;color:#64748b;line-height:1.6;}
        .lock-foot{padding:16px 32px;background:#f8fafc;border-top:1px solid #eef2f7;text-align:center;}
        .lock-btn{display:inline-block;padding:11px 24px;border-radius:10px;background:#5a57d6;color:#fff;font-weight:700;font-size:.88rem;text-decoration:none;}
        .lock-btn:hover{background:#4845b8;}
    </style>
</head>
<body>
    <div class="lock-card">
        <div class="lock-top">
            <div class="lock-badge">
                @if ($isUpcoming)
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                @endif
            </div>
            @if (!empty($schoolName))<div class="lock-school">{{ $schoolName }}</div>@endif
            <div class="lock-title">{{ $isUpcoming ? 'Enrollment Not Yet Open' : 'Enrollment Closed' }}</div>
        </div>

        <div class="lock-body">
            @if ($isUpcoming)
                <p class="lock-msg">
                    Enrollment for <strong>{{ $sessionTitle }}</strong>
                    @if ($startLabel) will open on <strong>{{ $startLabel }}</strong>@endif.
                    Please check back on or after the opening date.
                </p>
                @if ($startLabel)
                    <div class="lock-pill">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Opens {{ $startLabel }}
                    </div>
                @endif
            @else
                <p class="lock-msg">
                    The enrollment for <strong>{{ $sessionTitle }}</strong>
                    @if ($endLabel) is closed as of <strong>{{ $endLabel }}</strong>@else is now closed @endif.
                    The enrollment form is no longer accepting submissions.
                </p>
                @if ($endLabel)
                    <div class="lock-pill">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Closed on {{ $endLabel }}
                    </div>
                @endif
            @endif

            <p class="lock-note">
                If you still intend to enroll, please contact the Admissions Office —
                the enrollment period may be extended or reopened.
            </p>
        </div>

        <div class="lock-foot">
            <a href="{{ url('/') }}" class="lock-btn">Return Home</a>
        </div>
    </div>
</body>
</html>
