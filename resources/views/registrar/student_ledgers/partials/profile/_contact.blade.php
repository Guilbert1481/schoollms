<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
        <i data-lucide="phone" class="h-4 w-4 text-indigo-600"></i>
        <h3 class="text-sm font-bold text-slate-800">Contact Information</h3>
    </div>
    <div class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
        <div>
            <div class="text-[11px] font-semibold text-indigo-500">Home Address</div>
            <div class="text-sm font-semibold text-slate-800">{{ $profile['home_address'] }}</div>
        </div>
        <div class="space-y-3">
            <div>
                <div class="text-[11px] font-semibold text-indigo-500">Phone Number</div>
                <div class="text-sm font-semibold text-slate-800">{{ $profile['phone'] }}</div>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-indigo-500">Alternate Number</div>
                <div class="text-sm font-semibold text-slate-800">{{ $profile['alternate'] }}</div>
            </div>
        </div>
    </div>
</div>
