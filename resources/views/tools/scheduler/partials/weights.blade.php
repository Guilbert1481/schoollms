<div class="rounded-xl border border-slate-200 bg-white p-4">
    <h2 class="text-base font-semibold text-slate-800 mb-3">Optimization Weights</h2>
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-sm">
        <label class="block">
            <span class="text-slate-600">Gap penalty: <span x-text="weights.gap"></span></span>
            <input type="range" min="0" max="3" step="0.1" x-model.number="weights.gap" class="w-full">
        </label>
        <label class="block">
            <span class="text-slate-600">Compactness: <span x-text="weights.compact"></span></span>
            <input type="range" min="0" max="3" step="0.1" x-model.number="weights.compact" class="w-full">
        </label>
        <label class="block">
            <span class="text-slate-600">Teacher balance: <span x-text="weights.teacher"></span></span>
            <input type="range" min="0" max="3" step="0.1" x-model.number="weights.teacher" class="w-full">
        </label>
        <label class="block">
            <span class="text-slate-600">Room efficiency: <span x-text="weights.room"></span></span>
            <input type="range" min="0" max="3" step="0.1" x-model.number="weights.room" class="w-full">
        </label>
    </div>
</div>
