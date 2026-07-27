@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('css/mcq.css') }}">
<style>
    .question-input-row .question-input {
    min-height: 120px;   /* or 100px, depending on your UI, adjust as needed */
    resize: vertical;   /* allow user to resize vertically, optional */
    width: 100%;
    font-size: 1rem;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #e3e3e3;
    box-sizing: border-box;
}

</style>



<div class="space-y-4">

    <div class="main-content-container">

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
<script src="{{ asset('js/tests/essay.js') }}?v={{ time() }}"></script>
<x-math-tool />
@endsection