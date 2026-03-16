<div id="create-quote-modal"
     style="display:none;"
     class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">

    <!-- Modal Card -->
    <div class="relative bg-white w-full max-w-3xl rounded-3xl shadow-lg border border-gray-200 overflow-hidden">

        <!-- Close Button -->
        <button type="button"
                onclick="closeModal('create-quote-modal')"
                class="absolute top-4 right-5 text-gray-500 hover:text-gray-800 text-2xl font-bold z-10"
                aria-label="Close modal">
            &times;
        </button>

        <!-- Header -->
        <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
            <div class="p-2 bg-indigo-100 rounded-xl">
                <i data-lucide="plus" class="w-5 h-5 text-indigo-600"></i>
            </div>
            <h2 class="font-bold text-gray-800">Add to Quote Library</h2>
        </div>

        <!-- Form STARTS -->
        <form action="{{ route('admin.quotes.store') }}" method="POST" class="flex flex-col">
            @csrf

            <!-- SCROLLABLE AREA -->
            <div id="quotes-wrapper"
                 class="space-y-6 overflow-y-auto px-6 pt-6"
                 style="max-height:45vh; min-height:80px;">

                <div class="quote-block border border-gray-200 rounded-2xl p-5 space-y-4">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Category</label>
                            <select name="quotes[0][theme]"
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
                                   name="quotes[0][author]"
                                   class="w-full h-12 px-4 rounded-2xl border border-gray-200 bg-gray-50 text-sm"
                                   placeholder="e.g. Mark Twain">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Content</label>
                        <textarea name="quotes[0][content]"
                                  class="w-full rounded-2xl border border-gray-200 bg-gray-50 text-sm p-4"
                                  rows="3"
                                  placeholder="Enter inspirational quote..."></textarea>
                    </div>

                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="px-6 py-5 border-t border-gray-100 flex justify-between gap-4 bg-white">

                <button type="button"
                        id="add-quote-btn"
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