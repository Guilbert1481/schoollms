@php
    $superadmin = \App\Models\User::find(1);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialize Environment</title>
    @vite(['resources/css/app.css'])
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap');
        body { margin: 0; padding: 0; overflow: hidden; } /* Prevents scrolling */
        .font-serif-premium { font-family: 'Playfair Display', serif; }
        .font-sans-premium { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 antialiased font-sans-premium h-screen">

<div class="flex h-screen w-full overflow-hidden">
    {{-- LEFT SIDE --}}  
    <div class="hidden md:flex flex-col relative w-1/2 h-full">
        <div class="absolute inset-0 h-full w-full bg-gray-400">
            @if($superadmin && $superadmin->profile_photo)
                <img src="{{ asset('storage/' . $superadmin->profile_photo) }}" class="h-full w-full object-cover opacity-80" alt="Superadmin">
            @endif
            <div class="absolute inset-0 h-full w-full bg-black/30"></div>
        </div>
        <div class="relative flex flex-col justify-end h-full p-12 z-10">
            <div class="mb-4 inline-flex items-center justify-center w-12 h-12 bg-white/20 backdrop-blur-md rounded-xl border border-white/30">
                <i data-lucide="graduation-cap" class="w-6 h-6 text-white"></i>
            </div>
            <h1 class="text-3xl font-serif-premium text-white leading-tight">“Build your academic ecosystem.”</h1>
            <p class="mt-2 text-white/70 font-bold uppercase tracking-widest text-[9px]">Initialize Your Professional Environment</p>
        </div>
    </div>

    {{-- RIGHT SIDE --}}
    <div class="w-full md:w-1/2 h-full bg-white flex flex-col items-center justify-center p-6 lg:p-10">
        <div class="w-full max-w-2xl">
            
            {{-- Header: Logo and Text in One Horizontal Line --}}
            <div class="flex items-center justify-center gap-4 mb-6">
                <div class="flex-shrink-0 inline-flex items-center justify-center w-12 h-12 bg-indigo-600 rounded-2xl shadow-lg text-white">
                    <i data-lucide="graduation-cap" class="w-6 h-6"></i>
                </div>
                <div class="text-left">
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight italic uppercase leading-none">Join the Future</h2>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Select your professional path</p>
                </div>
            </div>

            <div x-data="{ type: 'school' }" class="bg-white py-6 px-8 shadow-2xl rounded-[2rem] border border-slate-100">
                <form action="{{ route('register.school.post') }}" method="POST" class="space-y-3">
                    @csrf

                    {{-- Path Selector --}}
                    <div class="bg-slate-100 p-1 rounded-xl flex gap-1 mb-2">
                        <input type="radio" name="type" id="type_school" value="school" class="hidden peer/school" checked x-model="type">
                        <label for="type_school" class="flex-1 text-center py-2 rounded-lg text-[9px] font-black uppercase tracking-widest cursor-pointer transition-all peer-checked/school:bg-white peer-checked/school:text-indigo-600 peer-checked/school:shadow-sm text-slate-500">Institutional</label>
                        <input type="radio" name="type" id="type_freelancer" value="freelancer" class="hidden peer/free" x-model="type">
                        <label for="type_freelancer" class="flex-1 text-center py-2 rounded-lg text-[9px] font-black uppercase tracking-widest cursor-pointer transition-all peer-checked/free:bg-white peer-checked/free:text-indigo-600 peer-checked/free:shadow-sm text-slate-500">Freelancer</label>
                    </div>

                    {{-- Name Fields: Shorter Inputs --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">First Name</label>
                            <input type="text" name="first_name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 outline-none font-bold text-sm text-slate-700">
                        </div>
                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">Middle Name</label>
                            <input type="text" name="middle_name" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 outline-none font-bold text-sm text-slate-700">
                        </div>
                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">Last Name</label>
                            <input type="text" name="last_name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 outline-none font-bold text-sm text-slate-700">
                        </div>
                    </div>

                    {{-- Account Holder --}}
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">Account Holder Name</label>
                        <input type="text" name="entity_name" placeholder="School Name or Your Name" required class="w-full px-4 py-2 bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 outline-none font-bold text-sm text-slate-700">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div x-show="type === 'school'">
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">School Code</label>
                            <input type="text" name="code" placeholder="MRIS" oninput="this.value = this.value.toUpperCase()" class="w-full px-4 py-2 bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 outline-none font-bold text-sm text-slate-700">
                        </div>
                        <div :class="type === 'school' ? '' : 'col-span-2'">
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">Email Address</label>
                            <input type="email" name="email" required class="w-full px-4 py-2 bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 outline-none font-bold text-sm text-slate-700">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">Security Key</label>
                            <input type="password" name="password" required class="w-full px-4 py-2 bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">Confirm Key</label>
                            <input type="password" name="password_confirmation" required class="w-full px-4 py-2 bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 outline-none text-sm">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-4 mt-2 bg-slate-900 hover:bg-indigo-600 text-white rounded-xl font-black uppercase text-[10px] tracking-widest transition-all shadow-lg">
                        Initialize Environment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>