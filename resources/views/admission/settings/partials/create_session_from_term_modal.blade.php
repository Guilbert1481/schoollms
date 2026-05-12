<div id="sessionFromTermModal" class="fixed inset-0 bg-black/40 hidden z-50">

    <div id="createDraggableModal"
         class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 absolute max-h-[90vh] overflow-y-auto"
         style="top:80px; left:400px;">

        <div id="createModalHeader" class="flex justify-between mb-4 cursor-move">
            <h2 class="font-extrabold">Open Enrollment Date</h2>
            <button type="button" onclick="closeSessionFromTermModal()">✕</button>
        </div>

        @php
            $currency = \App\Helpers\CurrencyHelper::forCurrentSchool();
        @endphp

        <form method="POST"
              action="{{ route('admission.enrollment-settings.store') }}"
              enctype="multipart/form-data">
            @csrf

            <!-- Hidden fields -->
            <input type="hidden" name="term_id" id="modal_term_id">
            <input type="hidden" name="academic_year_id" id="modal_academic_year_id">
            <input type="hidden" name="name" id="modal_name">
            <input type="hidden" name="title" id="modal_title">
            <input type="hidden" name="currency" value="{{ $currency['code'] }}">

            <div class="space-y-3">
                <div>
                    <label class="text-xs font-bold">Name</label>
                    <input type="text" name="name" id="modal_name_display"
                            class="w-full border rounded-lg px-3 py-2 bg-gray-100"
                            readonly>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold">Start Date</label>
                        <input type="date" name="start_date" id="modal_start_date"
                               class="w-full border rounded-lg px-3 py-2" required>
                    </div>

                    <div>
                        <label class="text-xs font-bold">End Date</label>
                        <input type="date" name="end_date" id="modal_end_date"
                               class="w-full border rounded-lg px-3 py-2" required>
                    </div>
                </div>

                <div data-training-only>
                    <label class="text-xs font-bold">
                        Price ({{ $currency['code'] }})
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">
                            {{ $currency['symbol'] }}
                        </span>
                        <input type="number" step="0.01" min="0"
                               name="price"
                               class="w-full border rounded-lg pl-8 pr-3 py-2"
                               placeholder="0.00">
                    </div>
                </div>

                <div data-training-only>
                    <label class="text-xs font-bold">Cover Image</label>
                    <input type="file" name="cover_image" accept="image/*"
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                    <p class="text-xs text-slate-500 mt-1">
                        Used as the course card image. Large photos are automatically resized.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-3" data-training-only>
                    <div>
                        <label class="text-xs font-bold">Instructor Title</label>
                        <select name="instructor_title"
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
                        <input type="text" name="instructor_name"
                               class="w-full border rounded-lg px-3 py-2"
                               placeholder="Full name">
                    </div>
                </div>

                <div data-training-only>
                    <label class="text-xs font-bold">Course Details</label>
                    <textarea name="course_details" rows="4"
                              class="w-full border rounded-lg px-3 py-2"
                              placeholder="Short description that appears on the course card"></textarea>
                </div>
            </div>

            <div class="flex justify-end mt-4">
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg">
                    Save
                </button>
            </div>
        </form>

    </div>
</div>

<script src="{{ asset('js/modules/draggable.js') }}"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    makeDraggable("createDraggableModal", "createModalHeader");
});
</script>