<style>
    .header-bar { 
        width: 100%;
        padding: 15px 40px; 
        display: flex; 
        justify-content: flex-end; /* Keeps the name box on the right side */
        align-items: center;
        background: transparent; /* Allows the dark container color to show through */
    }

    /* The Rectangle Box for the User Name */
    .user-profile-box { 
        background: #ffffff; /* White box as requested */
        color: #000000;      /* Black name text */
        padding: 8px 20px; 
        border-radius: 20px; /* Rectangle with rounded corners */
        font-size: 13px; 
        font-weight: 600;
        display: flex; 
        align-items: center; 
        gap: 10px; 
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .user-avatar-circle { 
        width: 20px; 
        height: 20px; 
        background: #cbd5e1; 
        border-radius: 50%; 
    }

    /* Auth Action Buttons (Login/Sign Up) */
    .auth-button {
        padding: 8px 20px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: 0.2s;
    }
    .btn-white { background: #ffffff; color: #1e293b; }
    .btn-indigo { background: #5a57d6; color: #ffffff; }
</style>

<div class="header-bar">
    <div style="display: flex; align-items: center; gap: 15px;">
        @auth
            {{-- Rectangle with border radius for the authenticated user --}}
            <div class="user-profile-box">
                <div class="user-avatar-circle"></div>
                {{ auth()->user()->name }}
            </div>
        @else
            <a href="{{ url('/login') }}" class="auth-button btn-white">
                Login
            </a>

            <a href="{{ url('/register/1') }}" class="auth-button btn-indigo">
                Sign Up
            </a>
        @endauth
    </div>
</div>