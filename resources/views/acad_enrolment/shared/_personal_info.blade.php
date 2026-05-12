{{--
    Shared partial: Personal Info form body.
    Required vars:
        $action        — POST URL the form submits to
        $term          — Term model (used for the draft URL)
    Optional vars:
        $student       — Student model or null
        $stepLabel     — e.g. "STEP 1 OF 8" (default omitted)
        $progressPct   — int 0..100 (default 12)
        $backUrl       — string|null
--}}
@php
    $student     = $student     ?? (auth()->user()->student ?? null);
    $stepLabel   = $stepLabel   ?? null;
    $progressPct = $progressPct ?? 12;
    $backUrl     = $backUrl     ?? null;

    // Fall back to the profiles table when the student row hasn't been
    // populated yet (so first/middle/last name carry over from registration).
    $profile     = auth()->user()?->profile;
    $authEmail   = auth()->user()?->email;
    $prefillFirst  = $student->first_name  ?? $profile?->first_name  ?? '';
    $prefillMiddle = $student->middle_name ?? $profile?->middle_name ?? '';
    $prefillLast   = $student->last_name   ?? $profile?->last_name   ?? '';
@endphp

@if ($stepLabel)
    <div class="step-indicator">{{ $stepLabel }}</div>
    <div class="progress-bar"><div class="progress-fill" style="width: {{ $progressPct }}%"></div></div>
@endif

<h3>Personal Information</h3>

<form id="enrollmentForm" action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="form-grid">
        <div class="upload-box photo-upload" id="photo-trigger">
            @if (!empty($student?->photo_path))
                <img src="{{ asset('storage/'.$student->photo_path) }}" alt="Photo" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
            @else
                <span style="font-size: 32px; margin-bottom: 10px;">📷</span>
                <strong>Upload Photo</strong>
            @endif
            <input type="file" id="photoInput" name="student_photo" hidden accept="image/*">
        </div>

        <div class="inputs-container">
            <div class="input-group">
                <label>First Name*</label>
                <input type="text" name="first_name" placeholder="First Name"
                       value="{{ old('first_name', $prefillFirst) }}" required>
            </div>
            <div class="input-group">
                <label>Middle Name</label>
                <input type="text" name="middle_name" placeholder="Middle Name"
                       value="{{ old('middle_name', $prefillMiddle) }}">
            </div>
            <div class="input-group">
                <label>Last Name*</label>
                <input type="text" name="last_name" placeholder="Last Name"
                       value="{{ old('last_name', $prefillLast) }}" required>
            </div>

            <div class="input-group">
                <label>Preferred Name</label>
                <input type="text" name="preferred_name" placeholder="Preferred Name"
                       value="{{ old('preferred_name', $student->preferred_name ?? '') }}">
            </div>
            <div class="input-group">
                <label>Gender*</label>
                @php $g = old('gender', $student->gender ?? ''); @endphp
                <select name="gender" required>
                    <option value="" disabled @selected($g === '')>Select Gender</option>
                    <option value="male"   @selected($g === 'male')>Male</option>
                    <option value="female" @selected($g === 'female')>Female</option>
                </select>
            </div>
            <div class="input-group">
                <label>Sexual Orientation</label>
                @php $so = old('sexual_orientation', $student->sexual_orientation ?? ''); @endphp
                <select name="sexual_orientation">
                    <option value="">— Select —</option>
                    <option value="straight" @selected($so === 'straight')>Straight</option>
                    <option value="gay"      @selected($so === 'gay')>Gay</option>
                    <option value="lesbian"  @selected($so === 'lesbian')>Lesbian</option>
                    <option value="bisexual" @selected($so === 'bisexual')>Bisexual</option>
                </select>
            </div>

            <div class="input-group">
                <label>Date of Birth*</label>
                @php
                    $dob = $student->date_of_birth ?? null;
                    if ($dob instanceof \DateTimeInterface) { $dob = $dob->format('Y-m-d'); }
                    elseif (is_string($dob) && $dob !== '') { $dob = substr($dob, 0, 10); }
                @endphp
                <input type="date" name="dob"
                       value="{{ old('dob', $dob ?? '') }}" required>
            </div>
            <div class="input-group">
                <label>Nationality*</label>
                <input type="text" name="nationality" placeholder="Nationality"
                       value="{{ old('nationality', $student->nationality ?? '') }}" required>
            </div>
            <div class="input-group">
                <label>Civil Status*</label>
                @php $cs = old('civil_status', $student->civil_status ?? ''); @endphp
                <select name="civil_status" required>
                    <option value="" disabled @selected($cs === '')>Select Status</option>
                    <option value="single"    @selected($cs === 'single')>Single</option>
                    <option value="married"   @selected($cs === 'married')>Married</option>
                    <option value="widowed"   @selected($cs === 'widowed')>Widowed</option>
                    <option value="separated" @selected($cs === 'separated')>Separated</option>
                </select>
            </div>

            <div class="input-group">
                <label>Govt ID Type</label>
                @php $idt = old('government_id_type', $student->government_id_type ?? ''); @endphp
                <select name="government_id_type">
                    <option value="">— Select —</option>
                    <option value="national_id"      @selected($idt === 'national_id')>National ID</option>
                    <option value="passport"         @selected($idt === 'passport')>Passport</option>
                    <option value="drivers_license"  @selected($idt === 'drivers_license')>Driver's License</option>
                </select>
            </div>
            <div class="input-group">
                <label>Govt ID Number</label>
                <input type="text" name="government_id_number" placeholder="ID Number"
                       value="{{ old('government_id_number', $student->government_id_number ?? '') }}">
            </div>
            <div class="input-group">
                <label>Upload ID</label>
                <div class="upload-box id-upload" id="id-trigger">
                    <span style="font-size: 18px;">📎</span>
                    <strong>Select File</strong>
                    <input type="file" id="idInput" name="id_file" hidden>
                </div>
            </div>
        </div>
    </div>

    <div class="form-footer">
        @if ($backUrl)
            <a href="{{ $backUrl }}" class="btn btn-back">Back</a>
        @else
            <button type="button" id="btnSaveDraft" class="btn btn-draft"
                    data-draft-url="{{ route('public.apply.draft', $term->id) }}">
                Save as Draft
            </button>
        @endif
        <button type="submit" class="btn btn-next">Next Step</button>
    </div>
