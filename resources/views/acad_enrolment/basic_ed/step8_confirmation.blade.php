@extends('layouts.enrollment')

@section('content')
<div class="step-indicator">STEP 9 OF 9</div>
<div class="progress-bar"><div class="progress-fill" style="width:100%"></div></div>

<div style="text-align:center;padding:40px 20px;">
    <div style="font-size:64px;margin-bottom:14px;">✅</div>
    <h2 style="font-size:28px;font-weight:800;color:#1e293b;margin-bottom:12px;">Enrolment Submitted!</h2>
    <p style="color:#475569;font-size:15px;margin-bottom:24px;">
        Reference #: <strong>ENR-{{ str_pad($enrollment->id, 6, '0', STR_PAD_LEFT) }}</strong>
    </p>

    <div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;text-align:left;font-size:14px;">
        <div style="margin-bottom:8px;"><strong>Status:</strong> <span style="color:#5a57d6;">{{ ucfirst($enrollment->status) }}</span></div>
        <div style="margin-bottom:8px;"><strong>Pending Approval:</strong> {{ str_replace('_',' ', $enrollment->approval_level) }}</div>
        <div style="margin-bottom:8px;"><strong>Term:</strong> {{ $term->name }}</div>
        <div style="margin-bottom:8px;"><strong>Education Level:</strong> {{ ucfirst(str_replace('_',' ', $enrollment->education_level)) }}</div>
        @if ($enrollment->remarks)
            <div style="margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;color:#92400e;">
                <strong>Notes:</strong> {{ $enrollment->remarks }}
            </div>
        @endif
    </div>

    <p style="margin-top:32px;color:#64748b;font-size:13px;">
        You will receive an email once your enrolment is reviewed. You may close this page.
    </p>

    <a href="{{ url('/dashboard') }}" class="btn btn-next" style="display:inline-block;margin-top:18px;text-decoration:none;">
        Go to Dashboard
    </a>
</div>
@endsection
