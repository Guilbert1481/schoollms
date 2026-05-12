{{-- Uses global draggable modal system: js/modal/modal.js --}}
<div id="update-display-modal" class="fixed inset-0 bg-black/50 hidden z-50">

    <div class="modal-draggable absolute bg-white w-full max-w-2xl rounded-3xl shadow-lg border border-gray-200 overflow-hidden"
         style="top:100px; left:50%; transform:translateX(-50%);">

        {{-- Header (drag handle) --}}
        <div class="modal-header flex items-center justify-between px-5 py-4 border-b bg-gray-50 cursor-move select-none">
            <h2 class="font-bold text-gray-800">Display Settings</h2>
            <button type="button"
                    onclick="closeModal('update-display-modal')"
                    class="text-gray-500 hover:text-gray-800 text-2xl font-bold leading-none"
                    aria-label="Close">&times;</button>
        </div>

        <div class="p-6">
            <form method="POST" action="{{ route('admin.quotes.update-display') }}">
                @csrf

                <div class="grid md:grid-cols-3 gap-4 items-end">

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Display</label>
                        <select name="display" class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-gray-50 text-sm">
                            <option value="success">Success</option>
                            <option value="happiness">Happiness</option>
                            <option value="love">Love</option>
                            <option value="joy">Joy</option>
                            <option value="peace">Peace</option>
                            <option value="perseverance">Perseverance</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Duration (days)</label>
                        <input type="number" name="display_duration" min="1"
                               class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-gray-50 text-sm"
                               value="1">
                    </div>

                    <button type="submit"
                            class="h-12 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold">
                        Apply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div id="update-display-modal"
     style="display:none;"
     class="fixed inset-0 bg-black/50 z-50 items-center justify-center">

    <div class="relative bg-white w-full max-w-2xl rounded-3xl shadow-lg border overflow-hidden">

        <button type="button"
                onclick="closeQuoteModal('update-display-modal')"
                class="absolute top-4 right-5 text-gray-500 text-2xl">
            &times;
        </button>

        <div class="p-5 border-b bg-gray-50">
            <h2 class="font-bold text-gray-800">Display Settings</h2>
        </div>

        <div class="p-6">
            <form method="POST" action="{{ route('admin.quotes.update-display') }}">
                @csrf

                <div class="grid md:grid-cols-3 gap-4 items-end">

                    <select name="display" class="h-12 px-4 rounded-xl border">
                        <option value="success">Success</option>
                        <option value="happiness">Happiness</option>
                    </select>

                    <input type="number" name="display_duration"
                           class="h-12 px-4 rounded-xl border"
                           value="1">

                    <button type="submit"
                            class="h-12 bg-black text-white rounded-xl">
                        Apply
                    </button>

                </div>
            </form>
        </div>
    </div>
</div>