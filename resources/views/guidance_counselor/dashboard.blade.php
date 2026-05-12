@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

    <div class="rounded-2xl bg-gradient-to-br from-teal-600 via-emerald-700 to-green-800 p-6 md:p-8 text-white shadow-lg">
        <div class="flex items-center gap-3">
            <i data-lucide="shield-check" class="w-8 h-8"></i>
            <div>
                <h1 class="text-2xl md:text-3xl font-bold">Guidance Counselor Dashboard</h1>
                <p class="text-sm md:text-base text-teal-100 mt-1">
                    Monitor flagged communications and intervene when students need support.
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('guidance_counselor.flagged.index') }}"
           class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition">
            <div class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-rose-50 ring-1 ring-rose-200 mb-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-rose-600"></i>
            </div>
            <h2 class="text-base font-semibold text-slate-800 group-hover:text-teal-700">Flagged Interventions</h2>
            <p class="mt-1 text-sm text-slate-600">Review every flagged chat, deadline, or announcement the system has captured.</p>
        </a>
    </div>
</div>

<script>
    if (window.lucide?.createIcons) lucide.createIcons();
</script>
@endsection
