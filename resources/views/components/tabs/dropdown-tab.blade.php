@php
    /**
     * Dropdown tab selector — same convention as components.tabs.horizontal-tab
     * but renders a <select> with optional count badges. Useful when the list
     * of "tabs" is dynamic (e.g. DB-driven) or too long to lay out horizontally.
     *
     * Expected $tabs item shape:
     *   [
     *     'label'  => 'Basic Education',
     *     'url'    => 'https://.../?level=1',
     *     'count'  => 2,            // optional
     *     'active' => true|false,   // optional — drives the selected option
     *   ]
     *
     * Optional props:
     *   $label        Label rendered to the left of the select.
     *   $placeholder  First disabled option (omit to skip).
     */
    if (!isset($tabs) || !is_array($tabs)) {
        $tabs = [];
    }
    $label       = $label       ?? 'Filter';
    $placeholder = $placeholder ?? null;
@endphp

@if(count($tabs))
    <div class="flex items-center gap-3">
        @if($label)
            <label class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                {{ $label }}
            </label>
        @endif

        <div class="relative">
            <select onchange="if(this.value) window.location.href=this.value"
                    class="appearance-none pr-9 pl-3 py-2 text-sm font-semibold rounded-lg border border-slate-300 bg-white text-slate-800 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                @if($placeholder)
                    <option value="" disabled @selected(!collect($tabs)->contains(fn($t) => $t['active'] ?? false))>
                        {{ $placeholder }}
                    </option>
                @endif

                @foreach($tabs as $tab)
                    @if(!is_array($tab) || empty($tab['url'])) @continue @endif
                    <option value="{{ $tab['url'] }}" @selected($tab['active'] ?? false)>
                        {{ $tab['label'] ?? 'Tab' }}@isset($tab['count']) ({{ $tab['count'] }})@endisset
                    </option>
                @endforeach
            </select>
            <i data-lucide="chevron-down"
               class="w-4 h-4 absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        </div>
    </div>
@endif
