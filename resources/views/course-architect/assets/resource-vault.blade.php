@extends('layouts.app')

@section('content')
<div class="w-full p-6 bg-slate-50 min-h-screen flex flex-col gap-6"
     x-data="caResourceVault()">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="folder-lock" class="w-6 h-6 text-indigo-600"></i>
                Resource Vault
            </h1>
            <p class="text-sm text-slate-500 mt-1">Central library of MP4, PDF and E-book assets.</p>
        </div>
        <button class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <i data-lucide="upload" class="w-4 h-4"></i> Upload Asset
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 min-h-[480px] flex items-center justify-center text-slate-400">
        <div class="text-center">
            <i data-lucide="folder-lock" class="w-10 h-10 mx-auto mb-3 text-slate-300"></i>
            <p>Asset library will list here.</p>
        </div>
    </div>

</div>
<script src="{{ asset('js/course_architect/resource_vault.js') }}" defer></script>
@endsection
