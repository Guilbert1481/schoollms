@extends('layouts.enrollment')

@section('content')
<div class="step-indicator">STEP 4 OF 9 — CHOOSE TRACK &amp; MODALITY</div>
<div class="progress-bar"><div class="progress-fill" style="width:44%"></div></div>

<h3 style="font-size:22px;font-weight:700;color:#1e293b;margin-bottom:8px;">Which programme are you enrolling in?</h3>
<p style="color:#64748b;font-size:13px;margin-bottom:24px;">
    This determines the rest of the form. You can come back and change it before submitting.
</p>

<form method="POST" action="{{ route('public.apply.track.store', $term->id) }}">
    @csrf
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:18px;">
        <label style="border:2px solid #e2e8f0;border-radius:14px;padding:24px;cursor:pointer;background:#fff;">
            <input type="radio" name="track" value="basic" @checked(($pick ?? null) === 'basic') required style="margin-right:8px;">
            <div style="font-weight:800;color:#1e293b;font-size:16px;">Basic Education</div>
            <div style="font-size:13px;color:#64748b;margin-top:6px;">
                Kindergarten · Elementary · Junior High School (Grades 1-10)
            </div>
        </label>

        <label style="border:2px solid #e2e8f0;border-radius:14px;padding:24px;cursor:pointer;background:#fff;">
            <input type="radio" name="track" value="shs" @checked(($pick ?? null) === 'shs') required style="margin-right:8px;">
            <div style="font-weight:800;color:#1e293b;font-size:16px;">Senior High School</div>
            <div style="font-size:13px;color:#64748b;margin-top:6px;">
                Grades 11-12 with strand selection (STEM, ABM, HUMSS, GAS, TVL, Arts, Sports)
            </div>
        </label>

        <label style="border:2px solid #e2e8f0;border-radius:14px;padding:24px;cursor:pointer;background:#fff;">
            <input type="radio" name="track" value="higher_regular" @checked(($pick ?? null) === 'higher_regular') required style="margin-right:8px;">
            <div style="font-weight:800;color:#1e293b;font-size:16px;">College / Higher Ed</div>
            <div style="font-size:13px;color:#64748b;margin-top:6px;">
                Block enrolment for undergraduate &amp; graduate programmes — sign up for a section and the system enrols you in every class.
            </div>
        </label>

        <label style="border:2px solid #e2e8f0;border-radius:14px;padding:24px;cursor:pointer;background:#fff;">
            <input type="radio" name="track" value="graduate" @checked(($pick ?? null) === 'graduate') required style="margin-right:8px;">
            <div style="font-weight:800;color:#1e293b;font-size:16px;">Graduate School (Master’s / Doctorate)</div>
            <div style="font-size:13px;color:#64748b;margin-top:6px;">
                Post-baccalaureate programmes — pick your master’s or doctoral curriculum and enrol in your block of classes.
            </div>
        </label>
    </div>

    {{-- Modality dropdown, shown only if College/Higher Ed is selected --}}
    <div id="modality-group" style="margin-top:24px;display:none;">
        <label for="modality_id" style="font-weight:700;color:#1e293b;">Modality</label>
        <select name="modality_id" id="modality_id" style="margin-top:8px;width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;">
            <option value="">— Select Modality —</option>
            <option value="1">Face to Face</option>
            <option value="2">Online</option>
        </select>
    </div>

    <div class="form-footer" style="display:flex;justify-content:space-between;margin-top:32px;padding-top:20px;border-top:1px solid #e2e8f0;">
        <a href="{{ route('public.apply.family', $term->id) }}" class="btn btn-back">Back</a>
        <button type="submit" class="btn btn-next">Continue</button>
    </div>

    <script>
    // Show modality dropdown only for College/Higher Ed
    document.addEventListener('DOMContentLoaded', function() {
        const trackRadios = document.querySelectorAll('input[name=\'track\']');
        const modalityGroup = document.getElementById('modality-group');
        function updateModalityVisibility() {
            const selected = Array.from(trackRadios).find(r => r.checked)?.value;
            modalityGroup.style.display = (selected === 'higher_regular') ? '' : 'none';
        }
        trackRadios.forEach(r => r.addEventListener('change', updateModalityVisibility));
        updateModalityVisibility();
    });
    </script>
</form>
@endsection
