{{-- =========================
    SUBJECT OFFERING MODAL  (uses reusable x-modal.form, draggable)
========================= --}}
<x-modal.form id="addSubjectOfferingModal"
              title="Add Subject Offering"
              widthClass="w-full max-w-lg">
    <form id="addSubjectOfferingForm"
          method="POST"
          action="{{ route('subject-offerings.store') }}"
          class="space-y-4">
        @csrf
        <input type="hidden" name="term_id" id="offering_term_id">

        <div id="offeringTermLabel" class="text-xs text-slate-500"></div>

        <div>
            <label class="block text-sm font-bold mb-1">Subject</label>
            <select name="subject_id" required class="w-full rounded-lg border p-2">
                <option value="" disabled selected>-- Select subject --</option>
                @foreach(($subjects ?? []) as $subject)
                    <option value="{{ $subject->id }}">
                        {{ $subject->code }} — {{ $subject->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-bold mb-1">Program (optional)</label>
            <select name="program_id" class="w-full rounded-lg border p-2">
                <option value="">All Programs</option>
                @foreach(($programs ?? []) as $program)
                    <option value="{{ $program->id }}">
                        {{ $program->code ? $program->code . ' — ' : '' }}{{ $program->name }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-slate-500 mt-1">Leave blank to offer to all programs.</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-1">Year Level (optional)</label>
                <input type="number" name="year_level" min="1" max="10"
                       class="w-full rounded-lg border p-2" placeholder="e.g. 1">
            </div>

            <div class="flex items-end gap-4">
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="is_open" value="0">
                    <input type="checkbox" name="is_open" value="1" checked class="rounded">
                    <span class="text-sm font-bold">Open</span>
                </label>

                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="is_for_irregular" value="0">
                    <input type="checkbox" name="is_for_irregular" value="1" checked class="rounded">
                    <span class="text-sm font-bold">Allow Irregular</span>
                </label>
            </div>
        </div>
    </form>
</x-modal.form>
