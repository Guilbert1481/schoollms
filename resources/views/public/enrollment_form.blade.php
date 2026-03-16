@extends('layouts.enrollment')

@section('content')
<div class="relative">
    {{-- 1. THE INVISIBLE SHIELD: Covers everything --}}
    @guest
        <div id="form-lock-overlay" 
             style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; cursor: pointer; background: transparent;" 
             onclick="showAuthModal()">
        </div>
    @endguest

    {{-- 2. THE CONTENT: Fully visible but interaction is blocked via JS for guests --}}
    <div id="main-enrollment-content">
    
        <div class="step-indicator">STEP 1 OF 7</div>
        <div class="progress-bar"><div class="progress-fill"></div></div>

        <h3>Personal Information</h3>

        <form id="enrollmentForm" action="{{ route('public.apply', $semester->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-grid">
                <div class="upload-box photo-upload" id="photo-trigger">
                    <span style="font-size: 32px; margin-bottom: 10px;">📷</span>
                    <strong>Upload Photo</strong>
                    <input type="file" id="photoInput" name="student_photo" hidden accept="image/*">
                </div>

                <div class="inputs-container">
                    <div class="input-group">
                        <label>First Name*</label>
                        <input type="text" name="first_name" placeholder="First Name" value="{{ $student->first_name ?? old('first_name') }}" required>
                    </div>
                    <div class="input-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" placeholder="Middle Name" value="{{ $student->middle_name ?? old('middle_name') }}">
                    </div>
                    <div class="input-group">
                        <label>Last Name*</label>
                        <input type="text" name="last_name" placeholder="Last Name" value="{{ $student->last_name ?? old('last_name') }}" required>
                    </div>
                    
                    <div class="input-group">
                        <label>Preferred Name</label>
                        <input type="text" name="preferred_name" placeholder="Preferred Name" value="{{ old('preferred_name') }}">
                    </div>
                    <div class="input-group">
                        <label>Gender*</label>
                        <select name="gender" required>
                            <option value="" disabled selected>Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Sexual Orientation</label>
                        <select name="sexual_orientation">
                            <option value="straight">Straight</option>
                            <option value="gay">Gay</option>
                            <option value="lesbian">Lesbian</option>
                            <option value="bisexual">Bisexual</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Date of Birth*</label>
                        <input type="date" name="dob" value="{{ $student->dob ?? old('dob') }}" required>
                    </div>
                    <div class="input-group">
                        <label>Nationality*</label>
                        <input type="text" name="nationality" placeholder="Nationality" value="{{ old('nationality') }}" required>
                    </div>
                    <div class="input-group">
                        <label>Civil Status*</label>
                        <select name="civil_status" required>
                            <option value="" disabled selected>Select Status</option>
                            <option value="single">Single</option>
                            <option value="married">Married</option>
                            <option value="widowed">Widowed</option>
                            <option value="separated">Separated</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Govt ID Type</label>
                        <select name="id_type">
                            <option value="national_id">National ID</option>
                            <option value="passport">Passport</option>
                            <option value="drivers_license">Driver's License</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Govt ID Number</label>
                        <input type="text" name="id_number" placeholder="ID Number">
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
                <button type="button" id="btnSaveDraft" class="btn btn-draft">Save as Draft</button>
                <button type="submit" class="btn btn-next">Next Step</button>
            </div>
        </form>
    </div>
</div>

{{-- The Sign up / Log in / Cancel Modal --}}
<div id="authModal" style="display: none; position: fixed; inset: 0; z-index: 10000; align-items: center; justify-content: center; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);">
    <div style="background: white; padding: 40px; border-radius: 20px; width: 100%; max-width: 400px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 10px;">Sign up or Log in to Continue</h3>
        <p style="color: #64748b; margin-bottom: 30px; font-size: 14px;">Please create an account or log in to fill out the enrollment form.</p>
        
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <a href="{{ url('/register/1') }}" class="btn btn-next" style="text-decoration: none; display: block; background: #5a57d6; color: white;">Sign Up</a>
            <a href="{{ url('/login') }}" class="btn btn-draft" style="text-decoration: none; display: block; background: #f1f5f9; color: #1e293b;">Log In</a>
            <button onclick="closeAuthModal()" style="background: none; border: none; color: #94a3b8; font-size: 12px; font-weight: 600; cursor: pointer; margin-top: 10px; text-decoration: underline;">Cancel</button>
        </div>
    </div>
</div>

<style>
    .relative { position: relative; }
</style>
@endsection

@push('scripts')
<script>
    function showAuthModal() {
        document.getElementById('authModal').style.display = 'flex';
    }
    function closeAuthModal() {
        document.getElementById('authModal').style.display = 'none';
    }

    {{-- Block keyboard interaction for Guests --}}
    @guest
    document.addEventListener('keydown', function(e) {
        // Prevent typing and show modal
        e.preventDefault();
        showAuthModal();
    });
    @endguest

    {{-- Standard Photo/ID Preview & Draft Logic --}}
    document.getElementById('photo-trigger').addEventListener('click', () => {
        if ("{{ Auth::guest() }}" == "1") { showAuthModal(); return; }
        document.getElementById('photoInput').click();
    });

    document.getElementById('id-trigger').addEventListener('click', () => {
        if ("{{ Auth::guest() }}" == "1") { showAuthModal(); return; }
        document.getElementById('idInput').click();
    });

    document.getElementById('photoInput').onchange = evt => {
        const [file] = document.getElementById('photoInput').files;
        if (file) {
            const container = document.getElementById('photo-trigger');
            container.style.backgroundImage = `url(${URL.createObjectURL(file)})`;
            container.style.backgroundSize = 'cover';
            container.style.backgroundPosition = 'center';
            container.innerHTML = ''; 
        }
    };
</script>
@endpush