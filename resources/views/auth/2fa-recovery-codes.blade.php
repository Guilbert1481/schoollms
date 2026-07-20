<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recovery Keys | MRIS Platform</title>
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
        <div class="bg-white/80 backdrop-blur-xl p-10 rounded-[3rem] shadow-2xl border border-white/50 text-center">
            <div class="mb-8 inline-flex items-center justify-center w-20 h-20 bg-white rounded-[2rem] shadow-xl">
                <i data-lucide="key-round" class="w-10 h-10 text-emerald-600"></i>
            </div>

            <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-2">Save Your Recovery Keys</h2>
            <p class="text-xs font-bold text-slate-500 mb-8 uppercase tracking-widest leading-relaxed">
                Security Shield is active. These one-time keys are your only way in if you lose your
                authenticator — they will <span class="text-rose-500">never be shown again</span>.
            </p>

            <div class="mb-8 p-5 bg-slate-50 border border-slate-100 rounded-2xl grid grid-cols-2 gap-3">
                @foreach ($codes as $code)
                    <p class="font-mono font-bold text-sm tracking-widest text-slate-800 select-all">{{ $code }}</p>
                @endforeach
            </div>

            <a href="{{ route('dashboard') }}"
               class="block w-full py-5 bg-slate-900 hover:bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest transition-all shadow-xl">
                I saved them — continue
            </a>
        </div>
    </div>
</div>
<script>lucide.createIcons();</script>
</body>
</html>
