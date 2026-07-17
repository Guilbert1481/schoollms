@extends('layouts.superadmin')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-0 py-10"
     x-data="{ tab: '{{ old('providers') ? 'ai' : 'payments' }}' }">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-black text-slate-800">Platform Settings</h1>
        <p class="text-slate-500 text-sm">Global configuration applied across every school.</p>
    </div>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="mb-6 px-5 py-3 bg-emerald-100 text-emerald-700 font-semibold rounded-xl shadow-sm border border-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- TABS --}}
    <div class="mb-6 flex gap-2 border-b border-slate-200">
        <button type="button" @click="tab='payments'"
                :class="tab==='payments' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="px-4 py-2 -mb-px border-b-2 text-sm font-bold transition-colors">
            Payments
        </button>
        <button type="button" @click="tab='ai'"
                :class="tab==='ai' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="px-4 py-2 -mb-px border-b-2 text-sm font-bold transition-colors">
            AI Providers
        </button>
    </div>

    {{-- ============================ PAYMENTS TAB ============================ --}}
    <div x-show="tab==='payments'">
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

    {{-- ============================ AI TAB ============================ --}}
    <div x-show="tab==='ai'" style="display:none;">
        <form method="POST" action="{{ route('superadmin.settings.ai.update') }}" class="space-y-6">
            @csrf

            <p class="text-sm text-slate-500">
                Configure the AI providers used by AI-assisted features. Pick one <span class="font-semibold text-slate-700">Default</span> —
                that's the provider features will call. Keys are stored encrypted; leave a key blank to keep the saved one.
            </p>

            @foreach($aiProviders as $p)
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-slate-800">{{ $p->label }}</h3>
                            @if($p->is_default)
                                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">Default</span>
                            @endif
                            @if($p->hasKey())
                                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Key set</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-4 shrink-0">
                            <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 cursor-pointer">
                                <input type="radio" name="default_provider" value="{{ $p->provider }}" @checked($p->is_default)
                                       class="text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                Default
                            </label>
                            <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 cursor-pointer">
                                <input type="checkbox" name="providers[{{ $p->provider }}][enabled]" value="1" @checked($p->is_enabled)
                                       class="rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                Enabled
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">API Key</label>
                            <input type="password" name="providers[{{ $p->provider }}][api_key]" autocomplete="new-password"
                                   placeholder="{{ $p->hasKey() ? '•••••••••• (leave blank to keep)' : 'Paste API key' }}"
                                   class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-100 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-slate-700 text-sm font-mono">
                            @if($p->provider === 'ollama')
                                <p class="mt-1 text-[11px] text-slate-400">Ollama runs locally and usually needs no key — set the Base URL instead.</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Base URL</label>
                            <input type="text" name="providers[{{ $p->provider }}][base_url]" value="{{ old('providers.'.$p->provider.'.base_url', $p->base_url) }}"
                                   placeholder="https://…"
                                   class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-100 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-slate-700 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Default Model</label>
                            <input type="text" name="providers[{{ $p->provider }}][model]" value="{{ old('providers.'.$p->provider.'.model', $p->model) }}"
                                   placeholder="e.g. gpt-4o"
                                   class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-100 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-slate-700 text-sm">
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-indigo-700">
                    Save AI Settings
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
