@extends('layouts.school') {{-- or your school layout --}}

@section('content')
<div class="max-w-6xl mx-auto pb-24 px-4 sm:px-6 lg:px-0">

    {{-- FLASH MESSAGE --}}
    @if(session('status'))
        <div 
            x-data="{ show: true }" 
            x-show="show" 
            x-init="setTimeout(() => show = false, 2500)"
            class="mb-6 px-5 py-3 bg-emerald-100 text-emerald-700 font-semibold rounded-xl shadow"
        >
            {{ session('status') }}
        </div>
    @endif

    {{-- PAGE HEADER --}}
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-black text-slate-800 tracking-tight">
            Plans & Modules
        </h1>
        <p class="text-slate-500 font-medium mt-1 text-sm sm:text-base">
            Manage your school’s subscription and enhance it with additional modules.
        </p>
    </div>

    {{-- BUNDLE SELECTION --}}
    <div class="mb-10">
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-[0.18em] mb-3">
            Choose a plan
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

            {{-- BASIC --}}
            <form method="POST" action="{{ route('school.plans.update') }}" class="h-full">
                @csrf
                <input type="hidden" name="plan" value="basic">
                <div class="flex flex-col h-full bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="h-1 bg-blue-500"></div>

                    <div class="flex-1 p-5 flex flex-col">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-bold text-slate-900">Basic</h3>
                            @if($currentPlan === 'basic')
                                <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                    Current
                                </span>
                            @endif
                        </div>

                        <p class="text-sm text-slate-500 mb-3">
                            Essential tools for small schools.
                        </p>

                        <p class="text-xs font-semibold text-slate-600 mb-4">
                            {{ $bundleSummary['basic']['count'] ?? 9 }} modules included
                        </p>

                        <ul class="text-xs text-slate-500 space-y-1 mb-4">
                            <li>• Students, Teachers, Classes</li>
                            <li>• Lessons, Assessments, Grades</li>
                            <li>• Registration & Admissions</li>
                        </ul>

                        <button 
                            type="submit"
                            class="mt-auto w-full inline-flex items-center justify-center px-3 py-2.5 text-sm font-semibold rounded-xl
                                   {{ $currentPlan === 'basic' ? 'bg-slate-200 text-slate-700 cursor-default' : 'bg-blue-500 text-white hover:bg-blue-600' }} transition">
                            {{ $currentPlan === 'basic' ? 'Selected' : 'Select Plan' }}
                        </button>
                    </div>
                </div>
            </form>

            {{-- STANDARD --}}
            <form method="POST" action="{{ route('school.plans.update') }}" class="h-full">
                @csrf
                <input type="hidden" name="plan" value="standard">
                <div class="flex flex-col h-full bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="h-1 bg-emerald-500"></div>

                    <div class="flex-1 p-5 flex flex-col">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-bold text-slate-900">Standard</h3>
                            @if($currentPlan === 'standard')
                                <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                    Current
                                </span>
                            @endif
                        </div>

                        <p class="text-sm text-slate-500 mb-3">
                            Advanced tools for growing schools.
                        </p>

                        <p class="text-xs font-semibold text-slate-600 mb-4">
                            {{ $bundleSummary['standard']['count'] ?? 14 }} modules included
                        </p>

                        <ul class="text-xs text-slate-500 space-y-1 mb-4">
                            <li>• Everything in Basic</li>
                            <li>• Announcements & Events</li>
                            <li>• File Management & Reports</li>
                        </ul>

                        <button 
                            type="submit"
                            class="mt-auto w-full inline-flex items-center justify-center px-3 py-2.5 text-sm font-semibold rounded-xl
                                   {{ $currentPlan === 'standard' ? 'bg-slate-200 text-slate-700 cursor-default' : 'bg-emerald-500 text-white hover:bg-emerald-600' }} transition">
                            {{ $currentPlan === 'standard' ? 'Selected' : 'Select Plan' }}
                        </button>
                    </div>
                </div>
            </form>

            {{-- PREMIUM --}}
            <form method="POST" action="{{ route('school.plans.update') }}" class="h-full">
                @csrf
                <input type="hidden" name="plan" value="premium">
                <div class="flex flex-col h-full bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="h-1 bg-purple-500"></div>

                    <div class="flex-1 p-5 flex flex-col">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-bold text-slate-900">Premium</h3>
                            @if($currentPlan === 'premium')
                                <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                    Current
                                </span>
                            @endif
                        </div>

                        <p class="text-sm text-slate-500 mb-3">
                            Full LMS + SIS capabilities.
                        </p>

                        <p class="text-xs font-semibold text-slate-600 mb-4">
                            {{ $bundleSummary['premium']['count'] ?? 22 }} modules included
                        </p>

                        <ul class="text-xs text-slate-500 space-y-1 mb-4">
                            <li>• Everything in Standard</li>
                            <li>• Finance & Student Services</li>
                            <li>• Operations & Security</li>
                        </ul>

                        <button 
                            type="submit"
                            class="mt-auto w-full inline-flex items-center justify-center px-3 py-2.5 text-sm font-semibold rounded-xl
                                   {{ $currentPlan === 'premium' ? 'bg-slate-200 text-slate-700 cursor-default' : 'bg-purple-500 text-white hover:bg-purple-600' }} transition">
                            {{ $currentPlan === 'premium' ? 'Selected' : 'Select Plan' }}
                        </button>
                    </div>
                </div>
            </form>

            {{-- ENTERPRISE --}}
            <form method="POST" action="{{ route('school.plans.update') }}" class="h-full">
                @csrf
                <input type="hidden" name="plan" value="enterprise">
                <div class="flex flex-col h-full bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden relative overflow-hidden">
                    <div class="h-1 bg-amber-400"></div>

                    <div class="absolute top-3 right-3">
                        <span class="text-[10px] font-bold text-slate-900 bg-amber-300 px-2 py-0.5 rounded-full">
                            Most Popular
                        </span>
                    </div>

                    <div class="flex-1 p-5 flex flex-col">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-bold text-slate-900">Enterprise</h3>
                            @if($currentPlan === 'enterprise')
                                <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                    Current
                                </span>
                            @endif
                        </div>

                        <p class="text-sm text-slate-500 mb-3">
                            Complete SIS + LMS + ERP suite.
                        </p>

                        <p class="text-xs font-semibold text-slate-600 mb-4">
                            {{ $bundleSummary['enterprise']['count'] ?? 42 }} modules included
                        </p>

                        <ul class="text-xs text-slate-500 space-y-1 mb-4">
                            <li>• Everything in Premium</li>
                            <li>• Housing, Cafeteria, Research</li>
                            <li>• Parent Portal & Proctoring</li>
                        </ul>

                        <button 
                            type="submit"
                            class="mt-auto w-full inline-flex items-center justify-center px-3 py-2.5 text-sm font-semibold rounded-xl
                                   {{ $currentPlan === 'enterprise' ? 'bg-slate-200 text-slate-700 cursor-default' : 'bg-amber-400 text-slate-900 hover:bg-amber-500' }} transition">
                            {{ $currentPlan === 'enterprise' ? 'Selected' : 'Select Plan' }}
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    {{-- ADD INDIVIDUAL MODULES --}}
    <div class="mt-10">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
            <div>
                <h2 class="text-base sm:text-lg font-semibold text-slate-800">
                    Add Individual Modules
                </h2>
                <p class="text-xs sm:text-sm text-slate-500">
                    Enhance your plan with additional modules. Dependencies will be enabled automatically.
                </p>
            </div>
        </div>

        <form id="modulesForm" method="POST" action="{{ route('school.modules.update') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($categories as $categoryName => $modules)
                    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
                        <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.18em] mb-3">
                            {{ $categoryName }}
                        </h3>

                        <div class="space-y-2">
                            @foreach($modules as $module)
                                <div class="flex items-center justify-between p-3 rounded-2xl hover:bg-slate-50 transition-colors">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-700 text-sm">
                                            {{ $module->name }}
                                        </span>

                                        @if(in_array($module->id, $bundleModules ?? []))
                                            <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-600">
                                                Included in plan
                                            </span>
                                        @else
                                            <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-50 text-slate-500">
                                                Add-on
                                            </span>
                                        @endif
                                    </div>

                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input 
                                            type="checkbox"
                                            class="module-toggle sr-only peer"
                                            name="modules[]"
                                            value="{{ $module->id }}"
                                            {{ in_array($module->id, $enabled) ? 'checked' : '' }}
                                        >
                                        <div class="w-10 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer 
                                                    peer-checked:bg-indigo-600 transition-all"></div>
                                        <div class="absolute left-1 top-1 w-3.5 h-3.5 bg-white rounded-full 
                                                    peer-checked:translate-x-5 transition-all shadow"></div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button 
                    type="submit"
                    class="px-6 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold shadow hover:bg-emerald-700 transition">
                    Save Module Changes
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
