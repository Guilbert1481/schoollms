<form method="POST" action="{{ isset($deadline) ? route('communication.deadlines.update', $deadline) : route('communication.deadlines.store') }}">
    @csrf
    @if(isset($deadline)) @method('PUT') @endif

    {{-- Title --}}
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
        <input type="text" name="title" value="{{ old('title', $deadline->title ?? '') }}"
            placeholder="e.g., Submit Semester Grades"
            class="w-full h-12 px-4 rounded-xl border border-gray-300 bg-white shadow-sm focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 transition"
            required>
    </div>

    {{-- Description --}}
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
        <textarea name="content" rows="4" placeholder="Provide details..."
            class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-white shadow-sm focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 transition">{{ old('content', $deadline->content ?? '') }}</textarea>
    </div>


    <x-assignable-dropdown :groups="$groups" />
    

    <div class="w-full pt-8">
    
    <!-- Label -->
    <label class="block text-sm font-medium text-gray-700 mb-2">
        Due Date
    </label>

    <!-- Row -->
    <div class="flex items-end w-full">
        
        <!-- Input (Half Width) -->
        <div class="w-1/2">
            <input 
                type="datetime-local" 
                name="due_date"
                value="{{ old('due_date', isset($deadline) ? $deadline->due_date->format('Y-m-d\TH:i') : '') }}"
                class="w-full h-12 px-4 rounded-xl border border-gray-400 bg-white shadow-sm focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 hover:border-gray-500 transition-all cursor-pointer"
            >
        </div>

        <!-- Spacer -->
        <div class="flex-1"></div>

        <!-- Buttons -->
        <div class="flex gap-3">
            <a href="{{ route('communication.deadlines.index') }}"
               class="h-12 flex items-center justify-center px-6 rounded-lg border bg-gray-100 hover:bg-gray-200 transition font-semibold min-w-[96px]">
                Cancel
            </a>

            <button 
                type="submit" 
                class="h-12 flex items-center justify-center px-8 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition shadow-md min-w-[140px]">
                Save Deadline
            </button>
        </div>

    </div>
</div>
</form>