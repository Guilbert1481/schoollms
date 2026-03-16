@extends('layouts.superadmin')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-10 space-y-12">
    
    <div class="border-b border-slate-200 pb-8">
        <h1 class="text-3xl font-black text-slate-900 tracking-tight uppercase italic">My Profile</h1>
        <p class="text-slate-500 font-bold uppercase text-[10px] tracking-[0.2em] mt-2">
            Master Security & Credential Management
        </p>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 p-10 overflow-hidden relative">
        <div class="flex items-center gap-4 mb-10">
            <div class="p-3 bg-indigo-50 rounded-2xl text-indigo-600">
                <i data-lucide="settings" class="w-6 h-6"></i>
            </div>
            <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">System Settings</h2>
        </div>

        <form action="{{ route('superadmin.profile.update') }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Master Name</label>
                    <input type="text" name="name" value="{{ auth()->user()->name }}" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>

                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">System Email</label>
                    <input type="email" name="email" value="{{ auth()->user()->email }}" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>

                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Admin Mobile No.</label>
                    <input type="text" name="phone" value="{{ auth()->user()->phone }}" placeholder="+63 9XX XXX XXXX" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>

                <div class="hidden md:block"></div>

                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Update Security Key</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>

                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Confirm New Key</label>
                    <input type="password" name="password_confirmation" placeholder="Confirm your new key" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
            </div>
            
            <div class="pt-6">
                <button type="submit" class="px-10 py-4 bg-slate-900 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-slate-800 transition-all shadow-xl shadow-slate-200">
                    Update Master Credentials
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 p-10">
        <div class="flex items-center gap-4 mb-8">
            <div class="p-3 bg-rose-50 rounded-2xl text-rose-600">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Security Shield (2FA)</h2>
        </div>
        
        <p class="text-slate-500 font-bold text-sm mb-10 leading-relaxed max-w-2xl">
            Protect the **LMS Engine** infrastructure. Activating this will require a mobile code every time you log in as Superadmin.
        </p>

        <button class="px-10 py-4 bg-rose-500 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-rose-600 transition-all shadow-xl shadow-rose-200">
            Setup Security Shield
        </button>
    </div>
</div>
@endsection