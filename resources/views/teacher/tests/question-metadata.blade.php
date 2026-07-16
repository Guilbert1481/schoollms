@extends('layouts.app')

@section('page-subtitle', 'Define where your questions belong')

@section('page-title', 'Question Metadata')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<body data-page="question-metadata">


<link rel="stylesheet" href="{{ asset('css/question-metadata.css') }}">

<div class="dashboard-content-rail">
  <div class="main-content-container">

    <div style="margin-bottom: 20px;">
      <h1 class="text-2xl font-bold text-slate-800">Question Metadata</h1>
    </div>

    <form id="question-metadataForm" action="{{ route('teacher.question.metadata.save') }}" method="POST">
      @csrf

      <div class="ti-layout">

        {{-- LEFT — Assessment Level (mirrors the Test Builder's Test Controls;
             single-select since a question carries exactly one level). --}}
        <div class="ti-card">
          <h2>Assessment Level</h2>
          @include('components.education-levels', [
              'levelTree'      => $levelTree,
              'levelsByNode'   => $levelsByNode,
              'academicLevels' => $academicLevels,
              'multiple'       => false,
              'name'           => 'academic_level_id',
          ])
        </div>

        {{-- RIGHT — Question Classification (the Test Builder's Test Source:
             Subject → Topic → Lesson → Competency, ungated for authoring). --}}
        <div class="ti-card">
          <h2>Question Classification</h2>
          @include('components.cascading-dropdown', ['subjects' => $subjects])
        </div>

      </div>

      {{-- Question Type + Proceed (full-width action row, like the Test
           Builder's Render row below its two cards). --}}
      <div class="qm-action-row">
        <div class="qm-qtype">
          <label for="qm-question-type">Question Type</label>
          <select id="qm-question-type" name="question_type" class="ti-input" required>
            <option value="">Select</option>
            <option value="mcq">Multiple Choice</option>
            <option value="true_or_false">True / False</option>
            <option value="mtf">Modified True / False</option>
            <option value="identification">Identification</option>
            <option value="fib">Fill in the Blank</option>
            <option value="matching">Matching Type</option>
            <option value="enumeration">Enumeration</option>
            <option value="essay">Essay</option>
          </select>
        </div>

        <button type="submit" class="ti-btn-primary">Proceed</button>
      </div>

    </form>

  </div>
</div>

<style>
  /* Action row under the two cards — Question Type + Proceed. */
  .qm-action-row {
      margin-top: 24px;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      gap: 20px;
      flex-wrap: wrap;
  }
  .qm-action-row .qm-qtype { min-width: 260px; }
  .qm-action-row .qm-qtype label { display: block; margin-bottom: 6px; }
</style>

<script src="{{ asset('js/tests/question-metadata.js') }}"></script>

@endsection
