<div x-show="editStatusModal" 
     x-cloak 
     class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-black/60 p-4"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    
    <div class="relative w-full max-w-xs transform overflow-hidden rounded-2xl bg-white p-6 shadow-2xl transition-all" 
         @click.away="editStatusModal = false"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-bold text-gray-900">Change Status</h3>
            <button type="button" @click="editStatusModal = false" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="mb-5 rounded-lg bg-indigo-50/50 border border-indigo-100 p-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-500">Subject</p>
            <p class="truncate text-sm font-bold text-indigo-900" x-text="currentSubjectCode"></p>
        </div>
        
        <form :action="`/staff/program-head/subjects/${statusSubjectId}`" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-500">New Availability</label>
                    <select name="active" 
                            x-model="statusValue" 
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all">
                        <option :value="1">Visible (Active)</option>
                        <option :value="0">Hidden (Inactive)</option>
                    </select>
                </div>

                <div class="rounded-lg bg-amber-50 p-3">
                    <p class="text-[11px] leading-relaxed text-amber-700">
                        <span class="font-bold">Note:</span> Setting to inactive hides this from the curriculum.
                    </p>
                </div>
            </div>

            <div class="mt-6 space-y-2">
                <button type="submit" 
                        class="w-full rounded-xl bg-indigo-600 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-200 hover:bg-indigo-700 active:scale-[0.97] transition-all">
                    Update Status
                </button>
                <button type="button" 
                        @click="editStatusModal = false" 
                        class="w-full py-2 text-sm font-semibold text-gray-400 hover:text-gray-600 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>