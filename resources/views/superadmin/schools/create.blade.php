@extends('layouts.superadmin')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Register New Partner Entity</h5>
                    <p class="text-muted small mb-0">Add a new school or independent freelance teacher to the system.</p>
                </div>
                
                <div class="card-body p-4">
                    {{-- Updated action to match your SuperSchoolRegistrationController route --}}
                    <form action="{{ route('superadmin.schools.store') }}" method="POST">
                        @csrf
                        
                        {{-- Validation Error Alert --}}
                        @if ($errors->has('error'))
                            <div class="alert alert-danger">
                                {{ $errors->first('error') }}
                            </div>
                        @endif

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Institution or Teacher Name</label>
                                <input type="text" name="school_name" class="form-control @error('school_name') is-invalid @enderror" value="{{ old('school_name') }}" placeholder="e.g. Memory Ridge Int'l School" required>
                                @error('school_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Account Type</label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="school" {{ old('type') == 'school' ? 'selected' : '' }}>International/Local School</option>
                                    <option value="freelance" {{ old('type') == 'freelance' ? 'selected' : '' }}>Freelance Teacher</option>
                                </select>
                                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Determines which modules are enabled by default.</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="text-primary mb-3">Primary Admin Account Credentials</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="admin_name" class="form-control @error('admin_name') is-invalid @enderror" value="{{ old('admin_name') }}" required>
                            @error('admin_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Admin Email Address</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Access Password</label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 shadow-sm">
                                <i class="fas fa-check-circle me-1"></i> Register & Initialize Modules
                            </button>
                            <a href="{{ route('superadmin.schools.index') }}" class="btn btn-light border mt-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection