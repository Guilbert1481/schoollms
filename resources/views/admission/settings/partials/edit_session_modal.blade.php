<div id="editSessionModal" class="fixed inset-0 bg-black/40 hidden z-50">

    <div id="editDraggableModal"
         class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 absolute max-h-[90vh] overflow-y-auto"
         style="top:80px; left:450px;">

        <div id="editModalHeader" class="flex justify-between mb-4 cursor-move">
            <h2 class="font-extrabold">Edit Session</h2>
            <button type="button" onclick="closeEditSessionModal()">✕</button>
        </div>

        @php
            $currency = \App\Helpers\CurrencyHelper::forCurrentSchool();
        @endphp

        <form id="editSessionForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <input type="hidden" name="currency" value="{{ $currency['code'] }}">

            <div class="space-y-3">
                <div>
                    <label class="text-xs font-bold">Session Title</label>
                    {{-- Read-only: the title always follows the linked term set in
                         Master Data → AY & Terms. It is NOT submitted; the server
                         derives it from the term. --}}
                    <input type="text" id="edit_title" readonly tabindex="-1"
                           class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-slate-600 cursor-not-allowed">
                    <p class="text-xs text-slate-500 mt-1">
                        Comes from the term in Master Data → AY &amp; Terms. Rename it there to change this.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold">Start Date</label>
                        <input type="date" name="start_date" id="edit_start_date"
                               class="w-full border rounded-lg px-3 py-2">
                    </div>

                    <div>
                        <label class="text-xs font-bold">End Date</label>
                        <input type="date" name="end_date" id="edit_end_date"
                               class="w-full border rounded-lg px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold">
                        Price ({{ $currency['code'] }})
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">
                            {{ $currency['symbol'] }}
                        </span>
                        <input type="number" step="0.01" min="0"
                               name="price" id="edit_price"
                               class="w-full border rounded-lg pl-8 pr-3 py-2"
                               placeholder="0.00">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold">Cover Image</label>
                    <div id="edit_cover_preview_wrap" class="mb-2 hidden">
                        <img id="edit_cover_preview" src="" alt="cover"
                             class="h-24 rounded-lg border object-cover">
                    </div>
                    <input type="file" name="cover_image" accept="image/*"
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                    <p class="text-xs text-slate-500 mt-1">
                        Leave empty to keep current image. Large photos are automatically resized.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs font-bold">Instructor Title</label>
                        <select name="instructor_title" id="edit_instructor_title"
                                class="w-full border rounded-lg px-2 py-2 text-sm">
                            <option value="">—</option>
                            <option value="Mr.">Mr.</option>
                            <option value="Mrs.">Mrs.</option>
                            <option value="Ms.">Ms.</option>
                            <option value="Dr.">Dr.</option>
                            <option value="Prof.">Prof.</option>
                            <option value="Engr.">Engr.</option>
                            <option value="Atty.">Atty.</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="text-xs font-bold">Instructor Name</label>
                        <input type="text" name="instructor_name" id="edit_instructor_name"
                               class="w-full border rounded-lg px-3 py-2"
                               placeholder="Full name">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold">Course Details</label>
                    <textarea name="course_details" id="edit_course_details" rows="4"
                              class="w-full border rounded-lg px-3 py-2"
                              placeholder="Short description that appears on the course card"></textarea>
                </div>
            </div>

            <div class="flex justify-end mt-4">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">
                    Update
                </button>
            </div>
        </form>

    </div>
</div>

<script src="{{ asset('js/modules/draggable.js') }}"></script>

<script>

document.addEventListener("DOMContentLoaded", function () {
    makeDraggable("editDraggableModal", "editModalHeader");
});
</script>