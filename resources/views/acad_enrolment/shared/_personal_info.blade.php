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
                <label>Religion</label>
                @php
                    $religion = old('religion', $student->religion ?? '');
                    $religionSubs = old('religion_subcategories', $student->religion_subcategories ?? []);
                    if (! is_array($religionSubs)) { $religionSubs = []; }
                    $christianSubs = [
                        'Catholic', 'Protestant', 'Baptist', 'Lutheran',
                        'Pentecostal', 'Evangelical', 'Presbyterian',
                        'Iglesia Ni Cristo',
                    ];
                @endphp
                <select name="religion" id="religionSelect">
                    <option value="">— Select —</option>
                    @foreach (['Christian','Islam','Buddhism','Hinduism','Atheist','Others'] as $rel)
                        <option value="{{ $rel }}" @selected($religion === $rel)>{{ $rel }}</option>
                    @endforeach
                </select>

                {{-- Floating list anchored to the Religion select; visible
                     only when "Christian" is selected. Click an item to pick
                     it (single-select). Click outside or press Esc to dismiss. --}}
                <div id="christianPopoverHost" class="relative">
                    <div id="christianSummary"
                         class="mt-1 text-xs text-slate-500 {{ $religion === 'Christian' && count($religionSubs) ? '' : 'hidden' }}">
                        Selected: <span id="christianSummaryText">{{ implode(', ', $religionSubs) }}</span>
                        <button type="button" id="christianEditBtn"
                                class="ml-2 text-indigo-600 hover:underline">Change</button>
                    </div>

                    <div id="christianPopover"
                         class="hidden absolute z-50 mt-2 w-64 rounded-lg border border-slate-200 bg-white shadow-lg overflow-hidden">
                        <div class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-slate-600 border-b border-slate-100">
                            Christian Denomination
                        </div>
                        <ul class="max-h-64 overflow-y-auto py-1 text-sm" id="christianList">
                            @foreach ($christianSubs as $sub)
                                <li>
                                    <button type="button"
                                            data-value="{{ $sub }}"
                                            class="christian-sub-item w-full text-left px-3 py-2 hover:bg-indigo-50 hover:text-indigo-700 {{ in_array($sub, $religionSubs, true) ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-700' }}">
                                        {{ $sub }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Hidden field that actually submits the selection.
                         Kept as religion_subcategories[] so the existing
                         server-side array validation keeps working. --}}
                    <input type="hidden" name="religion_subcategories[]"
                           id="christianSubInput"
                           value="{{ $religionSubs[0] ?? '' }}">
                </div>
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
    // Religion dropdown — floating single-select list for Christian
    // denomination. Click an item to pick it; click outside / Esc to close.
    const religionSel  = document.getElementById('religionSelect');
    const popover      = document.getElementById('christianPopover');
    const summary      = document.getElementById('christianSummary');
    const summaryText  = document.getElementById('christianSummaryText');
    const editBtn      = document.getElementById('christianEditBtn');
    const hiddenInput  = document.getElementById('christianSubInput');
    const listItems    = () => popover ? popover.querySelectorAll('.christian-sub-item') : [];

    if (religionSel && popover) {
        const openPopover  = () => popover.classList.remove('hidden');
        const closePopover = () => popover.classList.add('hidden');

        const highlight = (value) => {
            listItems().forEach(btn => {
                const isSel = btn.dataset.value === value;
                btn.classList.toggle('bg-indigo-50', isSel);
                btn.classList.toggle('text-indigo-700', isSel);
                btn.classList.toggle('font-semibold', isSel);
                btn.classList.toggle('text-slate-700', !isSel);
            });
        };

        const refreshSummary = () => {
            const val = hiddenInput.value;
            if (!val) {
                summary.classList.add('hidden');
                summaryText.textContent = '';
            } else {
                summary.classList.remove('hidden');
                summaryText.textContent = val;
            }
            highlight(val);
        };

        const syncReligion = (autoOpen) => {
            const isChristian = religionSel.value === 'Christian';
            if (!isChristian) {
                closePopover();
                hiddenInput.value = '';
                summary.classList.add('hidden');
                summaryText.textContent = '';
                return;
            }
            refreshSummary();
            if (autoOpen) openPopover();
        };

        religionSel.addEventListener('change', () => syncReligion(true));
        editBtn && editBtn.addEventListener('click', openPopover);

        listItems().forEach(btn => {
            btn.addEventListener('click', () => {
                hiddenInput.value = btn.dataset.value;
                refreshSummary();
                closePopover();
            });
        });

        // Dismiss on outside click.
        document.addEventListener('click', (e) => {
            if (popover.classList.contains('hidden')) return;
            if (popover.contains(e.target)) return;
            if (e.target === religionSel) return;
            if (editBtn && editBtn.contains(e.target)) return;
            closePopover();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closePopover();
        });

        syncReligion(false);
    }

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
