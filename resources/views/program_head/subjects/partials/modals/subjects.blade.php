<div x-show="addModal" 
     x-cloak
     class="fixed inset-0 flex items-center justify-center bg-black/50 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 relative" @click.away="closeModal()">
        <button class="absolute top-4 right-4 text-gray-400 hover:text-gray-600" @click="closeModal()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        
        <h3 class="text-xl font-bold text-gray-800 mb-6">Add New Subject</h3>
        
        <form action="{{ route('program_head.subjects.store') }}" method="POST">
        @csrf
        <input type="hidden" name="school_id" value="{{ auth()->user()->school_id }}">

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Subject Name</label>
                <input name="name" type="text" required placeholder="e.g. Mathematics"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Subject Code</label>
                <input name="code" type="text" required placeholder="e.g. MATH-101"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                <select name="active" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>

        <div class="mt-8 flex justify-end gap-3">
            <button type="button" @click="closeModal()" 
                class="px-4 py-2 text-gray-400 hover:bg-gray-100 rounded-lg transition-colors">Cancel</button>
            <button type="submit" 
                class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 shadow-md transition-all active:scale-95">
                Create Subject
            </button>
        </div>
    </form>
    </div>
</div>