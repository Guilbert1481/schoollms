

@php
    $superadmin = \App\Models\User::find(1);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        Login | {{ isset($school) && $school ? $school->school_name : 'Sophentis' }}
    </title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="{{ asset('css/admin/dashboard.css') }}">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Playfair+Display:wght@700&display=swap');
        .font-serif-premium { font-family: 'Playfair Display', serif; }
        .font-sans-premium { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>

<body class="bg-slate-100 antialiased font-sans-premium">

<div class="min-h-screen flex items-center justify-center p-0 md:p-6">
    <div class="flex w-full max-w-5xl min-h-[700px] overflow-hidden bg-white shadow-2xl md:rounded-[2.5rem]">

        {{-- LEFT SIDE (IMAGE) --}}
<div class="relative hidden md:block md:w-1/2">

    @if(isset($school) && $school && $school->school_image)
        <img 
            src="{{ asset('storage/' . $school->school_image) }}" 
            class="absolute inset-0 h-full w-full object-cover"
        />
    @elseif($superadmin && $superadmin->profile_photo)
        <img 
            src="{{ asset('storage/' . $superadmin->profile_photo) }}" 
            class="absolute inset-0 h-full w-full object-cover"
        />
    @endif
</div>


        {{-- RIGHT SIDE (LOGIN FORM) --}}
        <div class="w-full md:w-1/2 p-8 md:p-16 flex flex-col justify-center bg-white">

            <div class="mb-10">
                <h2 class="text-4xl font-serif-premium text-slate-900 mb-2">
                    Welcome Back
                </h2>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest">
                    {{ isset($school) && $school ? $school->school_name . ' Portal' : 'Sophentis Platform' }}
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 bg-rose-50 border border-rose-100 rounded-2xl flex items-center gap-3 text-rose-600">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <p class="text-[10px] font-black uppercase tracking-wider">
                        {{ $errors->first() }}
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ request()->route('slug') ? route('school.login', request()->route('slug')) : route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">
                        Identity (Email)
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-5 flex items-center text-slate-300">
                            <i data-lucide="mail" class="w-5 h-5"></i>
                        </span>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            placeholder="admin@example.com"
                            class="w-full pl-14 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2 ml-1">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            Security Key
                        </label>
                        <a href="#" class="text-[10px] font-black text-indigo-500 uppercase hover:text-indigo-600 transition-colors">
                            Recover?
                        </a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-5 flex items-center text-slate-300">
                            <i data-lucide="key-round" class="w-5 h-5"></i>
                        </span>
                        <input type="password" name="password" required placeholder="••••••••"
                            class="w-full pl-14 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700">
                    </div>
                </div>

                <div class="flex items-center justify-between ml-1">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="remember" name="remember"
                            {{ old('remember') ? 'checked' : '' }}
                            class="w-4 h-4 rounded border-slate-200 text-indigo-600 focus:ring-indigo-500">
                        <label for="remember" class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            Remember Device
                        </label>
                    </div>

                    @if(!isset($school))
                        <a href="{{ route('register.school.public') }}"
                            class="text-[10px] font-black text-slate-900 uppercase border-b-2 border-slate-900 hover:text-indigo-600 hover:border-indigo-600 transition-all">
                            Sign Up
                        </a>
                    @endif
                </div>

                <button type="submit"
                    class="w-full py-5 bg-[#1a2b4b] hover:bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs tracking-[0.2em] transition-all shadow-xl hover:shadow-indigo-500/40 group mt-4">
                    <span class="flex items-center justify-center gap-2">
                        Enter Portal
                        <i data-lucide="chevron-right"
                            class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                    </span>
                </button>
            </form>

            <div class="mt-auto pt-10 text-center opacity-40">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em]">
                    {{ isset($school) && $school ? 'Protected by ' . $school->school_name : 'Powered by Sophentis' }}
                </p>
            </div>

        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>

</body>
</html>
