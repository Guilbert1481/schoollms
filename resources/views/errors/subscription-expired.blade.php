@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-slate-50 px-4">
    <div class="max-w-2xl w-full">
        <div class="text-center mb-10">
            <div class="mb-6 inline-flex p-4 bg-red-100 rounded-2xl text-red-600 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            
            <h1 class="text-4xl font-black text-slate-800 mb-3">Subscription Expired</h1>
            <p class="text-slate-600 text-lg">
                Your 1-month free trial on the <span class="font-bold text-indigo-600 uppercase">Basic</span> plan has ended. 
                Please select a plan below to restore access to your dashboard.
            </p>
        </div>

        {{-- Mini Pricing Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            @php
                $plans = [
                    ['name' => 'Standard', 'price' => '₱5,000', 'color' => 'slate'],
                    ['name' => 'Premium', 'price' => '₱10,000', 'color' => 'indigo'],
                    ['name' => 'Enterprise', 'price' => 'Custom', 'color' => 'slate'],
                ];
            @endphp

            @foreach($plans as $plan)
                <div class="bg-white p-6 rounded-3xl border-2 {{ $plan['color'] == 'indigo' ? 'border-indigo-500 shadow-xl shadow-indigo-100' : 'border-slate-100' }} text-center">
                    <h3 class="font-bold text-slate-800 uppercase text-xs tracking-widest mb-2">{{ $plan['name'] }}</h3>
                    <div class="text-2xl font-black text-slate-900 mb-4">{{ $plan['price'] }}</div>
                    <button class="w-full py-2 px-4 rounded-xl text-sm font-bold {{ $plan['color'] == 'indigo' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700' }} hover:opacity-90 transition">
                        Select
                    </button>
                </div>
            @endforeach
        </div>

        <div class="text-center">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm font-bold text-slate-400 hover:text-slate-600 transition underline underline-offset-4">
                    Logout and return later
                </button>
            </form>
        </div>
    </div>
</div>
@endsection