</form>

@once
@push('scripts')
<script>
(function () {
    const photoTrigger = document.getElementById('photo-trigger');
    const idTrigger    = document.getElementById('id-trigger');
    const photoInput   = document.getElementById('photoInput');
    const idInput      = document.getElementById('idInput');

    if (photoTrigger && photoInput) {
        photoTrigger.addEventListener('click', () => {
            if (window.__APPLY_GUEST__) { window.showAuthModal && window.showAuthModal(); return; }
            photoInput.click();
        });
        photoInput.addEventListener('change', () => {
            const [file] = photoInput.files;
            if (!file) return;
            // Preview without removing the <input type="file"> from the DOM,
            // otherwise the form would submit with no file attached.
            const url = URL.createObjectURL(file);
            // Clear visual children but keep the file input so it submits.
            Array.from(photoTrigger.children).forEach(el => {
                if (el !== photoInput) el.remove();
            });
            photoTrigger.style.backgroundImage    = `url(${url})`;
            photoTrigger.style.backgroundSize     = 'cover';
            photoTrigger.style.backgroundPosition = 'center';
        });
    }

    if (idTrigger && idInput) {
        idTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            if (window.__APPLY_GUEST__) { window.showAuthModal && window.showAuthModal(); return; }
            idInput.click();
        });
        idInput.addEventListener('change', () => {
            const [file] = idInput.files;
            if (!file) return;
            const label = idTrigger.querySelector('strong');
            if (label) label.textContent = file.name.length > 22 ? file.name.slice(0,20)+'…' : file.name;
        });
    }
})();
</script>
@endpush
@endonce
