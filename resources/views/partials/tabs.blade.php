@if(!empty($tabs))
<div class="flex gap-6 border-b mb-4">

@php
    $role = strtolower(auth()->user()->role->name ?? auth()->user()->role ?? '');
@endphp

@foreach($tabs as $tab)

    @php
        if (!is_array($tab)) continue;

        // Role check
        $allowed = true;
        if(isset($tab['roles'])){
            $allowed = in_array($role, $tab['roles']);
        }

        // Active state
        $isActive = false;
        if(isset($tab['active'])){
            $isActive = request()->routeIs($tab['active']);
        } elseif(isset($tab['route'])){
            $isActive = request()->routeIs($tab['route']);
        }

        // Query-param disambiguation for tabs that share the same route
        if ($isActive && isset($tab['active_query'])) {
            $queryKey   = $tab['active_query']['key']   ?? null;
            $queryValue = $tab['active_query']['value'] ?? null;
            if ($queryKey !== null) {
                $current = request()->query($queryKey);
                $isActive = ($current ?? '') === (string) $queryValue
                         || ($current === null && ($tab['active_query']['default'] ?? false));
            }
        }
    @endphp

    @if(!$allowed)
        @continue
    @endif

    {{-- TAB WITH DROPDOWN --}}
    @if(isset($tab['children']))
        <div class="relative" x-data="{ open: false }">

            <button
                @click="open = !open"
                class="px-3 py-2 border-b-2 flex items-center gap-1
                {{ $isActive ? 'border-blue-600 text-blue-600' : 'border-transparent' }}">
                {{ $tab['label'] }}
                <span>▼</span>
            </button>

            <div x-show="open"
                 @click.outside="open = false"
                 class="absolute bg-white shadow rounded mt-2 w-44 z-50">

                @foreach($tab['children'] as $child)
                    @php
                        $childIsActive = request()->routeIs($child['active'] ?? '');

                        if ($childIsActive && isset($child['active_query'])) {
                            $queryKey = $child['active_query']['key'] ?? null;
                            $queryValue = $child['active_query']['value'] ?? null;
                            $childIsActive = $queryKey ? request()->query($queryKey) === $queryValue : $childIsActive;
                        }
                    @endphp

                    <a href="{{ route($child['route'], $child['params'] ?? []) }}"
                       class="block px-4 py-2 hover:bg-gray-100
                       {{ $childIsActive ? 'bg-gray-100 font-semibold' : '' }}">
                        {{ $child['label'] }}
                    </a>
                @endforeach

            </div>

        </div>

    {{-- NORMAL TAB --}}
    @elseif(isset($tab['route']))
        <a href="{{ route($tab['route'], $tab['params'] ?? []) }}"
           class="px-3 py-2 border-b-2
           {{ $isActive ? 'border-blue-600 text-blue-600' : 'border-transparent' }}">
            {{ $tab['label'] }}
        </a>
    @endif

@endforeach

</div>
@endif