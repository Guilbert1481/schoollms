@extends('layouts.enrollment')

@section('content')
<style>
    /* Premium Grid Layout for Contact Details */
    .form-container {
        width: 100%;
        max-width: 100%; /* Spans full width of the white content area */
    }

    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 24px;
    }

    .subsection-title {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 30px 0 15px 0;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 8px;
    }

    .form-grid {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .row {
        display: grid;
        grid-template-columns: 1fr 1fr; /* Two balanced columns matching Step 1 */
        gap: 24px;
    }

    .input-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .input-group label {
        font-size: 12px;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
    }

    .input-group input {
        padding: 12px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        width: 100%; /* Ensures input fills the grid cell */
        transition: all 0.2s;
    }

    .input-group input:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .form-footer {
        display: flex;
        justify-content: space-between;
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: 0.2s;
    }

    .btn-next {
        background: #4f46e5;
        color: white;
        border: none;
    }

    .btn-back {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
</style>

<div class="form-container">
    <div class="step-indicator" style="font-size: 12px; font-weight: 600; color: #6366f1; margin-bottom: 8px;">STEP 2 OF 7</div>
    <div class="progress-bar" style="height: 4px; background: #e2e8f0; border-radius: 2px; margin-bottom: 30px;">
        <div class="progress-fill" style="width: 28%; height: 100%; background: #6366f1; border-radius: 2px;"></div>
    </div>

    <h3 class="section-title">Contact Details</h3>

    <form action="{{ route('public.apply.step2.store', $term->id) }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="row">
                <div class="input-group">
                    <label>MOBILE NUMBER*</label>
                    <input type="text" name="mobile_number" placeholder="0917XXXXXXX" value="{{ $student->mobile_number ?? '' }}" required>
                </div>
                <div class="input-group">
                    <label>LANDLINE (OPTIONAL)</label>
                    <input type="text" name="landline_number" value="{{ $student->landline_number ?? '' }}">
                </div>
            </div>

            <h4 class="subsection-title">RESIDENTIAL ADDRESS</h4>

            <div class="row">
                <div class="input-group">
                    <label>UNIT / HOUSE NUMBER</label>
                    <input type="text" name="unit_number" value="{{ $student->unit_number ?? '' }}">
                </div>
                <div class="input-group">
                    <label>STREET / BUILDING*</label>
                    <input type="text" name="street" value="{{ $student->street ?? '' }}" required>
                </div>
            </div>

            <div class="row">
                <div class="input-group">
                    <label>BARANGAY*</label>
                    <input type="text" name="barangay" value="{{ $student->barangay ?? '' }}" required>
                </div>
                <div class="input-group">
                    <label>PROVINCE*</label>
                    <input type="text" name="province" value="{{ $student->province ?? '' }}" required>
                </div>
            </div>

            <div class="row">
                <div class="input-group">
                    <label>CITY / MUNICIPALITY*</label>
                    <input type="text" name="city_municipality" value="{{ $student->city_municipality ?? '' }}" required>
                </div>
                <div class="input-group">
                    <label>ZIP CODE*</label>
                    <input type="text" name="zip_code" value="{{ $student->zip_code ?? '' }}" required>
                </div>
            </div>
        </div>

        <div class="form-footer">
            <a href="{{ route('public.apply', $semester->id) }}" class="btn btn-back">Back</a>
            <button type="submit" class="btn btn-next">Next Step</button>
        </div>
    </form>
</div>
@endsection