@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('css/mcq.css') }}">
<style>
    .answer-row {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}
.answer-input {
    flex: 1;
    padding: 0.45em 0.8em;
    border: 1px solid #e5e7eb;
    border-radius: 0.35em;
    font-size: 1em;
    background: #fff;
}
.delete-answer {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.1em;
    color: #e11d48;
    margin-left: 8px;
    transition: color 0.1s;
}
.delete-answer:hover {
    color: #b91c1c;
}
#addAnswerBtn {
    display: inline-block;
    margin-top: 2px;
    color: #2563eb;
    background: #f3f4f6;
    border: none;
    border-radius: 0.3em;
    padding: 0.4em 1em;
}
#addAnswerBtn:hover {
    background: #e0e7ff;
}

.add-answer-btn {
    padding: 0.4em 1.2em;
    color: #fff;
    background: #3b82f6;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    font-size: 1em;
    box-shadow: 0 1px 2px #0002;
    transition: background .15s;
    margin-top: .7em;
}
.add-answer-btn:hover {
    background: #2563eb;
}



</style>
<div class="space-y-4">
    <div class="main-content-container">
        <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:1.2rem;">
            Enumeration
        </h2>
        <div class="questionsWrapper">
            <div id="questionsContainer"></div>
        </div>
        <div class="test-builder-actions">
            <button type="button" id="addQuestionBtn" class="tb-btn primary">➕ Add Questions</button>
            <button type="button" id="saveTestBtn" class="tb-btn dark">💾 Save</button>
        </div>
    </div>
</div>

<script>
    window.csrfToken = @json(csrf_token());
</script>
<script src="{{ asset('js/tests/enumeration.js') }}?v={{ time() }}"></script>
<x-math-tool />
@endsection