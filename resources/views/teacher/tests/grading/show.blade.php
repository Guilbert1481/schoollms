@extends('layouts.app')

@section('page-title', 'Grade attempt — '.$student)

@section('content')
<div id="egRoot" class="w-full space-y-6">
<style>
    #egRoot{ --eg-line:#e2e8f0; --eg-muted:#64748b; --eg-ink:#0f172a; --eg-accent:#6d28d9;
        --eg-surface:#fff; --eg-surface2:#f8fafc; }
    #egRoot .eg-head{ display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap }
    #egRoot .eg-title{ font-size:20px;font-weight:800;color:var(--eg-ink) }
    #egRoot .eg-sub{ font-size:13px;color:var(--eg-muted);margin-top:2px }
    #egRoot .eg-back{ font-size:13px;color:var(--eg-accent);text-decoration:none;font-weight:600 }
    #egRoot .eg-flash{ background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;border-radius:10px;padding:10px 14px;font-size:14px }
    #egRoot .eg-summary{ display:flex;gap:22px;flex-wrap:wrap;background:var(--eg-surface);border:1px solid var(--eg-line);
        border-radius:12px;padding:14px 18px }
    #egRoot .eg-stat .k{ font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--eg-muted) }
    #egRoot .eg-stat .v{ font-size:18px;font-weight:800;color:var(--eg-ink) }
    #egRoot .eg-integrity{ background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:12px;padding:12px 16px;font-size:13px }
    #egRoot .eg-integrity b{ color:#b45309 }
    #egRoot .eg-integrity .tags{ margin-top:6px;display:flex;gap:8px;flex-wrap:wrap }
    #egRoot .eg-integrity .tag{ background:#fff;border:1px solid #fde68a;border-radius:999px;padding:2px 9px;font-size:12px }
    #egRoot .eg-qcard{ background:var(--eg-surface);border:1px solid var(--eg-line);border-radius:12px;overflow:hidden }
    #egRoot .eg-qhead{ display:flex;justify-content:space-between;align-items:flex-start;gap:12px;
        padding:14px 18px;border-bottom:1px solid var(--eg-line);background:var(--eg-surface2) }
    #egRoot .eg-qhead .q{ font-weight:700;color:var(--eg-ink);max-width:70ch }
    #egRoot .eg-qhead .pts{ white-space:nowrap;font-size:12px;color:var(--eg-muted) }
    #egRoot .eg-essay{ padding:16px 18px;white-space:pre-wrap;font-size:15px;line-height:1.6;color:var(--eg-ink);
        max-width:80ch }
    #egRoot .eg-essay.empty{ color:var(--eg-muted);font-style:italic }
    #egRoot .eg-score{ display:flex;align-items:center;gap:10px;padding:12px 18px;border-top:1px solid var(--eg-line);
        background:var(--eg-surface2) }
    #egRoot .eg-score label{ font-size:13px;font-weight:700;color:var(--eg-ink) }
    #egRoot .eg-score input{ width:90px;font:inherit;border:1px solid var(--eg-line);border-radius:8px;padding:7px 10px }
    #egRoot .eg-score .of{ font-size:13px;color:var(--eg-muted) }
    #egRoot .eg-score .done{ font-size:12px;font-weight:700;color:#047857;margin-left:auto }
    #egRoot .eg-actions{ display:flex;justify-content:flex-end;gap:10px }
    #egRoot .eg-save{ background:var(--eg-accent);color:#fff;border:0;border-radius:8px;padding:11px 20px;
        font:inherit;font-weight:700;cursor:pointer }
    #egRoot .eg-none{ background:var(--eg-surface);border:1px solid var(--eg-line);border-radius:12px;
        padding:28px;text-align:center;color:var(--eg-muted) }
</style>

    <div class="eg-head">
        <div>
            <div class="eg-title">Grading: {{ $student }}</div>
            <div class="eg-sub">{{ $test->title }}</div>
        </div>
        <a class="eg-back" href="{{ route('teacher.tests.grade', $test) }}">← All attempts</a>
    </div>

    @if (session('success'))
        <div class="eg-flash">{{ session('success') }}</div>
    @endif

    <div class="eg-summary">
        <div class="eg-stat">
            <div class="k">Status</div>
            <div class="v">{{ $attempt->needs_manual ? 'Provisional' : 'Final' }}</div>
        </div>
        <div class="eg-stat">
            <div class="k">Current score</div>
            <div class="v">{{ $attempt->percentage }}% <span style="font-size:13px;color:var(--eg-muted);font-weight:600">({{ $attempt->raw_score }}/{{ $attempt->max_score }})</span></div>
        </div>
        <div class="eg-stat">
            <div class="k">Auto-graded items</div>
            <div class="v">{{ rtrim(rtrim(number_format($autoEarned, 2), '0'), '.') }}/{{ rtrim(rtrim(number_format($autoMax, 2), '0'), '.') }} <span style="font-size:13px;color:var(--eg-muted);font-weight:600">({{ $autoCount }})</span></div>
        </div>
    </div>

    @if ($proctor && ($proctor['total'] ?? 0) > 0)
        <div class="eg-integrity">
            This student left the test tab/window <b>{{ $proctor['total'] }}</b> time{{ $proctor['total'] == 1 ? '' : 's' }} during the sitting.
            <div class="tags">
                @foreach (($proctor['counts'] ?? []) as $type => $n)
                    <span class="tag">{{ str_replace('_', ' ', $type) }} · {{ $n }}</span>
                @endforeach
            </div>
        </div>
    @endif

    @if ($essays->isEmpty())
        <div class="eg-none">This attempt has no essay answers to grade.</div>
    @else
        <form method="POST" action="{{ route('teacher.tests.grade.store', [$test, $attempt]) }}" class="space-y-6">
            @csrf
            @foreach ($essays as $i => $e)
                <div class="eg-qcard">
                    <div class="eg-qhead">
                        <div class="q">{{ $i + 1 }}. {{ $e['question'] }}</div>
                        <div class="pts">{{ rtrim(rtrim(number_format($e['possible'], 2), '0'), '.') }} pt{{ $e['possible'] == 1 ? '' : 's' }}</div>
                    </div>
                    @if (trim($e['text']) === '')
                        <div class="eg-essay empty">— No answer submitted —</div>
                    @else
                        <div class="eg-essay">{{ $e['text'] }}</div>
                    @endif
                    <div class="eg-score">
                        <label for="score-{{ $e['id'] }}">Score</label>
                        <input type="number" id="score-{{ $e['id'] }}" name="scores[{{ $e['id'] }}]"
                               min="0" max="{{ $e['possible'] }}" step="0.5"
                               value="{{ $e['earned'] !== null ? rtrim(rtrim(number_format($e['earned'], 2), '0'), '.') : '' }}"
                               placeholder="0">
                        <span class="of">of {{ rtrim(rtrim(number_format($e['possible'], 2), '0'), '.') }}</span>
                        @unless ($e['needsManual'])
                            <span class="done">✓ scored</span>
                        @endunless
                    </div>
                </div>
            @endforeach

            <div class="eg-actions">
                <button type="submit" class="eg-save">Save scores</button>
            </div>
        </form>
    @endif
</div>
@endsection
