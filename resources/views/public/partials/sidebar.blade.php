<style>
    .sidebar { 
        width: 280px; 
        min-width: 280px; 
        padding: 40px 20px; 
        display: flex; 
        flex-direction: column; 
        gap: 12px;
        background-color: #2c333d; 
        height: 100vh;
        position: sticky;
        top: 0;
    }
    
    .logo-text {
        color: white; 
        font-size: 30px; 
        font-weight: 700; 
        margin-bottom: 35px; 
        margin-top: 35px; 
        margin-left: 3px;
        line-height: 1.2;
    }

    .nav-item { 
        padding: 16px 20px; 
        border-radius: 12px; 
        font-size: 12px; 
        font-weight: 700; 
        cursor: pointer; 
        background: #ffffff; 
        color: #1e293b; 
        border: none; 
        transition: all 0.2s; 
        text-transform: uppercase; 
        letter-spacing: 0.5px;
        text-align: left;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        text-decoration: none;
        display: block;
    }

    /* Active State matching your design */
    .nav-item.active { 
        background: #5a57d6 !important; 
        color: #ffffff !important; 
        box-shadow: 0 10px 15px -3px rgba(90, 87, 214, 0.4);
    }

    .nav-item:hover:not(.active) { 
        transform: translateX(5px); 
        background: #f1f5f9; 
    }
</style>

<div class="sidebar">
    <div class="logo-text">Enrolment Form</div>

    {{-- Step 1: Personal Info --}}
    {{-- Highlighted if the URL is just /apply/{id} OR /apply/{id}/step-1 --}}
    <a href="{{ route('public.apply', $semester->id) }}"
       class="nav-item {{ (request()->routeIs('public.apply') || request()->routeIs('public.apply.step1')) ? 'active' : '' }}">
        1. Personal Info
    </a>

    {{-- Step 2: Contact Details --}}
    <a href="{{ route('public.apply.step2', $semester->id) }}"
       class="nav-item {{ request()->routeIs('public.apply.step2') ? 'active' : '' }}">
        2. Contact Details
    </a>

    {{-- Step 3: Family & Emergency --}}
    <a href="#"
       class="nav-item {{ request()->routeIs('public.apply.step3') ? 'active' : '' }}">
        3. Family & Emergency
    </a>

    {{-- Remaining steps --}}
    <a href="#" class="nav-item {{ request()->routeIs('public.apply.step4') ? 'active' : '' }}">
        4. Academic Background
    </a>

    <a href="#" class="nav-item {{ request()->routeIs('public.apply.step6') ? 'active' : '' }}">
        6. Program Selection
    </a>

    <a href="#" class="nav-item {{ request()->routeIs('public.apply.step7') ? 'active' : '' }}">
        7. Legal & Consent
    </a>

    <a href="#" class="nav-item {{ request()->routeIs('public.apply.step8') ? 'active' : '' }}">
        8. Review & Submit
    </a>
</div>