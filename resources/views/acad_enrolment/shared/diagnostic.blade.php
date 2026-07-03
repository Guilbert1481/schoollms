@extends('layouts.enrollment')

@section('content')
@php
    $schLabel = $scholarship
        ? ($scholarship['label']
            ?: (rtrim(rtrim(number_format($scholarship['percent'], 2), '0'), '.').'% '.($scholarship['apply_to'] === 'tuition' ? 'tuition fee' : 'assessment').' scholarship'))
        : null;
@endphp

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none!important;}</style>

<div style="max-width:920px; margin:0 auto;">

    <div style="margin-bottom:1.4rem;">
        <div style="font-size:0.72rem; font-weight:800; letter-spacing:0.12em; color:#8b93a7; text-transform:uppercase;">Step 7 &mdash; {{ $label }}</div>
        <h1 style="font-size:2rem; font-weight:800; color:#16223e; margin:0.35rem 0 0.3rem;">{{ $label }}</h1>
        <p style="font-size:0.9rem; color:#64748b;">Answer all {{ count($questions) }} questions. Your score may qualify you for a scholarship before the Financial Assessment.</p>
    </div>

    @if ($errors->any())
        <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; border-radius:10px; padding:0.6rem 0.9rem; margin-bottom:1rem; font-size:0.85rem;">{{ $errors->first() }}</div>
    @endif

    @if (! $result)
        {{-- ================= STATE 1 — QUESTIONNAIRE ================= --}}
        <form method="POST" action="{{ route('public.apply.diagnostic.store', $term->id) }}">
            @csrf

            @if (!empty($instructions))
                <div style="background:#eef2ff; border:1px solid #e0e7ff; border-radius:12px; padding:0.9rem 1rem; margin-bottom:1.2rem; color:#3730a3; font-size:0.85rem; white-space:pre-line;">{{ $instructions }}</div>
            @endif

            @foreach ($questions as $i => $q)
                <div style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1.1rem 1.3rem; margin-bottom:1rem; box-shadow:0 10px 30px -20px rgba(16,34,62,.35);">
                    <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.7rem;">
                        <span style="font-size:0.64rem; font-weight:800; letter-spacing:0.06em; text-transform:uppercase; color:#4f46e5; background:#eef2ff; border:1px solid #e0e7ff; border-radius:999px; padding:0.2rem 0.6rem;">{{ $q['subject'] }}</span>
                        <span style="font-size:0.72rem; color:#94a3b8; font-weight:700;">Question {{ $i + 1 }} of {{ count($questions) }}</span>
                    </div>
                    <div style="font-size:1rem; font-weight:700; color:#16223e; margin-bottom:0.8rem;">{{ $q['question'] }}</div>
                    <div style="display:grid; gap:0.5rem;">
                        @foreach ($q['options'] as $oi => $opt)
                            <label style="display:flex; align-items:center; gap:0.7rem; border:1px solid #cbd5e1; border-radius:10px; padding:0.65rem 0.85rem; cursor:pointer; font-size:0.9rem; color:#334155;">
                                <input type="radio" name="answers[{{ $i }}]" value="{{ $oi }}" {{ (string) old("answers.$i") === (string) $oi ? 'checked' : '' }} required style="width:1rem; height:1rem; flex:0 0 auto;">
                                {{ $opt }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.4rem;">
                <a href="{{ route('public.apply.health', $term->id) }}" style="padding:0.7rem 1.3rem; border-radius:10px; background:#e2e8f0; color:#475569; font-weight:800; font-size:0.85rem; text-decoration:none;">&larr; Back</a>
                <button type="submit" style="padding:0.75rem 1.8rem; border-radius:10px; background:#5a57d6; color:#fff; font-weight:800; font-size:0.9rem; border:none; cursor:pointer; box-shadow:0 10px 15px -3px rgba(90,87,214,.4);">Submit Answers</button>
            </div>
        </form>

    @else
        {{-- ================= STATE 2 — RESULT ================= --}}
        <div x-data="{ open: {{ $scholarship ? 'true' : 'false' }} }">

            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:1.6rem; text-align:center; box-shadow:0 10px 30px -20px rgba(16,34,62,.35);">
                <div style="font-size:0.72rem; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#8b93a7;">Your Result</div>
                <div style="font-size:3rem; font-weight:800; color:#16223e; margin:0.3rem 0; line-height:1;">{{ $result['score'] }}<span style="font-size:1.3rem; color:#94a3b8;"> / {{ $result['max_score'] }}</span></div>
                @if (isset($result['correct']))
                    <div style="font-size:0.9rem; color:#64748b;">{{ $result['correct'] }} of {{ $result['total'] }} correct</div>
                @endif

                @if ($scholarship)
                    <div style="margin-top:1.1rem; background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; border-radius:12px; padding:0.85rem 1rem; font-weight:700; font-size:0.92rem;">🎉 You are eligible for a {{ $schLabel }}!</div>
                @endif

                <div style="display:flex; justify-content:center; gap:0.8rem; margin-top:1.5rem; flex-wrap:wrap;">
                    <a href="{{ route('public.apply.diagnostic', ['term' => $term->id, 'retake' => 1]) }}" style="padding:0.7rem 1.3rem; border-radius:10px; background:#e2e8f0; color:#475569; font-weight:800; font-size:0.85rem; text-decoration:none;">Retake</a>
                    <a href="{{ route('public.apply.financial', $term->id) }}" style="padding:0.7rem 1.8rem; border-radius:10px; background:#5a57d6; color:#fff; font-weight:800; font-size:0.9rem; text-decoration:none; box-shadow:0 10px 15px -3px rgba(90,87,214,.4);">Proceed to Financial Assessment &rarr;</a>
                </div>
            </div>

            {{-- Congratulations popup --}}
            @if ($scholarship)
                <div x-show="open" x-cloak style="position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,.6); display:flex; align-items:center; justify-content:center; padding:1rem;" @click.self="open=false">
                    <div style="background:#fff; border-radius:20px; max-width:30rem; width:100%; padding:2rem; text-align:center; box-shadow:0 30px 60px -20px rgba(0,0,0,.5);">
                        <div style="font-size:3rem; line-height:1;">🎉</div>
                        <h2 style="font-size:1.5rem; font-weight:800; color:#16223e; margin:0.8rem 0 0.4rem;">Congratulations!</h2>
                        <p style="font-size:1rem; color:#475569; line-height:1.5;">You are eligible for a <strong style="color:#047857;">{{ $schLabel }}</strong>.</p>
                        <p style="font-size:0.82rem; color:#94a3b8; margin-top:0.5rem;">Your score: {{ $result['score'] }} / {{ $result['max_score'] }}. The discount will be applied on your Financial Assessment.</p>
                        <a href="{{ route('public.apply.financial', $term->id) }}" style="display:inline-block; margin-top:1.4rem; padding:0.8rem 2rem; border-radius:12px; background:#059669; color:#fff; font-weight:800; font-size:0.95rem; text-decoration:none; box-shadow:0 12px 20px -8px rgba(5,150,105,.6);">Proceed &rarr;</a>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
