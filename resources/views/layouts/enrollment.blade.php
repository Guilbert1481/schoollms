<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Universal Enrollment System')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Base Reset */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        
        body { 
            background-color: #d1d5db; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            overflow: hidden;
        }

        /* Container holds Sidebar + Main Column */
.container {
    display: flex;
    width: 100%;
    height: 100vh;
    background-color: #2c333d; /* The dark color for sidebar & header */
    overflow: hidden;
}

/* Vertical stack for Header + White Card */
.main-column {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

/* The dark header bar */
.header-bar {
    width: 100%;
    padding: 15px 40px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    background-color: #2c333d; /* Matches Sidebar */
}

/* The White "Floating" Card */
.content-wrapper {
    flex-grow: 1;
    background-color: #f8fafc; 
    margin: 0 20px 20px 0; /* Creates the gap from edges */
    border-radius: 15px;
    overflow-y: auto;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
}



        /* Form Area */
        .main-form { 
            padding: 50px 60px; 
            overflow-y: auto; 
            flex-grow: 1;
        }

        /* Typography & Progress */
        .step-indicator { font-size: 12px; color: #64748b; font-weight: 700; margin-bottom: 10px; }
        .progress-bar { width: 100%; height: 6px; background: #e2e8f0; border-radius: 10px; margin-bottom: 40px; }
        .progress-fill { width: 12.5%; height: 100%; background: #5a57d6; border-radius: 10px; }
        
        h2 { 
            font-size: 32px; 
            font-weight: 800; 
            margin-bottom: 35px; 
            color: #1e293b; 
            letter-spacing: -0.5px;
        }

        /* Form Grid Logic */
        .form-grid { display: grid; grid-template-columns: 200px 1fr; gap: 40px; }
        
        /* Upload Boxes */
        .upload-box { 
            background: white; 
            border-radius: 15px; 
            border: 1px solid #e2e8f0; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            font-size: 12px; 
            color: #64748b; 
            cursor: pointer; 
            transition: all 0.2s; 
        }
        .upload-box:hover { border-color: #5a57d6; background: #fcfcff; }
        .photo-upload { width: 200px; height: 230px; }
        .id-upload { width: 100%; height: 48px; flex-direction: row; gap: 10px; border-style: dashed; }
        
        /* Input Fields */
        .inputs-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .input-group { display: flex; flex-direction: column; gap: 8px; }
        .input-group label { font-size: 11px; color: #475569; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        
        input, select { 
            padding: 14px; 
            border: 1px solid #e2e8f0; 
            border-radius: 10px; 
            font-size: 14px; 
            background: white; 
            width: 100%; 
            color: #1e293b;
            transition: border 0.2s;
        }
        input:focus, select:focus { outline: none; border-color: #5a57d6; box-shadow: 0 0 0 3px rgba(90, 87, 214, 0.1); }

        /* Action Buttons */
        .form-footer { 
            margin-top: 40px; 
            display: flex; 
            justify-content: space-between; 
            padding-top: 25px; 
            border-top: 1px solid #e2e8f0; 
        }
        .btn { padding: 14px 32px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; border: none; transition: 0.2s; }
        .btn-draft { background: #e2e8f0; color: #475569; }
        .btn-draft:hover { background: #cbd5e1; }
        .btn-next { background: #5a57d6; color: white; box-shadow: 0 4px 12px rgba(90, 87, 214, 0.3); }
        .btn-next:hover { background: #4845b8; transform: translateY(-1px); }
    </style>
</head>
<body>
    <div class="container">
    {{-- Dark Sidebar --}}
    @include('public.partials.sidebar')

    <div class="main-column">
        {{-- Header on the dark background --}}
        @include('public.partials.header')
        
        {{-- White "Card" starts here --}}
        <div class="content-wrapper">
            <div class="main-form">
                @yield('content')
            </div>
        </div>
    </div>
</div>
    @stack('scripts')
</body>
</html>