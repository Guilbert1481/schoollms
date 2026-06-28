{{--
    Reusable table action-column renderer.

    - Renders each action (modal | link | js | delete) as an ICON-ONLY button.
    - Hovering a button reveals its text label (styled tooltip).
    - When there are more than {{ $threshold }} actions, they collapse into a
      kebab (⋮) dropdown to keep the row clean.

    Icons come from the action's optional 'icon' key (a Lucide name); otherwise
    a sensible icon is inferred from the action name/label. So existing
    config/tables/table-actions.php entries work without changes.

    Usage:
        <x-table.actions :actions="$actions" :row="$row"
                         :tableKey="$tableKey" :deleteRoute="$deleteRoute" />
--}}
@props([
    'actions' => [],
    'row' => null,
    'tableKey' => '',
    'deleteRoute' => null,
    'threshold' => 3,
])

@php
    $actions = array_values(array_filter($actions ?? []));
    $rowId = is_object($row) ? ($row->id ?? null) : (is_array($row) ? ($row['id'] ?? null) : null);
    $useKebab = count($actions) > (int) $threshold;

    $resolveIcon = function (array $a) {
        if (! empty($a['icon'])) {
            return $a['icon'];
        }
        $key = strtolower((string) ($a['name'] ?? $a['label'] ?? ''));
        return match (true) {
            str_contains($key, 'edit')                                  => 'pencil',
            str_contains($key, 'delete') || str_contains($key, 'remove') => 'trash-2',
            str_contains($key, 'preview') || str_contains($key, 'view')  => 'eye',
            str_contains($key, 'create') || str_contains($key, 'add-on') => 'plus',
            str_contains($key, 'assign')                                 => 'user-plus',
            str_contains($key, 'share')                                  => 'share-2',
            str_contains($key, 'send')                                   => 'send',
            str_contains($key, 'manage')                                 => 'settings-2',
            str_contains($key, 'update')                                 => 'refresh-cw',
            str_contains($key, 'module')                                 => 'puzzle',
            str_contains($key, 'download')                               => 'download',
            default                                                      => 'circle-dot',
        };
    };
@endphp

@if(! empty($actions))
    @if($useKebab)
        <div x-data="{ open: false }" class="relative inline-flex">
            <button type="button"
                    @click="open = ! open"
                    @click.away="open = false"
                    @keydown.escape.window="open = false"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"
                    aria-label="More actions" title="More actions">
                <i data-lucide="more-vertical" class="h-4 w-4"></i>
            </button>

            <div x-show="open" x-transition x-cloak
                 class="absolute right-0 top-9 z-30 min-w-[180px] overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                 style="display:none;">
                @foreach($actions as $action)
                    @include('components.table.action-control', [
                        'action' => $action,
                        'rowId' => $rowId,
                        'tableKey' => $tableKey,
                        'deleteRoute' => $deleteRoute,
                        'icon' => $resolveIcon($action),
                        'style' => 'menu',
                    ])
                @endforeach
            </div>
        </div>
    @else
        <div class="flex items-center gap-1">
            @foreach($actions as $action)
                @include('components.table.action-control', [
                    'action' => $action,
                    'rowId' => $rowId,
                    'tableKey' => $tableKey,
                    'deleteRoute' => $deleteRoute,
                    'icon' => $resolveIcon($action),
                    'style' => 'icon',
                ])
            @endforeach
        </div>
    @endif
@endif
