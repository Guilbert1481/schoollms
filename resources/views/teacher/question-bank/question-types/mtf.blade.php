@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('css/mcq.css') }}">
<style>
    .mtf-correction-fields.error-highlight {
    border: 1px solid #e11d48;
    border-radius: 6px;
    background: #fff0f3;
    padding: 6px;
}
</style>
<div class="space-y-4">
    <div class="main-content-container">
        <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:1.2rem;">Modified True or False</h2>
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
<script src="{{ asset('js/tests/mtf.js') }}?v={{ time() }}"></script>
<x-math-tool />
@endsection