@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('css/mcq.css') }}">

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

<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
    window.csrfToken = @json(csrf_token());
</script>
<!-- Note the updated path: js/questions/fib.js -->
<script src="{{ asset('js/questions/fib.js') }}?v={{ time() }}"></script>
<x-math-tool />
@endsection

