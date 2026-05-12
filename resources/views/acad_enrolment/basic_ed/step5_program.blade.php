@extends('layouts.enrollment')

@section('content')
<div class="step-indicator">STEP 6 OF 9</div>
<div class="progress-bar"><div class="progress-fill" style="width:66%"></div></div>

<h3 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:8px;">Program &amp; Grade Level</h3>
<p style="color:#64748b;font-size:13px;margin-bottom:24px;">Pick the program your child will enrol in and the grade level for {{ $term->name }}.</p>

<form method="POST" action="{{ route('public.apply.basic.step5.store', $term->id) }}">
    @csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div class="input-group">
            <label>Program*</label>
            <select name="program_id" required>
                <option value="">— Select Program —</option>
                @foreach ($programs as $p)
                    <option value="{{ $p->id }}" @selected(old('program_id', $draft['program_id'] ?? '') == $p->id)>
                        {{ $p->name }} ({{ $p->code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="input-group">
            <label>Education Level*</label>
            @php $lvl = old('education_level', $draft['education_level'] ?? ''); @endphp
            <select name="education_level" required>
                <option value="">— Select —</option>
                <option value="kinder"      @selected($lvl === 'kinder')>Kindergarten</option>
                <option value="elementary"  @selected($lvl === 'elementary')>Elementary</option>
                <option value="junior_high" @selected($lvl === 'junior_high')>Junior High School</option>
            </select>
        </div>

        <div class="input-group">
            <label>Grade Level</label>
            @php $al = old('academic_level', $draft['academic_level'] ?? ''); @endphp
            <select name="academic_level">
                <option value="">— Select —</option>
                @foreach ($levels as $l)
                    <option value="{{ $l->name }}" @selected($al === $l->name)>{{ $l->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="input-group">
            <label>Enrollee Type*</label>
            @php $et = old('enrollee_type', $draft['enrollee_type'] ?? 'new'); @endphp
            <select name="enrollee_type" required>
                <option value="new"        @selected($et === 'new')>New</option>
                <option value="continuing" @selected($et === 'continuing')>Continuing</option>
                <option value="transferee" @selected($et === 'transferee')>Transferee</option>
                <option value="returnee"   @selected($et === 'returnee')>Returnee</option>
            </select>
        </div>

        <div class="input-group">
            <label>Program Type*</label>
            @php $pt = old('program_type', $draft['program_type'] ?? 'regular'); @endphp
            <select name="program_type" required>
                <option value="regular"    @selected($pt === 'regular')>Regular</option>
                <option value="bridging"   @selected($pt === 'bridging')>Bridging</option>
                <option value="non_degree" @selected($pt === 'non_degree')>Non-degree / Special</option>
            </select>
        </div>
    </div>

    <div class="form-footer" style="display:flex;justify-content:space-between;margin-top:32px;padding-top:20px;border-top:1px solid #e2e8f0;">
        <a href="{{ route('public.apply.basic.step4', $term->id) }}" class="btn btn-back">Back</a>
        <button type="submit" class="btn btn-next">Next Step</button>
    </div>
</form>
@endsection
