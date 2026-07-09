<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied | MRIS Security</title>
    @vite(['resources/css/app.css'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .login-gradient {
            background: radial-gradient(circle at top right, #6366f1 0%, transparent 40%),
                        radial-gradient(circle at bottom left, #4f46e5 0%, transparent 40%);
        }
    </style>
</head>
<body class="bg-slate-50 antialiased font-['Plus_Jakarta_Sans']">

<div class="min-h-screen flex items-center justify-center p-6 login-gradient">
    <div class="w-full max-w-md">
        <div class="bg-white/80 backdrop-blur-xl p-12 rounded-[3rem] shadow-2xl border border-white/50 text-center">
            
            <div class="inline-flex items-center justify-center w-24 h-24 bg-rose-50 rounded-[2.5rem] mb-8 shadow-inner">
                <i data-lucide="shield-alert" class="w-12 h-12 text-rose-500"></i>
            </div>

            <h2 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase mb-4">Access Denied</h2>
            
            <p class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-10 leading-relaxed">
                Identity verification failed or insufficient clearance detected for this sector.
            </p>

            <div class="space-y-4">
                <a href="{{ url()->previous() }}" class="block w-full py-5 bg-slate-900 hover:bg-slate-800 text-white rounded-2xl font-black uppercase text-xs tracking-[0.2em] transition-all">
                    Return to Safety
                </a>
                
                <a href="{{ route('login') }}" class="block w-full py-4 text-xs font-black text-indigo-600 uppercase hover:text-indigo-500 transition-colors">
                    Switch Identity
                </a>
            </div>

            <div class="mt-12 pt-8 border-t border-slate-200/50">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest opacity-50">MRIS SECURITY CORE v1.0</p>
            </div>
        </div>
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>