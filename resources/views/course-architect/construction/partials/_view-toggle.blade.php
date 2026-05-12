{{--
    Reusable Card/List view toggle for Lesson Studio folder browser.
    Used in two places (one per view mode). Each instance is bound on the
    client side via lessonStudioBindToggle() in lesson-studio.blade.php.
--}}
<div class="relative" data-view-toggle="{{ $idSuffix }}">
    <button type="button"
            data-role="btn"
            class="inline-flex items-center gap-2 rounded-md bg-white px-3 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-slate-300 shadow-sm hover:bg-slate-50">
        <i data-lucide="layout-grid" data-role="icon" class="w-4 h-4"></i>
        <span data-role="label">Card</span>
        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400"></i>
    </button>
    <div data-role="menu"
         class="hidden absolute right-0 mt-1 w-32 rounded-lg bg-white shadow-lg ring-1 ring-slate-200 overflow-hidden z-30">
        <button type="button" data-view="card"
                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
            <i data-lucide="layout-grid" class="w-4 h-4"></i> Card
        </button>
        <button type="button" data-view="list"
                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
            <i data-lucide="list" class="w-4 h-4"></i> List
        </button>
    </div>
</div>
