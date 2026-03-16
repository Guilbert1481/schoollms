<div x-show="deleteModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4">
    <div class="relative w-full max-w-xs transform overflow-hidden rounded-2xl bg-white p-6 shadow-2xl transition-all" @click.away="deleteModal = false">
        
        <div class="mb-4 flex items-center justify-between text-rose-600">
            <h3 class="text-base font-bold">Confirm Deletion</h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 5 4 4"/><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
        </div>

        <div class="mb-5 rounded-lg bg-rose-50 border border-rose-100 p-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-rose-500">Warning</p>
            <p class="text-xs text-rose-900 leading-relaxed">You are about to delete <span class="font-bold" x-text="currentSubjectCode"></span>. This action is irreversible.</p>
        </div>
        
        <form :action="`/staff/program-head/subjects/${currentSubjectId}`" method="POST">
            @csrf
            @method('DELETE')
            
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-500 italic">Enter your password to confirm</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" required placeholder="••••••••"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none transition-all">
                    </div>
                </div>
            </div>

            <div class="mt-6 space-y-2">
                <button type="submit" class="w-full rounded-xl bg-rose-600 py-2.5 text-sm font-bold text-white shadow-lg shadow-rose-200 hover:bg-rose-700 active:scale-[0.97] transition-all">
                    Delete Permanently
                </button>
                <button type="button" @click="deleteModal = false" class="w-full py-2 text-sm font-semibold text-gray-400 hover:text-gray-600 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>