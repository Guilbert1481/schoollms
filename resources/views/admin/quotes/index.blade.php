
@extends('layouts.app')

@section('content')

<div class="min-h-screen bg-gray-50/30 py-6 md:py-10 px-4 md:px-6">
    <div class="w-full flex flex-col gap-6">
        <div class="w-full flex flex-col space-y-6">
            
            <div class="flex flex-col md:flex-row items-center justify-between w-full gap-4 mb-0">
                <!-- Left: Search bar, half-width on desktop -->
                <div class="md:w-1/4">
                    <form method="GET" action="{{ route('admin.quotes.index') }}" class="m-0">
                        <input 
                            type="text"
                            id="quote-search"
                            placeholder="Search category, author, content..."
                            class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-gray-50 text-gray-700"
                            autocomplete="off">
                    </form>
                </div>

                <!-- Right: Button group, only as wide as content -->
                <div class="flex items-center gap-3 md:ml-auto">
                    <button type="button"
                            onclick="openModal('create-quote-modal')"
                            class="h-12 px-6 rounded-2xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 hover:shadow-md transition-all text-sm whitespace-nowrap">
                        + Add Quote
                    </button>
                    <button type="button"
                            onclick="openModal('update-display-modal')"
                            class="h-12 px-6 rounded-2xl bg-amber-500 text-white font-bold hover:bg-amber-600 hover:shadow-md transition-all text-sm whitespace-nowrap">
                        Update Display
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl md:rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="text-left text-gray-700 bg-gray-50 font-bold border-b">
                                <th class="px-4 py-3 text-sm md:text-base">Category</th>
                                <th class="px-4 py-3 text-sm md:text-base">Author</th>
                                <th class="px-4 py-3 text-sm md:text-base">Content</th>
                                <th class="px-4 py-3 text-sm md:text-base text-right md:text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($quotes as $quote)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-4 text-sm whitespace-nowrap">{{ ucfirst($quote->theme) }}</td>
                                <td class="px-4 py-4 text-sm font-medium">— {{ $quote->author }}</td>
                                <td class="px-4 py-4 text-sm text-gray-600 min-w-[200px] max-w-md">
                                    <span class="line-clamp-2 md:line-clamp-none">"{{ $quote->content }}"</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-row items-center justify-end md:justify-start gap-3">
                                        <button type="button" 
                                            onclick="openEditModal({{ $quote->id }}, '{{ e($quote->theme) }}', '{{ e($quote->author) }}', `{{ e($quote->content) }}`)"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </button>

                                        <form action="{{ route('admin.quotes.destroy', $quote) }}" method="POST" class="flex items-center m-0" onsubmit="return confirm('Delete this quote?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition-colors">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-gray-500 py-10">
                                    <div class="flex flex-col items-center">
                                        <p>No quotes found.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-4 md:px-5 py-3 flex justify-center md:justify-end border-t">
                    {{ $quotes->withQueryString()->links() }}
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

{{-- Render Modals Outside Layout Flow --}}
@include('admin.quotes.partials.modals.create')
@include('admin.quotes.partials.modals.update_display')
@include('admin.quotes.partials.modals.edit')

<script>
(function () {

    /* ===============================
       MODAL CONTROL
    =============================== */

    function openModal(id) {
        const createModal = document.getElementById('create-quote-modal');
        const updateModal = document.getElementById('update-display-modal');

        if (createModal) createModal.style.display = 'none';
        if (updateModal) updateModal.style.display = 'none';

        const modal = document.getElementById(id);
        if (modal) modal.style.display = 'flex';
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.style.display = 'none';
    }

    window.openModal = openModal;
    window.closeModal = closeModal;


    /* ===============================
       ADD ANOTHER QUOTE FUNCTION
    =============================== */

    document.addEventListener('DOMContentLoaded', function () {

        let quoteIndex = document.querySelectorAll('#quotes-wrapper .quote-block').length;

        const addBtn = document.getElementById('add-quote-btn');
        const wrapper = document.getElementById('quotes-wrapper');

        if (!addBtn || !wrapper) return;

        addBtn.addEventListener('click', function () {

            const firstBlock = wrapper.querySelector('.quote-block');
            if (!firstBlock) return;

            const newBlock = firstBlock.cloneNode(true);

            newBlock.querySelectorAll('select, input, textarea').forEach(function(field) {

                const name = field.getAttribute('name');

                if (name) {
                    const newName = name.replace(/\[\d+\]/, '[' + quoteIndex + ']');
                    field.setAttribute('name', newName);
                }

                if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                } else {
                    field.value = '';
                }
            });

            wrapper.appendChild(newBlock);
            quoteIndex++;
        });

    });

    window.openEditModal = function(id, theme, author, content) {

        const modal = document.getElementById('edit-quote-modal');
        if (!modal) {
            console.error('Edit modal not found');
            return;
        }

        modal.querySelector('[name="id"]').value = id;
        modal.querySelector('[name="theme"]').value = theme;
        modal.querySelector('[name="author"]').value = author;
        modal.querySelector('[name="content"]').value = content;

        modal.style.display = 'flex';
    }

})();
</script>


<script>
document.addEventListener("DOMContentLoaded", function () {

    const searchInput = document.getElementById("quote-search");
    if (!searchInput) return;

    let timer;

    searchInput.addEventListener("keyup", function () {

        clearTimeout(timer);

        timer = setTimeout(() => {

            fetch(`/admin/quotes?search=${encodeURIComponent(this.value)}`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
            .then(response => response.text())
            .then(html => {

                const parser = new DOMParser();
                const doc = parser.parseFromString(html, "text/html");

                const newTbody = doc.querySelector("tbody");
                const currentTbody = document.querySelector("tbody");

                if (newTbody && currentTbody) {
                    currentTbody.innerHTML = newTbody.innerHTML;

                    if (typeof lucide !== "undefined") {
                        lucide.createIcons();
                    }
                }

            });

        }, 300); // debounce

    });

});
</script>