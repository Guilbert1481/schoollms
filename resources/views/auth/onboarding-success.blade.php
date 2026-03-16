@extends('layouts.guest')

@section('content')
<div class="min-h-screen bg-slate-50 flex items-center justify-center p-6 font-['Plus_Jakarta_Sans']">
    <div class="max-w-xl w-full">
        <div class="bg-white p-12 rounded-[3.5rem] shadow-2xl border border-slate-100 text-center relative overflow-hidden">
            
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-500/5 rounded-full blur-3xl"></div>
            
            <div class="w-24 h-24 bg-emerald-50 text-emerald-500 rounded-[2.5rem] flex items-center justify-center mx-auto mb-8 shadow-inner">
                <i data-lucide="check-circle-2" class="w-12 h-12"></i>
            </div>
            
            <h2 class="text-3xl font-black text-slate-900 uppercase italic tracking-tight mb-4">Environment Ready</h2>
            <p class="text-slate-500 font-bold mb-8">We have successfully provisioned your digital campus for <span class="text-indigo-600 underline decoration-indigo-200 underline-offset-4">{{ auth()->user()->school->name }}</span>.</p>

            <div class="bg-slate-900 rounded-[2rem] p-8 text-left mb-10 border border-slate-800">
                <div class="flex justify-between items-center mb-4">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Unique Access URL</p>
                    <span class="px-2 py-1 bg-emerald-500/10 text-emerald-400 rounded text-[10px] font-bold uppercase border border-emerald-500/20">Active</span>
                </div>
                <div class="flex items-center gap-3">
                    <p class="text-lg font-mono text-indigo-400 font-bold break-all">
                        {{ url('/') }}/s/{{ auth()->user()->school->slug }}
                    </p>
                    <button class="p-2 text-slate-500 hover:text-white transition-colors">
                        <i data-lucide="copy" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <div class="space-y-4">
                <a href="{{ route('admin.dashboard') }}" class="block w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl font-black uppercase text-xs tracking-[0.2em] transition-all shadow-xl shadow-indigo-200">
                    Enter Dashboard
                </a>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest pt-4">Multi-tenant Isolation Protocol Active</p>
            </div>
        </div>
    </div>
</div>
<script>lucide.createIcons();</script>
@endsection