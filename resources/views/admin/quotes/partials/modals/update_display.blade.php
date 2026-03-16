<div id="update-display-modal"
     style="display:none;"
     class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">

    <!-- Modal Card -->
    <div class="relative bg-white w-full max-w-2xl rounded-3xl shadow-lg border border-gray-200 overflow-hidden">

        <!-- Close Button -->
        <button type="button"
                onclick="closeModal('update-display-modal')"
                class="absolute top-4 right-5 text-gray-500 hover:text-gray-800 text-2xl font-bold z-10"
                aria-label="Close modal">
            &times;
        </button>

        <!-- Header -->
        <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
            <div class="p-2 bg-amber-100 rounded-xl">
                <i data-lucide="zap" class="w-5 h-5 text-amber-600"></i>
            </div>
            <h2 class="font-bold text-gray-800">Live Dashboard Settings</h2>
        </div>

        <!-- Body -->
        <div class="p-6">
            <form action="{{ route('admin.quotes.update-display') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">

                    <div class="md:col-span-5">
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">
                            Active Display
                        </label>
                        <select name="display"
                                class="w-full h-12 px-4 rounded-2xl border border-gray-200 bg-gray-50 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            <option value="success">Success & Motivation</option>
                            <option value="happiness">Happiness & Well-being</option>
                            <option value="perseverance">Perseverance & Growth</option>
                        </select>
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">
                            Days Per Quote
                        </label>
                        <input type="number"
                               name="display_duration"
                               value="1"
                               min="1"
                               class="w-full h-12 px-4 rounded-2xl border border-gray-200 bg-gray-50 text-sm focus:ring-2 focus:ring-amber-500">
                    </div>

                    <div class="md:col-span-3">
                        <button type="submit"
                                class="w-full h-12 bg-gray-900 text-white rounded-2xl text-sm font-bold hover:bg-black transition shadow-lg shadow-gray-200">
                            Apply Changes
                        </button>
                    </div>

                </div>
            </form>
        </div>

    </div>
</div>