@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/mcq.css') }}">

<div class="space-y-4">


    <div class="main-content-container">

        <div class="questionsWrapper">
            <div id="questionsContainer">
            </div>
        </div>
        <div class="test-builder-actions">
            <button type="button" id="addQuestionBtn" class="tb-btn primary">➕ Add Questions</button>
            <button
                type="button"
                id="saveTestBtn"
                class="tb-btn dark"
            >
                💾 Save
            </button>
        </div>
    </div>

</div>

{{-- AI generate modal (opened by the ✨ AI button in each question card) --}}
<div id="aiModal" class="ai-overlay" hidden>
    <div class="ai-dialog" role="dialog" aria-modal="true">
        <div class="ai-dialog-head">
            <h3>✨ Generate with AI</h3>
            <button type="button" id="aiClose" class="ai-x" aria-label="Close">✕</button>
        </div>
        <p class="ai-sub">Uses this test's subject, topic, lesson and competency. The questions fill the builder for you to review before saving.</p>
        <div class="ai-grid">
            <label>Number of questions
                <input type="number" id="aiNumQuestions" min="1" max="20" value="10">
            </label>
            <label>Choices per question
                <input type="number" id="aiNumChoices" min="2" max="6" value="4">
            </label>
        </div>
        <div id="aiError" class="ai-error" hidden></div>
        <div class="ai-actions">
            <button type="button" id="aiCancel" class="tb-btn">Cancel</button>
            <button type="button" id="aiCreate" class="tb-btn primary">Create</button>
        </div>
    </div>
</div>

<style>
    /* Scoped, build-independent styles (avoids relying on the compiled Tailwind build). */
    .ai-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); display: flex; align-items: center; justify-content: center; z-index: 60; padding: 1rem; }
    .ai-overlay[hidden] { display: none; }
    .ai-dialog { background: #fff; border-radius: 16px; box-shadow: 0 20px 50px -12px rgba(2,6,23,.35); width: 100%; max-width: 30rem; padding: 1.5rem; }
    .ai-dialog-head { display: flex; align-items: center; justify-content: space-between; }
    .ai-dialog-head h3 { font-size: 1.1rem; font-weight: 800; color: #1e293b; margin: 0; }
    .ai-x { border: 0; background: transparent; font-size: 1rem; color: #94a3b8; cursor: pointer; }
    .ai-sub { font-size: .8rem; color: #64748b; margin: .25rem 0 1rem; }
    .ai-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
    .ai-grid label { display: flex; flex-direction: column; gap: .35rem; font-size: .72rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .03em; }
    .ai-grid input { padding: .55rem .7rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: .95rem; color: #1e293b; font-weight: 600; }
    .ai-grid input:focus { outline: none; border-color: #6366f1; }
    .ai-error { margin-top: .9rem; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-size: .8rem; padding: .5rem .7rem; border-radius: 10px; }
    .ai-error[hidden] { display: none; }
    .ai-actions { display: flex; justify-content: flex-end; gap: .5rem; margin-top: 1.25rem; }
    .ai-actions .tb-btn[disabled] { opacity: .6; cursor: not-allowed; }
    .ai-gen-btn { border: 0; background: #eef2ff; color: #4f46e5; font-weight: 700; font-size: .78rem; padding: .3rem .6rem; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: .25rem; }
    .ai-gen-btn:hover { background: #e0e7ff; }
    /* ✨ AI generate icon hidden for now — the backend/modal stay wired; delete this one rule to restore it. */
    .ai-gen-btn { display: none; }
</style>


<script>
       window.csrfToken = @json(csrf_token());
</script>

<script src="{{ asset('js/tests/mcq.js') }}?v={{ time() }}"></script>


<script>
    console.log('INLINE SCRIPT LOADED');
</script>

@endsection
