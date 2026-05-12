@extends('layouts.enrollment')

@section('content')
<div class="step-indicator">STEP 7 OF 9</div>
<div class="progress-bar"><div class="progress-fill" style="width:77%"></div></div>

<h3 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:8px;">Choose Your Block Section</h3>
<p style="color:#64748b;font-size:13px;margin-bottom:24px;">
    Block sections come bundled with all subjects scheduled for Year {{ $draft['year_level'] ?? '?' }},
    Semester {{ $draft['semester'] ?? '?' }}. Pick one and the system will enrol you in every class automatically.
</p>

<form method="POST" action="{{ route('public.apply.higher_regular.step6.store', $term->id) }}">
    @csrf
    @if ($sections->isEmpty())
        <div style="padding:24px;background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;color:#991b1b;">
            No block sections have been published for Year {{ $draft['year_level'] ?? '?' }} of this program for
            {{ $term->name }}. Please contact the registrar — you cannot proceed with regular enrolment.
        </div>
        <div class="form-footer" style="display:flex;justify-content:space-between;margin-top:32px;padding-top:20px;border-top:1px solid #e2e8f0;">
            <a href="{{ route('public.apply.higher_regular.step5', $term->id) }}" class="btn btn-back">Back</a>
        </div>
    @else
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            @foreach ($sections as $s)
                @php $remaining = max(0, ($s->capacity ?? 0) - ($s->classes_count ?? 0)); @endphp
                <label style="border:1px solid #e2e8f0;border-radius:12px;padding:18px;cursor:pointer;display:block;background:#fff;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <input type="radio" name="section_id" value="{{ $s->id }}"
                               required @checked((string) $pick === (string) $s->id)>
                        <div style="flex:1;">
                            <div style="font-weight:800;color:#1e293b;font-size:15px;">{{ $s->name }}</div>
                            <div style="font-size:12px;color:#64748b;margin-top:4px;">
                                {{ $s->code }} · Year {{ $s->year_level }} · Capacity {{ $s->capacity }}
                                · {{ $s->classes_count }} classes scheduled
                            </div>
                        </div>
                    </div>
                </label>
            @endforeach
        </div>

        <div class="form-footer" style="display:flex;justify-content:space-between;margin-top:32px;padding-top:20px;border-top:1px solid #e2e8f0;">
            <a href="{{ route('public.apply.higher_regular.step5', $term->id) }}" class="btn btn-back">Back</a>
            <button type="submit" class="btn btn-next">Next Step</button>
        </div>
    @endif
</form>
@endsection
