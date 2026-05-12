{{--
    Shared form. Expects: $curriculum, $programs, $termModes, $action, $method ('POST'|'PUT')
--}}

@if ($errors->any())
    <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-800 rounded text-sm">
        <strong>Please fix the following:</strong>
        <ul class="ml-5 mt-1 list-disc">
            @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="bg-white border rounded shadow p-6 space-y-6">
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif

    {{-- ============ IDENTITY ============ --}}
    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">Identity</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Program <span class="text-red-500">*</span></span>
                <select name="program_id" required class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
                    <option value="">— Select program —</option>
                    @foreach ($programs as $p)
                        <option value="{{ $p->id }}" @selected((string)old('program_id', $curriculum->program_id) === (string)$p->id)>
                            {{ $p->code }} — {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Version <span class="text-red-500">*</span></span>
                <input type="text" name="version" required maxlength="32"
                       value="{{ old('version', $curriculum->version) }}"
                       class="mt-1 block w-full border rounded px-2 py-1.5 text-sm"
                       placeholder="e.g. 2024 or REV-A">
            </label>

            <label class="block md:col-span-2">
                <span class="text-sm font-semibold text-slate-700">Curriculum Name <span class="text-red-500">*</span></span>
                <input type="text" name="name" required maxlength="255"
                       value="{{ old('name', $curriculum->name) }}"
                       class="mt-1 block w-full border rounded px-2 py-1.5 text-sm"
                       placeholder="e.g. BSEd English Curriculum 2024">
            </label>
        </div>
    </section>

    {{-- ============ TERM MODE (the toggle) ============ --}}
    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">Term Mode</h3>
        <p class="text-xs text-slate-500 mb-3">
            Determines how many terms make up one academic year. Validators, seeders, and the
            wizard's curriculum step all branch off this value.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @php $current = (int) old('terms_per_year', $curriculum->terms_per_year ?? 2); @endphp

            <label class="block cursor-pointer">
                <input type="radio" name="terms_per_year" value="2" class="peer sr-only" @checked($current === 2)>
                <div class="border-2 rounded-lg p-4 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 border-slate-200">
                    <div class="font-bold text-slate-800">Bi-semester</div>
                    <div class="text-xs text-slate-500 mt-1">2 terms per year (1st &amp; 2nd Semester). Standard CHED layout.</div>
                </div>
            </label>

            <label class="block cursor-pointer">
                <input type="radio" name="terms_per_year" value="3" class="peer sr-only" @checked($current === 3)>
                <div class="border-2 rounded-lg p-4 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 border-slate-200">
                    <div class="font-bold text-slate-800">Trimester</div>
                    <div class="text-xs text-slate-500 mt-1">3 terms per year. Lighter per-term load, faster cadence.</div>
                </div>
            </label>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <input type="hidden" name="has_summer_term" value="0">
            <input type="checkbox" name="has_summer_term" value="1"
                   id="has_summer_term"
                   @checked(old('has_summer_term', $curriculum->has_summer_term ?? false))>
            <label for="has_summer_term" class="text-sm font-semibold text-slate-700 cursor-pointer">
                Enable Summer/Midyear Term
            </label>
        </div>
    </section>

    {{-- ============ EFFECTIVITY ============ --}}
    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">Effectivity</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Effective From</span>
                <input type="date" name="effective_from"
                       value="{{ old('effective_from', optional($curriculum->effective_from)->format('Y-m-d')) }}"
                       class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Effective To</span>
                <input type="date" name="effective_to"
                       value="{{ old('effective_to', optional($curriculum->effective_to)->format('Y-m-d')) }}"
                       class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">
            </label>
            <label class="flex items-center gap-2 mt-7">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $curriculum->is_active ?? true))>
                <span class="text-sm font-semibold text-slate-700">Active</span>
            </label>
        </div>
    </section>

    {{-- ============ DESCRIPTION ============ --}}
    <section>
        <label class="block">
            <span class="text-sm font-semibold text-slate-700">Description</span>
            <textarea name="description" rows="3" maxlength="1000"
                      class="mt-1 block w-full border rounded px-2 py-1.5 text-sm">{{ old('description', $curriculum->description) }}</textarea>
        </label>
    </section>

    <div class="flex justify-between pt-4 border-t">
        <a href="{{ route('dean.curricula.index') }}"
           class="px-4 py-2 bg-slate-200 rounded text-sm">Cancel</a>
        <button type="submit"
                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-sm font-semibold">
            Save Curriculum
        </button>
    </div>
</form>
