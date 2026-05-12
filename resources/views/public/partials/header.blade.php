<style>
    .header-bar { 
        width: 100%;
        padding: 15px 40px; 
        display: flex; 
        justify-content: space-between;
        align-items: center;
        background: transparent;
        gap: 16px;
    }

    .school-badge {
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 0.3px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .school-badge .dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: #5a57d6;
        box-shadow: 0 0 0 3px rgba(90,87,214,0.25);
    }
    .school-badge small {
        display: block;
        font-size: 11px;
        font-weight: 500;
        color: rgba(255,255,255,0.6);
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    /* The Rectangle Box for the User Name */
    .user-profile-box { 
        background: #ffffff;
        color: #000000;
        padding: 8px 20px; 
        border-radius: 20px;
        font-size: 13px; 
        font-weight: 600;
        display: flex; 
        align-items: center; 
        gap: 10px; 
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .user-avatar-circle { 
        width: 24px; 
        height: 24px; 
        background: #5a57d6;
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 800;
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

@php
    $authUser     = auth()->user();
    $school       = $authUser?->school;
    $schoolName   = $school?->school_name ?? config('app.name', 'SchoolLMS');
    $displayName  = trim((string) ($authUser?->full_name ?: ''))
                     ?: ($authUser?->student?->full_name ?? null)
                     ?: ($authUser?->email ?? 'Student');
    $initials = collect(explode(' ', $displayName))
        ->filter()
        ->map(fn ($p) => mb_substr($p, 0, 1))
        ->take(2)
        ->implode('');
    $initials = strtoupper($initials ?: 'S');
@endphp

<div class="header-bar">
    {{-- LEFT: School --}}
    <div class="school-badge">
        <span class="dot"></span>
        <div>
            <small>School</small>
            {{ $schoolName }}
        </div>
    </div>

    {{-- RIGHT: User --}}
    <div style="display: flex; align-items: center; gap: 15px;">
        @auth
            <div class="user-profile-box" title="{{ $authUser->email }}">
                <div class="user-avatar-circle">{{ $initials }}</div>
                {{ $displayName }}
            </div>
        @else
            <a href="{{ url('/login') }}" class="auth-button btn-white">Login</a>
            <a href="{{ url('/register/1') }}" class="auth-button btn-indigo">Sign Up</a>
        @endauth
    </div>
</div>