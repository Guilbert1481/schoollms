{{-- Uses global draggable modal system: js/modal/modal.js --}}
<div id="edit-quote-modal" class="fixed inset-0 bg-black/50 hidden z-50">

    <div class="modal-draggable absolute bg-white w-full max-w-3xl rounded-3xl shadow-lg border border-gray-200 overflow-hidden"
         style="top:100px; left:50%; transform:translateX(-50%);">

        {{-- Header (drag handle) --}}
        <div class="modal-header flex items-center justify-between px-5 py-4 border-b bg-gray-50 cursor-move select-none">
            <h2 class="font-bold text-gray-800">Edit Quote</h2>
            <button type="button"
                    onclick="closeModal('edit-quote-modal')"
                    class="text-gray-500 hover:text-gray-800 text-2xl font-bold leading-none"
                    aria-label="Close">&times;</button>
        </div>

        <form method="POST" id="edit-quote-form" class="flex flex-col">
            @csrf
            @method('PUT')

            <input type="hidden" name="id">

            <div class="px-6 pt-6 pb-4 space-y-5">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Category</label>
                        <select name="theme"
                                class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-gray-50 text-sm">
                            <option value="success">Success</option>
                            <option value="happiness">Happiness</option>
                            <option value="love">Love</option>
                            <option value="joy">Joy</option>
                            <option value="peace">Peace</option>
                            <option value="perseverance">Perseverance</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Author</label>
                        <input type="text"
                               name="author"
                               class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-gray-50 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Content</label>
                    <textarea name="content"
                              class="w-full rounded-xl border border-gray-200 bg-gray-50 text-sm p-4"
                              rows="3"></textarea>
                </div>
            </div>

            <div class="px-6 py-5 border-t border-gray-100 bg-white flex justify-end">
                <button type="submit"
                        class="inline-flex items-center justify-center px-6 h-12 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 transition shadow-lg">
                    Update Quote
                </button>
            </div>
        </form>
    </div>
</div>
<div id="edit-quote-modal"
     style="display:none;"
     class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">

    <!-- Modal Card -->
    <div class="relative bg-white w-full max-w-3xl rounded-3xl shadow-lg border border-gray-200 overflow-hidden">

        <!-- Close Button -->
        <button type="button"
                onclick="closeQuoteModal('edit-quote-modal')"
                class="absolute top-4 right-5 text-gray-500 hover:text-gray-800 text-2xl font-bold z-10">
            &times;
        </button>

        <!-- Header -->
        <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
            <div class="p-2 bg-blue-100 rounded-xl">
                <i data-lucide="edit" class="w-5 h-5 text-blue-600"></i>
            </div>
            <h2 class="font-bold text-gray-800">Edit Quote</h2>
        </div>

        <!-- Form -->
        <form method="POST" id="edit-quote-form" class="flex flex-col">
            @csrf
            @method('PUT')

            <input type="hidden" name="id">

            <div class="px-6 pt-6 pb-4 space-y-5">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Category</label>
                        <select name="theme"
                                class="w-full h-12 px-4 rounded-2xl border border-gray-200 bg-gray-50 text-sm">
                            <option value="success">Success</option>
                            <option value="happiness">Happiness</option>
                            <option value="love">Love</option>
                            <option value="joy">Joy</option>
                            <option value="peace">Peace</option>
                            <option value="perseverance">Perseverance</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Author</label>
                        <input type="text"
                               name="author"
                               class="w-full h-12 px-4 rounded-2xl border border-gray-200 bg-gray-50 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Content</label>
                    <textarea name="content"
                              class="w-full rounded-2xl border border-gray-200 bg-gray-50 text-sm p-4"
                              rows="3"></textarea>
                </div>

            </div>

            <div class="px-6 py-5 border-t border-gray-100 bg-white flex justify-end">
                <button type="submit"
                        class="inline-flex items-center justify-center px-6 h-12 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 transition shadow-lg">
                    Update Quote
                </button>
            </div>

        </form>

    </div>
</div>