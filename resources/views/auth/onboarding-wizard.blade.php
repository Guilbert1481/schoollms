<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Step | Onboarding</title>
    @vite(['resources/css/app.css'])
    <script src="{{ asset('js/lucide.min.js') }}"></script>
</head>
<body class="bg-slate-50 antialiased font-['Plus_Jakarta_Sans']">

<div class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full">
        <div class="bg-white p-10 rounded-[3rem] shadow-2xl border border-slate-100 text-center">
            <div class="w-20 h-20 bg-indigo-50 text-indigo-600 rounded-3xl flex items-center justify-center mx-auto mb-6">
                <i data-lucide="sparkles" class="w-10 h-10"></i>
            </div>
            
            <h2 class="text-2xl font-black text-slate-900 uppercase italic tracking-tight">One Last Detail</h2>
            <p class="text-sm font-bold text-slate-400 mt-2 mb-8 uppercase tracking-widest">What should we call your professional space?</p>

            <form action="{{ route('onboarding.setup') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <input type="text" name="entity_name" placeholder="e.g. Memory Ridge Academy" required
                        class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all font-bold text-center text-slate-700">
                </div>

                <button type="submit" class="w-full py-5 bg-slate-900 hover:bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs tracking-[0.2em] transition-all shadow-xl">
                    Activate My Dashboard
                </button>
            </form>
            
            <p class="mt-6 text-[10px] font-black text-slate-400 uppercase tracking-widest opacity-50">
                Multi-tenant Isolation Protocol Active
            </p>
        </div>
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>