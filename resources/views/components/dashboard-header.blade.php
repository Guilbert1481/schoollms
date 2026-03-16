

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">

</head>

<div class="dashboard-header-wrapper-sticky"> 


  
    <div class="dashboard-header">

        <!-- LEFT -->
        <div class="dashboard-header-left">
            
           <h1>@yield('page-title', 'Teacher Dashboard')</h1>
           <p>
                @yield(
                    'page-subtitle',
                    Auth::check()
                        ? 'Good morning, ' . Auth::user()->name . '. Here’s how your class is doing today.'
                        : 'Welcome! Here’s how your class is doing today.'
                )
            </p>

        </div>

        <!-- RIGHT -->
        <div class="dashboard-header-right">
            <button class="icon-btn">
                <i data-lucide="bell"></i>
                <span class="notification-dot"></span>
            </button>

            <div class="user-profile">
                <img src="https://i.pravatar.cc/40?img=12">
                <div class="user-info">
                    <strong>Ms. Johnson</strong>
                    <span>Teacher / Instructor</span>
                </div>
                <i data-lucide="chevron-down"></i>
            </div>
        </div>

    </div>
</div>