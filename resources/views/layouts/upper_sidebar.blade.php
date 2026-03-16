<div class="px-6 py-6 border-b border-slate-800">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center overflow-hidden border border-white/5">
            @if(!empty($school_logo))
                <img src="{{ asset('storage/' . $school_logo) }}" alt="Logo" class="w-full h-full object-cover">
            @else
                <i data-lucide="shield-check" class="w-5 h-5 text-indigo-400"></i>
            @endif
        </div>
        <div class="overflow-hidden">
            <h1 class="text-sm font-semibold text-white leading-tight truncate">
                {{ $school_name }}
            </h1>
            <p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mt-0.5">
                Academic Governance
            </p>
        </div>
    </div>
</div>