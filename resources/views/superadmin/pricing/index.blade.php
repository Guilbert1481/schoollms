@extends('layouts.superadmin')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-0 py-10">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Pricing Configuration</h1>
            <p class="text-slate-500 text-sm">Managing rates for: <span class="font-bold text-indigo-600 uppercase">{{ $activePlan }}</span></p>
        </div>

        <div class="flex items-center gap-3">
            <label class="text-xs font-bold text-slate-400 uppercase">Switch Plan:</label>
            <select onchange="window.location.href = '{{ route('superadmin.pricing.index') }}?plan=' + this.value" 
                    class="bg-white border-2 border-slate-200 py-2 px-4 rounded-xl font-bold text-slate-700 outline-none focus:border-indigo-500 transition-all cursor-pointer">
                <option value="basic" {{ $activePlan == 'basic' ? 'selected' : '' }}>Basic</option>
                <option value="standard" {{ $activePlan == 'standard' ? 'selected' : '' }}>Standard</option>
                <option value="premium" {{ $activePlan == 'premium' ? 'selected' : '' }}>Premium</option>
                <option value="enterprise" {{ $activePlan == 'enterprise' ? 'selected' : '' }}>Enterprise</option>
            </select>
        </div>
    </div>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="mb-6 px-5 py-3 bg-emerald-100 text-emerald-700 font-semibold rounded-xl shadow-sm border border-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- FORM --}}
    <form method="POST" action="{{ route('superadmin.pricing.update') }}" class="space-y-8">
        @csrf
        <input type="hidden" name="plan_name" value="{{ $activePlan }}">

        {{-- PER USER PRICING --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
            <div class="flex items-center gap-2 mb-6">
                <div class="p-2 bg-indigo-50 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">Per User Pricing</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Student Price (per month)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold">₱</span>
                        <input type="number" name="student_price" step="0.01" value="{{ $pricing->student_price }}"
                               class="w-full pl-8 pr-4 py-3 rounded-xl border-2 border-slate-100 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-slate-700 font-semibold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Teacher Price (per month)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold">₱</span>
                        <input type="number" name="teacher_price" step="0.01" value="{{ $pricing->teacher_price }}"
                               class="w-full pl-8 pr-4 py-3 rounded-xl border-2 border-slate-100 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-slate-700 font-semibold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Staff Price (per month)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold">₱</span>
                        <input type="number" name="staff_price" step="0.01" value="{{ $pricing->staff_price }}"
                               class="w-full pl-8 pr-4 py-3 rounded-xl border-2 border-slate-100 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-slate-700 font-semibold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Parent Price (per month)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold">₱</span>
                        <input type="number" name="parent_price" step="0.01" value="{{ $pricing->parent_price }}"
                               class="w-full pl-8 pr-4 py-3 rounded-xl border-2 border-slate-100 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-slate-700 font-semibold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Alumni Price (per month)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold">₱</span>
                        <input type="number" name="alumni_price" step="0.01" value="{{ $pricing->alumni_price }}"
                               class="w-full pl-8 pr-4 py-3 rounded-xl border-2 border-slate-100 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-slate-700 font-semibold">
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- SETUP FEE --}}
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
                <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <span class="p-2 bg-amber-50 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </span>
                    Setup Fee
                </h2>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">One-Time Setup Fee</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold">₱</span>
                    <input type="number" name="setup_fee" step="0.01" value="{{ $pricing->setup_fee }}"
                           class="w-full pl-8 pr-4 py-3 rounded-xl border-2 border-slate-100 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-700 font-semibold">
                </div>
            </div>

            {{-- ADD-ON MODULE PRICING --}}
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
                <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <span class="p-2 bg-emerald-50 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                    </span>
                    Add-Ons
                </h2>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Price per User/Module</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold">₱</span>
                    <input type="number" name="addon_price" step="0.01" value="{{ $pricing->addon_price }}"
                           class="w-full pl-8 pr-4 py-3 rounded-xl border-2 border-slate-100 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-700 font-semibold">
                </div>
            </div>
        </div>

        {{-- SAVE BUTTON --}}
        <div class="flex justify-end pt-4">
            <button type="submit"
                    class="px-10 py-4 bg-indigo-600 text-white font-bold rounded-2xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all active:scale-95">
                Save Changes
            </button>
        </div>

    </form>
</div>
@endsection