{{-- =========================
    ACADEMIC YEAR MODALS  (uses reusable x-modal.form, draggable)
========================= --}}

{{-- CREATE AY --}}
<x-modal.form id="createAYModal"
              title="Create Academic Year"
              widthClass="w-full max-w-lg">
    <form id="createAYForm"
          method="POST"
          action="{{ route('school.settings.master-data.academic_year.store') }}"
          class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-2"
                   placeholder="2025-2026">
            @error('name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1">Start date</label>
                <input type="date" name="start_date" value="{{ old('start_date') }}"
                       class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-2">
                @error('start_date') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1">End date</label>
                <input type="date" name="end_date" value="{{ old('end_date') }}"
                       class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-2">
                @error('end_date') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
        </div>
    </form>
</x-modal.form>


{{-- EDIT AY --}}
<x-modal.form id="editAYModal"
              title="Edit Academic Year"
              widthClass="w-full max-w-lg">
    <form id="editAYForm" method="POST" action="#" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1">Name</label>
            <input id="edit_ay_name" type="text" name="name"
                   class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-2">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1">Start date</label>
                <input id="edit_ay_start_date" type="date" name="start_date"
                       class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-2">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1">End date</label>
                <input id="edit_ay_end_date" type="date" name="end_date"
                       class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-2">
            </div>
        </div>
    </form>
</x-modal.form>
