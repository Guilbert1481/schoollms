@extends('layouts.app')

@section('page-subtitle', 'Define where your questions belong')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<body data-page="question-metadata">


<link rel="stylesheet" href="{{ asset('css/question-metadata.css') }}">

<div class="dashboard-content-rail">
  <div class="main-content-container">

    <form id="question-metadataForm" action="{{ route('teacher.question.metadata.save') }}" method="POST">
      @csrf
      
      <div class="ti-layout">

        <!-- LEFT -->
        <div class="ti-card">
          <h2>Question Classification</h2>
          @include('components.cascading-dropdown', ['subjects' => $subjects])
        </div>

        <!-- RIGHT -->
        <div class="ti-card">
          <h2>Question Settings</h2>

          <select id="academic_level_id" name="academic_level_id" required>
              <option value="">Select</option>
              @foreach($academicLevels as $level)
                  <option value="{{ $level->id }}">{{ $level->name }}</option>
              @endforeach
          </select>



          <div class="form-row">
            <label>Question Type</label>
            <select name="question_type" class="ti-input" required>
              <option value="">Select</option>
              <option value="mcq">Multiple Choice</option>
              <option value="true_or_false">True / False</option>
              <option value="mtf">Modified  True / False</option>
              <option value="identification">Identification</option>
              <option value="fib">Fill in the Blank</option>
              <option value="matching">Matching Type</option>
              <option value="enumeration">Enumeration</option>
              <option value="essay">Essay</option>
            </select>
          </div>

          <div class="ti-actions">
            <button type="submit" class="ti-btn-primary">Proceed</button>
            </a>
          </div>
        </div>

      </div>
    </form>

  </div>
</div>



<script src="{{ asset('js/tests/question-metadata.js') }}"></script>



@endsection
