<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | {{ $school_name ?? 'Sophentis' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Playfair+Display:wght@700&display=swap');
        .font-serif-premium { font-family: 'Playfair Display', serif; }
        .font-sans-premium { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 antialiased font-sans-premium">
<div class="min-h-screen flex items-center justify-center p-0 md:p-6">
    <div class="flex w-full max-w-5xl min-h-[640px] overflow-hidden bg-white shadow-2xl md:rounded-[2.5rem]">

        {{-- LEFT BRAND PANEL --}}
        <div class="relative hidden md:flex md:w-1/2 flex-col justify-end p-12 bg-gradient-to-br from-[#1a2b4b] to-indigo-700 text-white">
            <i data-lucide="key-round" class="w-12 h-12 mb-6 opacity-90"></i>
            <h1 class="text-3xl font-serif-premium mb-3 leading-tight">New Security Key</h1>
            <p class="text-xs font-bold text-indigo-100 uppercase tracking-widest opacity-80">
                {{ $school_name ?? 'Sophentis Platform' }}
            </p>
        </div>

        {{-- RIGHT FORM PANEL --}}
        <div class="w-full md:w-1/2 p-8 md:p-16 flex flex-col justify-center bg-white">
            <div class="mb-8">
                <h2 class="text-4xl font-serif-premium text-slate-900 mb-2">Set New Password</h2>
                <p class="text-xs font-bold text-slate-400 leading-relaxed">
                    Choose a strong password (at least 8 characters) to secure your account.
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 bg-rose-50 border border-rose-100 rounded-2xl flex items-center gap-3 text-rose-600">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <p class="text-[10px] font-black uppercase tracking-wider">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Identity (Email)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-5 flex items-center text-slate-300"><i data-lucide="mail" class="w-5 h-5"></i></span>
                        <input type="email" name="email" value="{{ old('email', $email) }}" required readonly
                            class="w-full pl-14 pr-5 py-4 bg-slate-100 border border-slate-100 rounded-2xl outline-none font-bold text-slate-500">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">New Security Key</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-5 flex items-center text-slate-300"><i data-lucide="lock" class="w-5 h-5"></i></span>
                        <input type="password" name="password" required autofocus placeholder="••••••••"
                            class="w-full pl-14 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Confirm Security Key</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-5 flex items-center text-slate-300"><i data-lucide="lock" class="w-5 h-5"></i></span>
                        <input type="password" name="password_confirmation" required placeholder="••••••••"
                            class="w-full pl-14 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700">
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-5 bg-[#1a2b4b] hover:bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs tracking-[0.2em] transition-all shadow-xl hover:shadow-indigo-500/40 group mt-2">
                    <span class="flex items-center justify-center gap-2">
                        Reset Password
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </span>
                </button>
            </form>

            <div class="mt-8 text-center">
                <a href="{{ route('login') }}"
                    class="text-[10px] font-black text-slate-500 uppercase tracking-widest hover:text-indigo-600 transition-colors inline-flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</div>
<script>lucide.createIcons();</script>
</body>
</html>
