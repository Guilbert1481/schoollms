@extends('layouts.admin')

@section('title', 'System Health & Status')
@section('header-title', 'System Infrastructure')
@section('header-subtitle', 'Real-time Server & Database Monitoring')

@section('content')
<div class="space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <div class="bg-white p-6 rounded-3xl card-shadow border border-slate-100">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl">
                    <i data-lucide="database" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Database</p>
                    <p class="text-lg font-bold text-slate-900">{{ $status['database'] }}</p>
                </div>
            </div>
            <div class="h-1 w-full bg-emerald-100 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 w-full"></div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl card-shadow border border-slate-100">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl">
                    <i data-lucide="hard-drive" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Free Space</p>
                    <p class="text-lg font-bold text-slate-900">{{ $status['disk_space'] }} GB</p>
                </div>
            </div>
            <div class="h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-blue-500" style="width: 75%"></div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl card-shadow border border-slate-100">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
                    <i data-lucide="school" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Active Schools</p>
                    <p class="text-lg font-bold text-slate-900">{{ $status['active_schools'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl card-shadow border border-slate-100">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                    <i data-lucide="zap" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Cache Driver</p>
                    <p class="text-lg font-bold text-slate-900 uppercase">{{ $status['cache_driver'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl shadow-indigo-900/20">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold italic tracking-tight uppercase">System Logs & Environment</h3>
            <span class="px-4 py-1 bg-emerald-500/20 text-emerald-400 rounded-full text-xs font-bold border border-emerald-500/20">Operational</span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <div class="flex justify-between border-b border-slate-800 pb-2">
                    <span class="text-slate-400 text-sm">Laravel Version</span>
                    <span class="font-mono text-indigo-400">12.49.0</span>
                </div>
                <div class="flex justify-between border-b border-slate-800 pb-2">
                    <span class="text-slate-400 text-sm">PHP Version</span>
                    <span class="font-mono text-slate-200">{{ PHP_VERSION }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800 pb-2">
                    <span class="text-slate-400 text-sm">Server Load</span>
                    <span class="font-mono text-slate-200">{{ $status['server_load'] }}</span>
                </div>
            </div>
            
            <div class="p-6 bg-slate-800/50 rounded-2xl border border-slate-700 flex flex-col justify-center">
                <p class="text-[10px] font-black text-slate-500 uppercase mb-2">Internal Memory Usage</p>
                <p class="text-3xl font-black text-white italic tracking-widest">{{ round(memory_get_usage() / 1024 / 1024, 2) }} MB</p>
            </div>
        </div>
    </div>
</div>
@endsection