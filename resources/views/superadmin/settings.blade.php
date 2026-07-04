@extends('layouts.superadmin')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-0 py-10">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-black text-slate-800">Platform Settings</h1>
        <p class="text-slate-500 text-sm">Global configuration applied across every school.</p>
    </div>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="mb-6 px-5 py-3 bg-emerald-100 text-emerald-700 font-semibold rounded-xl shadow-sm border border-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- FORM --}}
    <form method="POST" action="{{ route('superadmin.settings.update') }}" class="space-y-8">
        @csrf

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
            <div class="flex items-center gap-2 mb-6">
                <div class="p-2 bg-indigo-50 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">Online Payments</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">System Fee (per transaction)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold">₱</span>
                        <input type="number" name="system_fee" step="0.01" min="0" value="{{ old('system_fee', $systemFee) }}"
                               class="w-full pl-8 pr-4 py-3 rounded-xl border-2 border-slate-100 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-slate-700 font-semibold">
                    </div>
                    @error('system_fee')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-slate-400">
                        Added on top of each invoice at online checkout. Leave at ₱{{ number_format($defaultSystemFee, 2) }} to use the default.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-indigo-700">
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
