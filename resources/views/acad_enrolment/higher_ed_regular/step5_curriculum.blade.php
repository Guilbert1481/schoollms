@extends('layouts.enrollment')

@section('content')
<div class="step-indicator">STEP 6 OF 9</div>
<div class="progress-bar"><div class="progress-fill" style="width:66%"></div></div>

<h3 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:8px;">Program &amp; Curriculum</h3>
<p style="color:#64748b;font-size:13px;margin-bottom:24px;">
    Choose your program, year level and semester. The system will pull the right block sections in the next step.
</p>

<form method="POST" action="{{ route('public.apply.higher_regular.step5.store', $term->id) }}">
    @csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div class="input-group">
            <label>Program*</label>
            <select id="program-select" name="program_id" required>
                <option value="">— Select Program —</option>
                @foreach ($programs as $p)
                    <option value="{{ $p->id }}" @selected(old('program_id', $draft['program_id'] ?? '') == $p->id)>
                        {{ $p->name }} ({{ $p->code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="input-group">
            <label>Year Level*</label>
            @php $yl = (string) old('year_level', $draft['year_level'] ?? ''); @endphp
            <select name="year_level" required>
                <option value="">— Select —</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}" @selected($yl === (string) $y)>Year {{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="input-group">
            <label>Semester*</label>
            @php $sm = (string) old('semester', $draft['semester'] ?? ''); @endphp
            <select name="semester" required>
                <option value="">— Select —</option>
                @foreach ($semesters as $code => $label)
                    <option value="{{ $code }}" @selected($sm === (string) $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="input-group">
            <label>Education Level*</label>
            @php $el = old('education_level', $draft['education_level'] ?? 'undergraduate'); @endphp
            <select name="education_level" required>
                <option value="undergraduate" @selected($el === 'undergraduate')>Undergraduate</option>
                <option value="graduate"      @selected($el === 'graduate')>Graduate</option>
            </select>
        </div>

        <div class="input-group">
            <label>Enrollee Type*</label>
            @php $et = old('enrollee_type', $draft['enrollee_type'] ?? 'new'); @endphp
            <select name="enrollee_type" required>
                <option value="new"        @selected($et === 'new')>New / Freshman</option>
                <option value="continuing" @selected($et === 'continuing')>Continuing</option>
                <option value="transferee" @selected($et === 'transferee')>Transferee</option>
                <option value="returnee"   @selected($et === 'returnee')>Returnee</option>
            </select>
        </div>

        <div class="input-group" style="grid-column:span 2;">
            <label>Program Type*</label>
            @php $pt = old('program_type', $draft['program_type'] ?? 'regular'); @endphp
            <select name="program_type" required>
                <option value="regular"    @selected($pt === 'regular')>Regular (Block enrolment)</option>
                <option value="bridging"   @selected($pt === 'bridging')>Bridging</option>
                <option value="non_degree" @selected($pt === 'non_degree')>Non-degree / Special</option>
            </select>
        </div>
    </div>

    <div class="form-footer" style="display:flex;justify-content:space-between;margin-top:32px;padding-top:20px;border-top:1px solid #e2e8f0;">
        <a href="{{ route('public.apply.higher_regular.step4', $term->id) }}" class="btn btn-back">Back</a>
        <button type="submit" class="btn btn-next">Next Step</button>
    </div>
</form>
@endsection
