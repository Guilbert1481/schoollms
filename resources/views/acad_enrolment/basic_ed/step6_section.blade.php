@extends('layouts.enrollment')

@section('content')
<div class="step-indicator">STEP 7 OF 9</div>
<div class="progress-bar"><div class="progress-fill" style="width:77%"></div></div>

<h3 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:8px;">Section Preference</h3>
<p style="color:#64748b;font-size:13px;margin-bottom:24px;">
    Optional. The school administrator will confirm or reassign during enrolment processing.
</p>

<form method="POST" action="{{ route('public.apply.basic.step6.store', $term->id) }}">
    @csrf
    @if ($sections->isEmpty())
        <div style="padding:24px;background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;color:#92400e;">
            No sections have been published for this program yet. You may continue without selecting one — the
            registrar will assign your section after review.
        </div>
        <input type="hidden" name="section_id" value="">
    @else
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <label style="border:1px solid #e2e8f0;border-radius:12px;padding:16px;cursor:pointer;display:flex;align-items:center;gap:12px;">
                <input type="radio" name="section_id" value="" @checked(empty($pick))>
                <div>
                    <div style="font-weight:700;color:#1e293b;">No preference</div>
                    <div style="font-size:12px;color:#64748b;">Let the registrar assign me.</div>
                </div>
            </label>
            @foreach ($sections as $s)
                <label style="border:1px solid #e2e8f0;border-radius:12px;padding:16px;cursor:pointer;display:flex;align-items:center;gap:12px;">
                    <input type="radio" name="section_id" value="{{ $s->id }}" @checked((string) $pick === (string) $s->id)>
                    <div>
                        <div style="font-weight:700;color:#1e293b;">{{ $s->name }}</div>
                        <div style="font-size:12px;color:#64748b;">
                            {{ $s->code }} · Year {{ $s->year_level }} · Capacity {{ $s->capacity }}
                        </div>
                    </div>
                </label>
            @endforeach
        </div>
    @endif

    <div class="form-footer" style="display:flex;justify-content:space-between;margin-top:32px;padding-top:20px;border-top:1px solid #e2e8f0;">
        <a href="{{ route('public.apply.basic.step5', $term->id) }}" class="btn btn-back">Back</a>
        <button type="submit" class="btn btn-next">Next Step</button>
    </div>
</form>
@endsection
