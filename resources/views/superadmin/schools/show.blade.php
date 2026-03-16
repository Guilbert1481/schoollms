@extends('layouts.superadmin')

@section('content')
<div class="max-w-5xl mx-auto">
    {{-- Breadcrumbs & Back Button --}}
    <div class="mb-10">
        <a href="{{ route('superadmin.schools.index') }}" 
        class="inline-flex items-center gap-3 px-6 py-3 rounded-2xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all group font-black text-xs uppercase tracking-widest">
            <i class="fas fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
            <span>Back to Partner Directory</span>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        {{-- Left Column: Institution Identity --}}
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 text-center">
                <div class="w-24 h-24 bg-indigo-50 text-indigo-600 rounded-3xl flex items-center justify-center text-3xl font-black mx-auto mb-4">
                    {{ substr($school->name, 0, 1) }}
                </div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">{{ $school->name }}</h1>
                <span class="inline-block mt-2 px-4 py-1 rounded-full bg-slate-100 text-slate-500 text-[10px] font-black uppercase tracking-widest">
                    {{ $school->type }}
                </span>
            </div>

            {{-- Subscription Stats --}}
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Current Plan</h3>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-black text-indigo-600 uppercase">{{ $school->plan_name ?? 'Basic' }}</span>
                    <span class="text-[10px] font-bold {{ $school->is_active ? 'text-emerald-500' : 'text-red-400' }}">
                        ● {{ $school->is_active ? 'ACTIVE' : 'INACTIVE' }}
                    </span>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-50">
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Expires On</p>
                    <p class="text-sm font-bold text-slate-700">{{ $school->plan_expires_at ? \Carbon\Carbon::parse($school->plan_expires_at)->format('M d, Y') : 'N/A' }}</p>
                </div>
            </div>
        </div>

        {{-- Right Column: Detailed Information --}}
        <div class="md:col-span-2 space-y-6">
            {{-- Contact Information Section --}}
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                <h2 class="text-xl font-black text-slate-800 mb-6">Institution Profile</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Contact Person</label>
                        <p class="text-slate-700 font-bold">{{ $school->users->first()->name ?? 'Not Assigned' }}</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Email Address</label>
                        <p class="text-indigo-600 font-bold">{{ $school->users->first()->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Mobile Number</label>
                        <p class="text-slate-700 font-bold">{{ $school->phone_number ?? 'No data' }}</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Institution ID</label>
                        <p class="text-slate-700 font-bold">#{{ $school->id }}</p>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-slate-50">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Physical Address</label>
                    <p class="text-slate-600 leading-relaxed">{{ $school->address ?? 'No address registered yet.' }}</p>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="flex gap-4">
                <a href="{{ route('superadmin.schools.edit', $school->id) }}" class="flex-1 text-center bg-slate-800 text-white py-4 rounded-2xl font-bold hover:bg-slate-900 transition shadow-lg shadow-slate-100">
                    Edit Profile
                </a>
                <form action="{{ route('superadmin.schools.toggle', $school->id) }}" method="POST" class="flex-1">
                    @csrf @method('PATCH')
                    <button class="w-full bg-white border-2 border-slate-100 text-slate-700 py-4 rounded-2xl font-bold hover:bg-slate-50 transition">
                        {{ $school->is_active ? 'Deactivate Account' : 'Activate Account' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection