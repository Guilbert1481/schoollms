{{-- Uses global draggable modal system: js/modal/modal.js --}}
<div id="create-quote-modal" class="fixed inset-0 bg-black/50 hidden z-50">

    <div class="modal-draggable absolute bg-white w-full max-w-3xl rounded-3xl shadow-lg border border-gray-200 overflow-hidden"
         style="top:80px; left:50%; transform:translateX(-50%);">

        {{-- Header (drag handle) --}}
        <div class="modal-header flex items-center justify-between px-5 py-4 border-b bg-gray-50 cursor-move select-none">
            <h2 class="font-bold text-gray-800">Add to Quote Library</h2>
            <button type="button"
                    onclick="closeModal('create-quote-modal')"
                    class="text-gray-500 hover:text-gray-800 text-2xl font-bold leading-none"
                    aria-label="Close">&times;</button>
        </div>

        <form action="{{ route('admin.quotes.store') }}" method="POST">
            @csrf

            <div id="quotes-wrapper"
                 class="space-y-6 overflow-y-auto px-6 pt-6"
                 style="max-height:45vh;">

                <div class="quote-block border border-gray-200 rounded-2xl p-5 space-y-4">

                    <div class="grid md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Category</label>
                            <select name="quotes[0][theme]" class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-gray-50 text-sm">
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
                                   name="quotes[0][author]"
                                   class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-gray-50 text-sm"
                                   placeholder="e.g. Mark Twain">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Content</label>
                        <textarea name="quotes[0][content]"
                                  class="w-full rounded-xl border border-gray-200 bg-gray-50 text-sm p-4"
                                  rows="3"
                                  placeholder="Enter inspirational quote..."></textarea>
                    </div>
                </div>
            </div>

            <div class="px-6 py-5 border-t border-gray-100 flex justify-between gap-4 bg-white">
                <button type="button" id="add-quote-btn"
                        class="inline-flex items-center justify-center px-6 h-12 border-2 border-dashed border-indigo-400 text-indigo-600 rounded-2xl font-semibold hover:bg-indigo-50 transition">
                    + Add Another Quote
                </button>

                <button type="submit"
                        class="inline-flex items-center justify-center px-6 h-12 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 transition shadow-lg">
                    Save All Quotes
                </button>
            </div>
        </form>
    </div>
</div>
<div id="create-quote-modal"
     style="display:none;"
     class="fixed inset-0 bg-black/50 z-50 items-center justify-center">

    <div class="relative bg-white w-full max-w-3xl rounded-3xl shadow-lg border border-gray-200 overflow-hidden">

        <!-- Close -->
        <button type="button"
                onclick="closeQuoteModal('create-quote-modal')"
                class="absolute top-4 right-5 text-gray-500 hover:text-gray-800 text-2xl font-bold">
            &times;
        </button>

        <!-- Header -->
        <div class="p-5 border-b bg-gray-50 flex items-center gap-3">
            <h2 class="font-bold text-gray-800">Add to Quote Library</h2>
        </div>

        <form action="{{ route('admin.quotes.store') }}" method="POST">
            @csrf

            <div id="quotes-wrapper"
                 class="space-y-6 overflow-y-auto px-6 pt-6"
                 style="max-height:45vh;">

                <div class="quote-block border rounded-2xl p-5 space-y-4">

                    <div class="grid md:grid-cols-2 gap-5">
                        <select name="quotes[0][theme]" class="h-12 px-4 rounded-xl border">
                            <option value="success">Success</option>
                            <option value="happiness">Happiness</option>
                            <option value="love">Love</option>
                        </select>

                        <input type="text"
                               name="quotes[0][author]"
                               class="h-12 px-4 rounded-xl border"
                               placeholder="Author">
                    </div>

                    <textarea name="quotes[0][content]"
                              class="w-full rounded-xl border p-4"
                              rows="3"
                              placeholder="Quote..."></textarea>
                </div>
            </div>

            <div class="px-6 py-5 border-t flex justify-between">
                <button type="button" id="add-quote-btn"
                        class="px-6 h-12 border-2 border-dashed rounded-xl">
                    + Add Another
                </button>

                <button type="submit"
                        class="px-6 h-12 bg-indigo-600 text-white rounded-xl">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>