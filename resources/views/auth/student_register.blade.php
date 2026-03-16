


<div class="auth-wrapper">
    {{-- School name captured from database --}}
    <h1 class="school-title">{{ $term->school->name ?? 'Memory Ridge International School' }}</h1>

    <div class="auth-card">
        <h2>Student Registration</h2>

        <form method="POST" action="{{ route('student.register.store', $semester->id) }}">
            @csrf

            <div class="row">
                <div class="input-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="First Name" required>
                </div>
                <div class="input-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Last Name" required>
                </div>
            </div>

            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Email" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <div class="input-group">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" placeholder="Confirm Password" required>
            </div>

            <button type="submit" class="btn-signup">Sign Up</button>
        </form>
    </div>
</div>

<style>
    /* Premium SaaS Box Design */
    .auth-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background-color: #2c333d; /* Branding Dark */
        padding: 20px;
    }

    .school-title {
        color: #ffffff;
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 30px;
        letter-spacing: -0.5px;
        text-align: center;
    }

    .auth-card {
        background: #ffffff;
        width: 100%;
        max-width: 480px;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .auth-card h2 {
        font-size: 24px;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 32px;
        text-align: center;
    }

    .row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .input-group {
        margin-bottom: 20px;
    }

    .input-group label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }

    input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.2s;
    }

    input:focus {
        outline: none;
        border-color: #5a57d6; /* Branding Indigo */
        box-shadow: 0 0 0 4px rgba(90, 87, 214, 0.1);
    }

    .btn-signup {
        width: 100%;
        padding: 14px;
        background-color: #5a57d6;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.2s;
        margin-top: 10px;
    }

    .btn-signup:hover {
        background-color: #4845b8;
    }
</style>
