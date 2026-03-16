

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

<script>
    window.csrfToken = @json(csrf_token());
</script>
<script src="{{ asset('js/tests/tf.js') }}?v={{ time() }}"></script>
@endsection