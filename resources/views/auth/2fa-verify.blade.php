<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Identity | MRIS Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                <i data-lucide="shield-check" class="w-10 h-10 text-indigo-600"></i>
            </div>

            <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-2">Identity Challenge</h2>
            <p class="text-xs font-bold text-slate-500 mb-8 uppercase tracking-widest leading-relaxed">
                Security Shield is Active. Enter the 6-digit code from your authenticator app.
            </p>

            @if ($errors->any())
                <div class="mb-6 p-4 bg-rose-50 border border-rose-100 rounded-2xl text-rose-600 text-[10px] font-black uppercase tracking-widest">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('2fa.verify.post') }}" class="space-y-6">
                @csrf
                <input type="text" name="one_time_password" required autofocus placeholder="000 000"
                    class="w-full text-center py-5 bg-white border border-slate-100 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all font-mono font-bold text-2xl tracking-[0.4em] shadow-sm">

                <button type="submit" class="w-full py-5 bg-slate-900 hover:bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest transition-all shadow-xl">
                    Verify & Access Dashboard
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-100">
                <a href="{{ route('2fa.recovery') }}" class="text-[10px] font-black text-indigo-500 uppercase hover:text-indigo-600 transition-colors">
                    Lost device? Use Recovery Key
                </a>
            </div>
        </div>
    </div>
</div>
<script>lucide.createIcons();</script>
</body>
</html>