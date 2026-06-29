<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
        <i data-lucide="phone-call" class="h-4 w-4 text-rose-500"></i>
        <h3 class="text-sm font-bold text-slate-800">Emergency Contact</h3>
    </div>
    @if($emergency)
        <div class="space-y-3">
            <div>
                <div class="text-[11px] font-semibold text-indigo-500">Name</div>
                <div class="text-sm font-semibold text-slate-800">{{ $emergency->name }}</div>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-indigo-500">Relationship</div>
                <div class="text-sm font-semibold text-slate-800">{{ $emergency->relationship }}</div>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-indigo-500">Contact Number</div>
                <div class="text-sm font-semibold text-slate-800">{{ $emergency->contact }}</div>
            </div>
            <div>
                <div class="text-[11px] font-semibold text-indigo-500">Email Address</div>
                <div class="text-sm font-semibold text-slate-800">{{ $emergency->email }}</div>
            </div>
        </div>
    @else
        <p class="text-sm text-slate-400">No emergency contact on record.</p>
    @endif
</div>
