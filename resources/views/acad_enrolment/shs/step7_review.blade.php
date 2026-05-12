@extends('layouts.enrollment')

@section('content')
<div class="step-indicator">STEP 8 OF 9</div>
<div class="progress-bar"><div class="progress-fill" style="width:88%"></div></div>

<h3 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:8px;">Review &amp; Submit</h3>
<p style="color:#64748b;font-size:13px;margin-bottom:24px;">
    Please review every section before submitting. Approval will route to:
    <strong style="color:#5a57d6;">{{ str_replace('_',' ', $approval) }}</strong>.
</p>

@if ($errors->any())
    <div style="padding:16px;background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;color:#991b1b;margin-bottom:20px;">
        <strong>Cannot submit yet:</strong>
        <ul style="margin:8px 0 0 18px;">
            @foreach ($errors->all() as $err)
                @if (is_array($err))
                    @foreach ($err as $msg) <li>{{ $msg }}</li> @endforeach
                @else
                    <li>{{ $err }}</li>
                @endif
            @endforeach
        </ul>
    </div>
@endif

@if ($result->hasWarnings())
    <div style="padding:16px;background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;color:#92400e;margin-bottom:20px;">
        <strong>Heads up:</strong>
        <ul style="margin:8px 0 0 18px;">
            @foreach ($result->toArray()['warnings'] ?? [] as $w)
                <li>{{ $w['message'] ?? '' }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $sectionTitle = fn ($t) => "<h4 style=\"font-size:12px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin:24px 0 12px 0;\">{$t}</h4>";
@endphp

{!! $sectionTitle('Personal') !!}
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px;display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:14px;">
    <div><strong>Name:</strong> {{ $student->full_name }}</div>
    <div><strong>Date of Birth:</strong> {{ $student->date_of_birth }}</div>
    <div><strong>Gender:</strong> {{ ucfirst($student->gender) }}</div>
    <div><strong>Email:</strong> {{ $student->email }}</div>
    <div><strong>Mobile:</strong> {{ $student->mobile_number }}</div>
    <div><strong>Address:</strong> {{ $student->street }}, {{ $student->barangay }}, {{ $student->city_municipality }}</div>
</div>

{!! $sectionTitle('Family & Emergency') !!}
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px;font-size:14px;">
    @forelse ($parents as $p)
        <div style="margin-bottom:8px;"><strong>{{ ucfirst($p->relationship ?? 'Parent') }}:</strong>
            {{ $p->full_name }} · {{ $p->mobile_number }}{{ $p->email ? ' · '.$p->email : '' }}</div>
    @empty
        <div style="color:#64748b;">No parent records.</div>
    @endforelse
    @if ($emergency)
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;">
            <strong>Emergency:</strong> {{ $emergency->full_name }} ({{ $emergency->relationship }}) · {{ $emergency->mobile_number }}
        </div>
    @endif
</div>

{!! $sectionTitle('Academic Background') !!}
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px;font-size:14px;">
    @forelse ($backgrounds as $b)
        <div style="margin-bottom:6px;">
            <strong>{{ ucfirst(str_replace('_',' ', $b->education_level)) }}:</strong>
            {{ $b->school_name }}
            @if ($b->year_ended) ({{ $b->year_ended }}) @endif
            @if ($b->last_grade_level) — last completed: {{ $b->last_grade_level }} @endif
        </div>
    @empty
        <div style="color:#64748b;">No previous schools recorded.</div>
    @endforelse
</div>

{!! $sectionTitle('Strand & Program') !!}
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px;display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:14px;">
    <div><strong>Program:</strong> #{{ $program['program_id'] }}</div>
    <div><strong>Strand:</strong> {{ $program['strand'] ?? '—' }}</div>
    <div><strong>Grade Level:</strong> {{ $program['academic_level'] ?? '—' }}</div>
    <div><strong>Education Level:</strong> Senior High School</div>
    <div><strong>Enrollee Type:</strong> {{ ucfirst($program['enrollee_type'] ?? 'new') }}</div>
    <div><strong>Program Type:</strong> {{ ucfirst(str_replace('_',' ', $program['program_type'] ?? 'regular')) }}</div>
    <div><strong>Section:</strong> {{ $section?->name ?? 'To be assigned' }}</div>
    <div><strong>Term:</strong> {{ $term->name }}</div>
</div>

<form method="POST" action="{{ route('public.apply.shs.submit', $term->id) }}" style="margin-top:32px;">
    @csrf
    <div class="form-footer" style="display:flex;justify-content:space-between;padding-top:20px;border-top:1px solid #e2e8f0;">
        <a href="{{ route('public.apply.shs.step6', $term->id) }}" class="btn btn-back">Back</a>
        <button type="submit" class="btn btn-next">Submit Enrolment</button>
    </div>
</form>
@endsection
