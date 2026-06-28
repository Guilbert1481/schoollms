{{--
    Renders a single table action in one of two styles:
      - 'icon' : an icon-only button with a hover tooltip (for the inline row)
      - 'menu' : an icon + label row (for the kebab dropdown)

    Expected vars: $action, $rowId, $tableKey, $deleteRoute, $icon, $style
    Preserves the original per-type behaviour (modal | link | js | delete).
--}}
@php
    $type  = $action['type'] ?? 'js';
    $label = $action['label'] ?? '';
    $cls   = $action['class'] ?? '';

    // Modal handler ({id}/{table}/{modal} placeholders) — same as before.
    $modalOnclick = isset($action['handler'])
        ? strtr($action['handler'], ['{id}' => $rowId, '{table}' => $tableKey, '{modal}' => $action['modal'] ?? ''])
        : "openEditModal('".($action['modal'] ?? '')."', ".$rowId.", '".$tableKey."')";

    $jsOnclick = ($action['handler'] ?? '').'('.$rowId.')';

    $deleteActionUrl = (isset($deleteRoute) && $deleteRoute)
        ? route($deleteRoute, $rowId)
        : route('school.system.dynamic.destroy', ['table' => $tableKey, 'id' => $rowId]);

    $confirm = $action['confirm'] ?? 'Are you sure you want to delete this record? This action cannot be undone.';

    // Inline icon button: colored chip (uses the action's own class).
    $iconBtn = 'relative group inline-flex h-8 w-8 items-center justify-center rounded-lg transition '.$cls;

    // Kebab menu row.
    $menuRow = 'flex w-full items-center gap-2 px-3 py-2 text-left text-sm '
        .($type === 'delete' ? 'text-rose-600 hover:bg-rose-50' : 'text-slate-700 hover:bg-slate-50');
@endphp

@if($style === 'menu')
    {{-- ===================== KEBAB MENU ROW ===================== --}}
    @if($type === 'modal')
        <button type="button" onclick="{{ $modalOnclick }}" class="{{ $menuRow }}">
            <i data-lucide="{{ $icon }}" class="h-4 w-4 shrink-0"></i><span>{{ $label }}</span>
        </button>
    @elseif($type === 'link')
        <a href="{{ route($action['route'], $rowId) }}" class="{{ $menuRow }}">
            <i data-lucide="{{ $icon }}" class="h-4 w-4 shrink-0"></i><span>{{ $label }}</span>
        </a>
    @elseif($type === 'delete')
        <form method="POST" data-confirm="{{ $confirm }}" action="{{ $deleteActionUrl }}" class="m-0">
            @csrf
            @method('DELETE')
            <button type="submit" class="{{ $menuRow }}">
                <i data-lucide="{{ $icon }}" class="h-4 w-4 shrink-0"></i><span>{{ $label }}</span>
            </button>
        </form>
    @else
        <button type="button" onclick="{{ $jsOnclick }}" class="{{ $menuRow }}">
            <i data-lucide="{{ $icon }}" class="h-4 w-4 shrink-0"></i><span>{{ $label }}</span>
        </button>
    @endif
@else
    {{-- ===================== INLINE ICON BUTTON ===================== --}}
    @if($type === 'modal')
        <button type="button" onclick="{{ $modalOnclick }}" class="{{ $iconBtn }}" aria-label="{{ $label }}">
            <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
            <x-table.action-tooltip :label="$label" />
        </button>
    @elseif($type === 'link')
        <a href="{{ route($action['route'], $rowId) }}" class="{{ $iconBtn }}" aria-label="{{ $label }}">
            <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
            <x-table.action-tooltip :label="$label" />
        </a>
    @elseif($type === 'delete')
        <form method="POST" data-confirm="{{ $confirm }}" action="{{ $deleteActionUrl }}" class="m-0 inline-flex">
            @csrf
            @method('DELETE')
            <button type="submit" class="{{ $iconBtn }}" aria-label="{{ $label }}">
                <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                <x-table.action-tooltip :label="$label" />
            </button>
        </form>
    @else
        <button type="button" onclick="{{ $jsOnclick }}" class="{{ $iconBtn }}" aria-label="{{ $label }}">
            <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
            <x-table.action-tooltip :label="$label" />
        </button>
    @endif
@endif
