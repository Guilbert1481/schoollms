{{--
    Reusable education-level tab bar for list pages (Student Ledgers pattern):
    an "All Levels" tab plus one tab per offered top-level education node,
    navigating via a `level` query param on the given route.

    Usage:
        <x-table.level-tabs route="finance.payments.index"
                            :levels="$levels"
                            :activeLevelId="$activeLevelId"
                            :showAll="$showAll"
                            :params="['tab' => 'payments']" />

    - levels:        iterable of objects with ->id and ->name (offered roots).
    - activeLevelId: currently selected level id (0/ignored when showAll).
    - showAll:       whether the "All Levels" tab is the active one.
    - params:        extra query params to carry on every tab URL (e.g. which
                     page tab pane to return to). Level tabs intentionally do
                     NOT carry the other filter params (same as Student Ledgers).
    - counts:        optional [levelId => int] red-badge counts per level.
    - allLabel:      label for the first tab (default "All Levels") — e.g.
                     "All Grade Levels" when the levels are basic-ed grades.

    Hidden entirely when the school offers a single level (no tabs needed).
--}}
@props([
    'route',
    'levels' => [],
    'activeLevelId' => 0,
    'showAll' => true,
    'params' => [],
    'counts' => [],
    'accent' => 'indigo',
    'allLabel' => 'All Levels',
])

@php
    $levelList = collect($levels);
@endphp

@if($levelList->count() > 1)
    @php
        $tabs = [[
            'label'  => $allLabel,
            'url'    => route($route, array_merge($params, ['level' => 'all'])),
            'active' => (bool) $showAll,
        ]];
        foreach ($levelList as $lvl) {
            $tabs[] = [
                'label'  => $lvl->name,
                'url'    => route($route, array_merge($params, ['level' => $lvl->id])),
                'active' => ! $showAll && (int) $activeLevelId === (int) $lvl->id,
                'count'  => (int) ($counts[$lvl->id] ?? 0),
            ];
        }
    @endphp
    <x-tabs.count-tabs :tabs="$tabs" :accent="$accent" />
@endif
