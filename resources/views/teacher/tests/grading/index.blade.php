@extends('layouts.app')

@section('page-title', 'Grade — '.$test->title)

@section('content')
<div id="egRoot" class="w-full space-y-6">
<style>
    #egRoot{ --eg-line:#e2e8f0; --eg-muted:#64748b; --eg-ink:#0f172a; --eg-accent:#6d28d9;
        --eg-surface:#fff; --eg-surface2:#f8fafc; }
    #egRoot .eg-head{ display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap }
    #egRoot .eg-title{ font-size:20px;font-weight:800;color:var(--eg-ink) }
    #egRoot .eg-sub{ font-size:13px;color:var(--eg-muted);margin-top:2px }
    #egRoot .eg-back{ font-size:13px;color:var(--eg-accent);text-decoration:none;font-weight:600 }
    #egRoot .eg-card{ background:var(--eg-surface);border:1px solid var(--eg-line);border-radius:12px;overflow:hidden }
    #egRoot table{ width:100%;border-collapse:collapse }
    #egRoot thead th{ text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--eg-muted);
        background:var(--eg-surface2);padding:11px 16px;border-bottom:1px solid var(--eg-line) }
    #egRoot tbody td{ padding:12px 16px;border-bottom:1px solid var(--eg-line);font-size:14px;color:var(--eg-ink) }
    #egRoot tbody tr:last-child td{ border-bottom:0 }
    #egRoot .pill{ display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:3px 10px;font-size:12px;font-weight:700 }
    #egRoot .pill-amber{ background:#fffbeb;border:1px solid #fde68a;color:#b45309 }
    #egRoot .pill-green{ background:#ecfdf5;border:1px solid #a7f3d0;color:#047857 }
    #egRoot .eg-flag{ color:#b45309;font-size:12px;font-weight:700 }
    #egRoot .eg-grade{ display:inline-flex;align-items:center;gap:6px;background:var(--eg-accent);color:#fff;
        border-radius:8px;padding:7px 13px;font-size:13px;font-weight:700;text-decoration:none }
    #egRoot .eg-grade.ghost{ background:transparent;color:var(--eg-accent);border:1px solid var(--eg-accent) }
    #egRoot .eg-empty{ padding:36px;text-align:center;color:var(--eg-muted);font-size:14px }
</style>

    <div class="eg-head">
        <div>
            <div class="eg-title">Grade: {{ $test->title }}</div>
            <div class="eg-sub">Score essay answers that couldn't be auto-graded — the auto items are already scored.</div>
        </div>
        <a class="eg-back" href="{{ route('teacher.tests.management') }}">← Test Management</a>
    </div>

    <div class="eg-card">
        @if ($rows->isEmpty())
            <div class="eg-empty">No submitted attempts yet.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Student</th><th>Status</th><th>Score</th><th>Integrity</th><th style="width:120px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr>
                            <td style="font-weight:600">{{ $r['student'] }}</td>
                            <td>
                                @if ($r['needsManual'])
                                    <span class="pill pill-amber">Needs grading</span>
                                @else
                                    <span class="pill pill-green">Graded</span>
                                @endif
                            </td>
                            <td>
                                @if ($r['needsManual'])
                                    <span style="color:var(--eg-muted)">{{ $r['raw'] }}/{{ $r['max'] }} · provisional</span>
                                @else
                                    <strong>{{ $r['percentage'] }}%</strong>
                                    <span style="color:var(--eg-muted)">({{ $r['raw'] }}/{{ $r['max'] }})</span>
                                @endif
                            </td>
                            <td>
                                @if ($r['proctorTotal'] > 0)
                                    <span class="eg-flag" title="Left the test tab / window">⚑ {{ $r['proctorTotal'] }}</span>
                                @else
                                    <span style="color:var(--eg-muted)">—</span>
                                @endif
                            </td>
                            <td>
                                <a class="eg-grade {{ $r['needsManual'] ? '' : 'ghost' }}"
                                   href="{{ route('teacher.tests.grade.show', [$test, $r['id']]) }}">
                                    {{ $r['needsManual'] ? 'Grade' : 'Review' }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
