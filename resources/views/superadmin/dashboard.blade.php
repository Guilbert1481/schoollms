@extends('layouts.superadmin')

@section('content')
<div class="stats-grid grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
    
    <div class="stat-card bg-white p-6 rounded-2xl shadow-sm border border-slate-50">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                <i data-lucide="school"></i>
            </div>
            <div>
                <p class="stat-label text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Full Schools</p>
                <h2 class="stat-value text-[#1e293b] text-3xl font-extrabold m-0 tracking-tight">{{ $schoolsCount ?? 1 }}</h2>
            </div>
        </div>
    </div>

    <div class="stat-card bg-white p-6 rounded-2xl shadow-sm border border-slate-50">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <i data-lucide="users"></i>
            </div>
            <div>
                <p class="stat-label text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Freelancers</p>
                <h2 class="stat-value text-[#1e293b] text-3xl font-extrabold m-0 tracking-tight">{{ $freelanceCount ?? 0 }}</h2>
            </div>
        </div>
    </div>

    <div class="stat-card bg-white p-6 rounded-2xl shadow-sm border border-slate-50">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <i data-lucide="fingerprint"></i>
            </div>
            <div>
                <p class="stat-label text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Total Users</p>
                <h2 class="stat-value text-[#1e293b] text-3xl font-extrabold m-0 tracking-tight">{{ $usersCount ?? 2 }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-50">
    <div class="flex items-center gap-3 mb-2">
        <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
        <h3 class="text-slate-900 font-bold m-0">System Status</h3>
    </div>
    <p class="text-slate-500 text-sm font-medium">All modules across all 24 partner institutions are currently operational. No incidents reported in the last 24 hours.</p>
</div>
@endsection