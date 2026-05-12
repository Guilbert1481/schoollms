@php
    // Ensure tabs is always an array
    if (!isset($tabs) || !is_array($tabs)) {
        $tabs = [];
    }

    $role = strtolower(auth()->user()->role->name ?? auth()->user()->role ?? '');
@endphp

@if(count($tabs))
<div class="bg-white border border-slate-200 rounded-2xl p-2 flex gap-2">

    @foreach($tabs as $tab)

        @php
            // Skip invalid tab entries
            if (!is_array($tab)) {
                continue;
            }

            $allowed = true;

            if (isset($tab['roles'])) {
                $allowed = in_array($role, $tab['roles']);
            }

            // Active state
            $isActive = false;

            if (isset($tab['active'])) {
                $isActive = request()->routeIs($tab['active']);
            } elseif (isset($tab['route'])) {
                $isActive = request()->routeIs($tab['route']);
            }
        @endphp

        @if($allowed && isset($tab['route']))
            <a href="{{ route($tab['route']) }}"
               class="px-4 py-2 rounded-lg font-bold whitespace-nowrap transition
               {{ $isActive 
                    ? 'bg-slate-200 text-indigo-600'
                    : 'text-slate-500 hover:bg-slate-100 hover:text-indigo-600' }}">
                {{ $tab['label'] ?? 'Tab' }}
            </a>
        @endif

    @endforeach

</div>
@endif