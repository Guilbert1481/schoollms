@extends('layouts.enrollment')

@section('content')
<div class="step-indicator">STEP 6 OF 9</div>
<div class="progress-bar"><div class="progress-fill" style="width:66%"></div></div>

<h3 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:8px;">Strand &amp; Grade Level</h3>
<p style="color:#64748b;font-size:13px;margin-bottom:24px;">
    Choose the SHS strand and the grade level you are entering for {{ $term->name }}.
</p>

<form method="POST" action="{{ route('public.apply.shs.step5.store', $term->id) }}">
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
            <label>Strand*</label>
            @php $sd = old('strand', $draft['strand'] ?? ''); @endphp
            <select name="strand" required>
                <option value="">— Select Strand —</option>
                @foreach ($strands as $code => $label)
                    <option value="{{ $code }}" @selected($sd === $code)>{{ $code }} — {{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="input-group">
            <label>Grade Level*</label>
            @php $al = old('academic_level', $draft['academic_level'] ?? ''); @endphp
            <select name="academic_level" required>
                <option value="">— Select —</option>
                @foreach ($grades as $g)
                    <option value="{{ $g }}" @selected($al === $g)>{{ $g }}</option>
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
        <a href="{{ route('public.apply.shs.step4', $term->id) }}" class="btn btn-back">Back</a>
        <button type="submit" class="btn btn-next">Next Step</button>
    </div>
</form>
@endsection
