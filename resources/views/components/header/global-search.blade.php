{{-- views/components/header/global-search.blade.php --}}

<div id="globalSearchModal"
     class="fixed inset-0 bg-black/40 hidden z-50 flex items-start justify-center">

    <div class="bg-white w-[750px] rounded-2xl shadow-xl mx-auto mt-24 p-5">

        {{-- HEADER --}}
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold text-slate-700">
                Global Search
            </h2>

            <button onclick="closeGlobalSearch()"
                    class="text-slate-400 hover:text-slate-600">
                ✕
            </button>
        </div>

        {{-- SEARCH BAR --}}
        <div class="flex gap-2 mb-4">
            <input type="text"
                id="globalSearchInput"
                placeholder="Search students, subjects, invoices, OR number..."
                class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">

            <button onclick="performGlobalSearch()"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Search
            </button>

            <button id="clearGlobalSearch"
                    class="px-4 py-2 bg-slate-200 rounded-lg hover:bg-slate-300">
                Clear
            </button>
        </div>

        {{-- RESULTS --}}
        <div id="globalSearchResults"
             class="max-h-[400px] overflow-y-auto border-t pt-3">

            <div class="text-sm text-slate-400">
                Start typing to search the system...
            </div>

        </div>

    </div>
</div>