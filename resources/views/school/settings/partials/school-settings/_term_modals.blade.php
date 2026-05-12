{{-- =========================
    TERM MODALS  (uses reusable x-modal.form, draggable)
========================= --}}

{{-- CREATE TERM --}}
<x-modal.form id="createTermModal"
              title="Create Term"
              widthClass="w-full max-w-lg">
    <form id="createTermForm" method="POST" action="#" class="space-y-4">
        @csrf

        <div id="createTermSub" class="text-xs text-slate-500"></div>

        <div>
            <label class="block text-sm font-bold mb-1">Enrollment Type</label>
            <select id="create_enrollment_type"
                    name="enrollment_type"
                    onchange="toggleTitleField('create')"
                    class="w-full rounded-lg border p-2">
                <option value="" disabled selected>Select enrollment type</option>
                <option value="regular">Regular</option>
                <option value="special">Special</option>
            </select>
        </div>

        <div id="createTitleField" class="hidden">
            <label class="block text-sm font-bold mb-1">Title</label>
            <input id="create_title" type="text" name="title"
                   placeholder="e.g. LET, Civil Service, Excel Training"
                   class="w-full rounded-lg border p-2">
        </div>

        <div>
            <label class="block text-sm font-bold mb-1">Term Type</label>
            <select id="create_term_term" name="term" class="w-full rounded-lg border p-2">
                <option value="" disabled selected>Select a term type</option>
                <option value="1st Semester" data-type="regular">1st Semester</option>
                <option value="2nd Semester" data-type="regular">2nd Semester</option>
                <option value="3rd Semester" data-type="regular">3rd Semester</option>
                <option value="4th Semester" data-type="regular">4th Semester</option>
                <option value="Summer" data-type="regular">Summer</option>
                <option value="Training" data-type="special">Training</option>
                <option value="Short Course" data-type="special">Short Course</option>
                <option value="Review" data-type="special">Review</option>
                <option value="Seminar" data-type="special">Seminar</option>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1">Start date</label>
                <input id="create_term_start" type="date" name="start_date"
                       class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1">End date</label>
                <input id="create_term_end" type="date" name="end_date"
                       class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-2">
            </div>
        </div>

        <div class="text-xs text-slate-500">
            Note: term dates must be within the academic year range.
        </div>
    </form>
</x-modal.form>


{{-- EDIT TERM --}}
<x-modal.form id="editTermModal"
              title="Edit Term"
              widthClass="w-full max-w-lg">
    <form id="editTermForm" method="POST" action="#" class="space-y-4">
        @csrf
        @method('PUT')

        <div id="editTermSub" class="text-xs text-slate-500"></div>

        <div>
            <label class="block text-sm font-bold mb-1">Enrollment Type</label>
            <select id="edit_enrollment_type"
                    name="enrollment_type"
                    onchange="toggleTitleField('edit')"
                    class="w-full rounded-lg border p-2">
                <option value="" disabled>Select enrollment type</option>
                <option value="regular">Regular</option>
                <option value="special">Special</option>
            </select>
        </div>

        <div id="editTitleField" class="hidden">
            <label class="block text-sm font-bold mb-1">Title</label>
            <input id="edit_title" type="text" name="title"
                   placeholder="e.g. LET, Civil Service, Excel Training"
                   class="w-full rounded-lg border p-2">
        </div>

        <div>
            <label class="block text-sm font-bold mb-1">Term Type</label>
            <select id="edit_term_term" name="term" class="w-full rounded-lg border p-2">
                <option value="" disabled>Select a term type</option>
                <option value="1st Semester" data-type="regular">1st Semester</option>
                <option value="2nd Semester" data-type="regular">2nd Semester</option>
                <option value="3rd Semester" data-type="regular">3rd Semester</option>
                <option value="4th Semester" data-type="regular">4th Semester</option>
                <option value="Summer" data-type="regular">Summer</option>
                <option value="Training" data-type="special">Training</option>
                <option value="Short Course" data-type="special">Short Course</option>
                <option value="Review" data-type="special">Review</option>
                <option value="Seminar" data-type="special">Seminar</option>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1">Start date</label>
                <input id="edit_term_start" type="date" name="start_date"
                       class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1">End date</label>
                <input id="edit_term_end" type="date" name="end_date"
                       class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-2">
            </div>
        </div>
    </form>
</x-modal.form>
