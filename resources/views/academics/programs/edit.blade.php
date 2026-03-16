<div class="mt-8 border-t pt-6">

    <h3 class="text-lg font-semibold mb-4">
        Admission Test Rules
    </h3>

    <!-- Hidden fallback -->
    <input type="hidden" name="requires_admission_test" value="0">

    <div class="mb-4">
        <label class="flex items-center gap-3">
            <input type="checkbox"
                   name="requires_admission_test"
                   value="1"
                   {{ $program->requires_admission_test ? 'checked' : '' }}
                   class="w-5 h-5">
            <span>Require Admission Test for this Program</span>
        </label>
    </div>

    <div class="mb-4">
        <label class="block mb-2 font-medium">
            Passing Score (%)
        </label>
        <input type="number"
               name="admission_passing_score"
               min="1"
               max="100"
               value="{{ $program->admission_passing_score }}"
               class="w-full border rounded-lg p-3">
    </div>

    <div class="mb-4">
        <label class="block mb-2 font-medium">
            Maximum Attempts
        </label>
        <input type="number"
               name="admission_max_attempts"
               min="1"
               value="{{ $program->admission_max_attempts }}"
               class="w-full border rounded-lg p-3">
    </div>

</div>
