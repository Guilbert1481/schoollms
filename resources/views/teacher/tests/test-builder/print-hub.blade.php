@extends('layouts.app')

@section('page-title', 'Print')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Print &amp; Answer Sheets</h1>
        <p class="text-sm text-slate-500">
            {{ $test->title }} · {{ $test->subject?->name ?? 'Subject' }} — the questionnaire, answer key, and answer
            sheets all follow the same arrangement, so Reshuffle keeps them in sync.
        </p>
    </div>

    {{-- Scoped, build-independent styles (avoids depending on the Tailwind build). --}}
    <style>
        .ph-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
        .ph-tabs { display: inline-flex; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 10px; padding: 4px; gap: 4px; }
        .ph-tab { border: 0; background: transparent; color: #475569; font-weight: 600; font-size: 14px; padding: 8px 16px; border-radius: 7px; cursor: pointer; }
        .ph-tab.is-active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(15,23,42,.12); }
        .ph-reshuffle { display: inline-flex; align-items: center; gap: 7px; border: 0; background: #0284c7; color: #fff; font-weight: 700; font-size: 14px; padding: 9px 16px; border-radius: 8px; cursor: pointer; }
        .ph-reshuffle:disabled { opacity: .6; cursor: default; }
        .ph-note { font-size: 12px; color: #64748b; margin-top: 6px; }
        .ph-frame-wrap { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; }
        .ph-frame { width: 100%; height: 78vh; border: 0; display: block; background: #fff; }
    </style>

    <div>
        <div class="ph-bar">
            <div class="ph-tabs" id="phTabs">
                <button type="button" class="ph-tab is-active" data-url="{{ route('teacher.tests.print', $test) }}">Questionnaire</button>
                <button type="button" class="ph-tab" data-url="{{ route('teacher.tests.answer-sheets', $test) }}">Answer Sheet</button>
                <button type="button" class="ph-tab" data-url="{{ route('teacher.tests.print-answer-key', $test) }}">Answer Key</button>
            </div>
            <button type="button" class="ph-reshuffle" id="phReshuffle">
                <span aria-hidden="true">⟳</span> Reshuffle
            </button>
        </div>

        <div class="ph-frame-wrap">
            <iframe id="phFrame" class="ph-frame" src="{{ route('teacher.tests.print', $test) }}" title="Print preview"></iframe>
        </div>
        <p class="ph-note">Reshuffle re-orders the questions and regenerates the answer sheets. Any sheets you already
            printed become void — reprint after reshuffling.</p>
    </div>

</div>

<script>
    (function () {
        const CSRF = @json(csrf_token());
        const RESHUFFLE_URL = @json(route('teacher.tests.reshuffle', $test));
        const tabs = document.getElementById('phTabs');
        const frame = document.getElementById('phFrame');
        const reshuffleBtn = document.getElementById('phReshuffle');

        // Track the active tab's base URL so we can force-reload it after a reshuffle.
        let currentUrl = frame.getAttribute('src');

        tabs.addEventListener('click', function (e) {
            const btn = e.target.closest('.ph-tab');
            if (!btn) return;
            tabs.querySelectorAll('.ph-tab').forEach((b) => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            currentUrl = btn.dataset.url;
            frame.src = currentUrl;
        });

        reshuffleBtn.addEventListener('click', async function () {
            if (!confirm('Reshuffle the questions? This regenerates the answer sheets and voids any already-printed ones.')) {
                return;
            }
            reshuffleBtn.disabled = true;
            try {
                const res = await fetch(RESHUFFLE_URL, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    credentials: 'include',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    alert(data.message || 'Reshuffle failed.');
                    return;
                }
                // Reload the visible tab from the new arrangement (cache-busted).
                frame.src = currentUrl + (currentUrl.includes('?') ? '&' : '?') + '_=' + Date.now();
            } catch (err) {
                alert('Something went wrong: ' + err.message);
            } finally {
                reshuffleBtn.disabled = false;
            }
        });
    })();
</script>
@endsection
