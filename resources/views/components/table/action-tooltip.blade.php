{{--
    Hover tooltip for an icon-only action button. Positioned above and
    right-aligned to the button so it never gets clipped by the table's
    horizontal scroll container (the action column is the right-most cell).
    The parent button must carry the `relative group` classes.
--}}
@props(['label' => ''])

<span class="pointer-events-none absolute bottom-full right-0 mb-2 z-30 whitespace-nowrap
             rounded bg-slate-800 px-2 py-1 text-[11px] font-medium text-white shadow
             opacity-0 transition-opacity duration-150 group-hover:opacity-100">
    {{ $label }}
</span>
