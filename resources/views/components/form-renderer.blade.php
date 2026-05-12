@props([
    'formKey',
    'model' => null
])

@php
    use App\Services\FormService;
    use Illuminate\Support\Facades\DB;

    $fieldsConfig   = FormService::fields();
    $sectionsConfig = FormService::sections();
    $formConfig     = FormService::form($formKey);

    $userRole   = strtolower(auth()->user()->role ?? '');
    $authSchoolId = auth()->user()->school_id ?? null;

    /**
     * Resolve the current DB value for a field using its save_to config.
     * Falls back to the passed-in $model, then null.
     */
    $resolveFieldValue = function(string $fieldKey, array $field) use ($model, $authSchoolId) {
        // 1) Pivot (many-to-many) — return array of ids
        $pivotDef = $field['model'] ?? null;
        if (is_string($pivotDef) && str_starts_with($pivotDef, 'pivot:')) {
            $pivotTable = substr($pivotDef, 6);
            try {
                return DB::table($pivotTable)
                    ->where('school_id', $authSchoolId)
                    ->pluck('modality_id')
                    ->map(fn($v) => (string) $v)
                    ->all();
            } catch (\Throwable $e) {
                return [];
            }
        }

        // 2) save_to definitions
        if (!empty($field['save_to']) && is_array($field['save_to'])) {
            foreach ($field['save_to'] as $target) {
                $modelClass = $target['model'] ?? null;
                $column     = $target['column'] ?? $fieldKey;
                $where      = $target['where']  ?? [];

                if (!$modelClass || !class_exists($modelClass)) continue;

                foreach ($where as $k => $v) {
                    if ($v === 'auth_school_id') $where[$k] = $authSchoolId;
                }
                if (empty($where) && $authSchoolId) {
                    $where = ['school_id' => $authSchoolId];
                }

                $row = $modelClass::where($where)->first();
                if ($row) return $row->{$column} ?? null;
            }
        }

        // 3) Fallback: the bound $model
        return $model?->{$fieldKey} ?? null;
    };
@endphp

@if(!$formConfig)
    <div class="text-red-500">Form config not found.</div>
    @return
@endif

@if(isset($formConfig['roles']) && !in_array($userRole, $formConfig['roles']))
    <div class="text-red-500">You do not have access to this form.</div>
    @return
@endif

