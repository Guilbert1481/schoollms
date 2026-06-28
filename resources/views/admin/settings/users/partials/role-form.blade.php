@php
    $prefix = $prefix ?? 'role';
    $badgeColors = [
        'bg-gray-100' => 'Gray',
        'bg-indigo-100' => 'Indigo',
        'bg-emerald-100' => 'Emerald',
        'bg-sky-100' => 'Sky',
        'bg-violet-100' => 'Violet',
        'bg-amber-100' => 'Amber',
        'bg-rose-100' => 'Rose',
        'bg-teal-100' => 'Teal',
    ];
    $textColors = [
        'text-gray-700' => 'Gray',
        'text-indigo-700' => 'Indigo',
        'text-emerald-700' => 'Emerald',
        'text-sky-700' => 'Sky',
        'text-violet-700' => 'Violet',
        'text-amber-700' => 'Amber',
        'text-rose-700' => 'Rose',
        'text-teal-700' => 'Teal',
    ];
@endphp

<div class="space-y-4">
    <div>
        <label for="{{ $prefix }}_role_name" class="mb-1 block text-sm font-semibold text-slate-700">Role Name</label>
        <input id="{{ $prefix }}_role_name"
               name="role_name"
               type="text"
               required
               maxlength="255"
               placeholder="e.g. Finance Manager"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label for="{{ $prefix }}_is_head_role" class="mb-1 block text-sm font-semibold text-slate-700">Role Type</label>
            <select id="{{ $prefix }}_is_head_role"
                    name="is_head_role"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <option value="0">Regular</option>
                <option value="1">Head</option>
            </select>
        </div>

        <div>
            <label for="{{ $prefix }}_badge_color" class="mb-1 block text-sm font-semibold text-slate-700">Badge Fill</label>
            <select id="{{ $prefix }}_badge_color"
                    name="badge_color"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                @foreach($badgeColors as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="{{ $prefix }}_badge_text_color" class="mb-1 block text-sm font-semibold text-slate-700">Badge Text</label>
            <select id="{{ $prefix }}_badge_text_color"
                    name="badge_text_color"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                @foreach($textColors as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
