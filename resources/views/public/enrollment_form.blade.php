@extends('layouts.enrollment')

@section('content')
<div class="relative">
    {{-- Invisible shield: blocks interaction for guests --}}
    @guest
        <div id="form-lock-overlay"
             style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; cursor: pointer; background: transparent;"
             onclick="showAuthModal()"></div>
    @endguest

    <div id="main-enrollment-content">
        @include('acad_enrolment.shared._personal_info', [
            'action'      => route('public.apply.store', $term->id),
            'term'        => $term,
            'student'     => auth()->user()->student ?? null,
            'stepLabel'   => 'STEP 1 OF 9',
            'progressPct' => 11,
        ])
    </div>
</div>

{{-- Sign-up / Log-in modal for guests --}}
<div id="authModal" style="display: none; position: fixed; inset: 0; z-index: 10000; align-items: center; justify-content: center; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);">
    <div style="background: white; padding: 40px; border-radius: 20px; width: 100%; max-width: 400px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 10px;">Sign up or Log in to Continue</h3>
        <p style="color: #64748b; margin-bottom: 30px; font-size: 14px;">Please create an account or log in to fill out the enrollment form.</p>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <a href="{{ url('/register/1') }}" class="btn btn-next" style="text-decoration: none; display: block; background: #5a57d6; color: white;">Sign Up</a>
            <a href="{{ url('/login') }}"      class="btn btn-draft" style="text-decoration: none; display: block; background: #f1f5f9; color: #1e293b;">Log In</a>
            <button onclick="closeAuthModal()" style="background: none; border: none; color: #94a3b8; font-size: 12px; font-weight: 600; cursor: pointer; margin-top: 10px; text-decoration: underline;">Cancel</button>
        </div>
    </div>
</div>

<style>.relative { position: relative; }</style>
@endsection

@push('scripts')
<script>
    window.__APPLY_GUEST__ = @json(auth()->guest());

    window.showAuthModal  = function () { document.getElementById('authModal').style.display = 'flex'; };
    window.closeAuthModal = function () { document.getElementById('authModal').style.display = 'none'; };

    @guest
    document.addEventListener('keydown', function (e) {
        e.preventDefault();
        showAuthModal();
    });
    @endguest

    {{-- Save-as-draft (auth users only) --}}
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('btnSaveDraft');
        if (!btn) return;
        btn.addEventListener('click', function () {
            if (window.__APPLY_GUEST__) { showAuthModal(); return; }
            const form     = document.getElementById('enrollmentForm');
            const formData = new FormData(form);
            fetch(btn.dataset.draftUrl, {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body:    formData,
            }).then(r => r.json()).then(j => {
                btn.textContent = j.status === 'saved' ? 'Draft Saved ✓' : 'Save as Draft';
                setTimeout(() => btn.textContent = 'Save as Draft', 2000);
            }).catch(() => { btn.textContent = 'Save Failed'; });
        });
    });
</script>
@endpush