@foreach($formConfig['layout'] as $sectionKey => $rows)

    {{-- SECTION TITLE --}}
    <div class="mt-6 mb-2">
        <h2 class="text-lg font-bold text-slate-700">
            {{ $sectionsConfig[$sectionKey] ?? $sectionKey }}
        </h2>
    </div>

    {{-- SECTION BOX --}}
    <div class="bg-white border border-slate-300 rounded-2xl p-6">

        @foreach($rows as $row)
            <div class="grid grid-cols-4 gap-6 mb-4">

                @foreach($row as $item)

                    @php
                        $fieldKey = $item['field'];
                        $field = $fieldsConfig[$fieldKey] ?? null;
                    @endphp

                    @if(!$field)
                        @continue
                    @endif

                    @if(isset($field['roles']) && !in_array($userRole, $field['roles']))
                        @continue
                    @endif

                    @php
                        $currentValue = $resolveFieldValue($fieldKey, $field);
                        $colSpan = (int)($item['col_span'] ?? 4);
                        $colSpanClass = match($colSpan) {
                            1 => 'col-span-1',
                            2 => 'col-span-2',
                            3 => 'col-span-3',
                            5 => 'col-span-5',
                            6 => 'col-span-6',
                            7 => 'col-span-7',
                            8 => 'col-span-8',
                            9 => 'col-span-9',
                            10 => 'col-span-10',
                            11 => 'col-span-11',
                            12 => 'col-span-12',
                            default => 'col-span-4',
                        };
                    @endphp

                    <div class="{{ $colSpanClass }}">

                        {{-- FIELD LABEL --}}
                        <label class="block text-sm text-slate-500 mb-1">
                            {{ $field['label'] }}
                        </label>

                        {{-- TEXT --}}
                        @if($field['type'] == 'text')
                            @php $isLocked = !empty($field['locked']); @endphp
                            <input type="text" name="{{ $fieldKey }}"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 {{ $isLocked ? 'bg-slate-100 text-slate-600 cursor-not-allowed' : 'bg-white' }}"
                                   value="{{ old($fieldKey, $currentValue) }}"
                                   @if($isLocked) readonly disabled @endif>
                            @if(!empty($field['hint']))
                                <p class="text-xs text-slate-400 mt-1">{{ $field['hint'] }}</p>
                            @endif
                        @endif

                        {{-- NUMBER --}}
                        @if($field['type'] == 'number')
                            <input type="number" name="{{ $fieldKey }}"
                                   class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2"
                                   value="{{ old($fieldKey, $currentValue) }}">
                        @endif

                        {{-- EMAIL --}}
                        @if($field['type'] == 'email')
                            <input type="email" name="{{ $fieldKey }}"
                                   class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2"
                                   value="{{ old($fieldKey, $currentValue) }}">
                        @endif

                        {{-- TEXTAREA --}}
                        @if($field['type'] == 'textarea')
                            <textarea name="{{ $fieldKey }}"
                                      class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2">{{ old($fieldKey, $currentValue) }}</textarea>
                        @endif

                        {{-- SELECT STATIC --}}
                        @if($field['type'] == 'select')
                            <select name="{{ $fieldKey }}"
                                    class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2">
                                @foreach($field['options'] ?? [] as $option)
                                    <option value="{{ $option }}"
                                        {{ old($fieldKey, $currentValue) == $option ? 'selected' : '' }}>
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        {{-- SELECT DB --}}
                        @if($field['type'] == 'select_db')
                            @php
                                $sourceModel = $field['source_model'];
                                $options = $sourceModel::all();
                            @endphp

                            <select name="{{ $fieldKey }}"
                                    class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2">
                                @foreach($options as $opt)
                                    <option value="{{ $opt->{$field['value']} }}"
                                        {{ old($fieldKey, $currentValue) == $opt->{$field['value']} ? 'selected' : '' }}>
                                        {{ $opt->{$field['display']} }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        {{-- CHECKBOX DB --}}
                        @if($field['type'] == 'checkbox_db')
                            @php
                                $sourceModel = $field['source_model'];
                                $options = $sourceModel::all();
                                $selected = is_array($currentValue) ? array_map('strval', $currentValue) : [];
                            @endphp

                            @foreach($options as $opt)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox"
                                           name="{{ $fieldKey }}[]"
                                           value="{{ $opt->{$field['value']} }}"
                                           {{ in_array((string) $opt->{$field['value']}, $selected) ? 'checked' : '' }}>
                                    {{ $opt->{$field['display']} }}
                                </label>
                            @endforeach
                        @endif

                        {{-- CHECKBOX DROPDOWN DB --}}
                        @if($field['type'] == 'checkbox_dropdown_db')
                            @php
                                $sourceModel = $field['source_model'];
                                $options = $sourceModel::all();
                                $selected = is_array($currentValue) ? array_map('strval', $currentValue) : [];
                            @endphp

                            <div x-data="{ open: false }" class="relative">
                                <div @click="open = !open"
                                     class="border border-slate-300 rounded-lg px-3 py-2 cursor-pointer bg-white">
                                    Select {{ $field['label'] }}
                                </div>

                                <div x-show="open"
                                     @click.outside="open = false"
                                     class="absolute bg-white border border-slate-300 rounded-lg shadow-lg mt-2 p-3 w-full z-50">

                                    @foreach($options as $opt)
                                        <label class="flex items-center gap-2 py-1">
                                            <input type="checkbox"
                                                   name="{{ $fieldKey }}[]"
                                                   value="{{ $opt->{$field['value']} }}"
                                                   {{ in_array((string) $opt->{$field['value']}, $selected) ? 'checked' : '' }}>
                                            {{ $opt->{$field['display']} }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- CHECKBOX DROPDOWN STATIC --}}
                        @if($field['type'] == 'checkbox_dropdown_static')
                            @php
                                $selected = is_array($currentValue)
                                    ? $currentValue
                                    : (is_string($currentValue) && $currentValue !== '' ? explode(',', $currentValue) : []);
                            @endphp
                            <div x-data="{ open: false }" class="relative">
                                <div @click="open = !open"
                                     class="border border-slate-300 rounded-lg px-3 py-2 cursor-pointer bg-white">
                                    Select {{ $field['label'] }}
                                </div>

                                <div x-show="open"
                                     @click.outside="open = false"
                                     class="absolute bg-white border border-slate-300 rounded-lg shadow-lg mt-2 p-3 w-full z-50">

                                    @foreach($field['options'] as $opt)
                                        <label class="flex items-center gap-2 py-1">
                                            <input type="checkbox"
                                                   name="{{ $fieldKey }}[]"
                                                   value="{{ $opt }}"
                                                   {{ in_array($opt, $selected) ? 'checked' : '' }}>
                                            {{ $opt }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- FILE --}}
                        @if($field['type'] == 'file')
                            @if(!empty($currentValue))
                                <div class="mb-2">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($currentValue) }}"
                                         alt="current {{ $field['label'] }}"
                                         class="h-20 object-contain rounded border border-slate-200 bg-slate-50 p-1">
                                </div>
                            @endif
                            <input type="file" name="{{ $fieldKey }}"
                                   class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2">
                        @endif

                        {{-- DATE --}}
                        @if($field['type'] == 'date')
                            <input type="date" name="{{ $fieldKey }}"
                                   class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2"
                                   value="{{ old($fieldKey, $currentValue) }}">
                        @endif

                        {{-- TIME --}}
                        @if($field['type'] == 'time')
                            <input type="time" name="{{ $fieldKey }}"
                                   class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2"
                                   value="{{ old($fieldKey, $currentValue) }}">
                        @endif

                        {{-- DATETIME --}}
                        @if($field['type'] == 'datetime')
                            <input type="datetime-local" name="{{ $fieldKey }}"
                                   class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2"
                                   value="{{ old($fieldKey, $currentValue) }}">
                        @endif

                    </div>

                @endforeach

            </div>
        @endforeach

    </div>

@endforeach