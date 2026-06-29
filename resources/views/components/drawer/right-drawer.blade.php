{{--
    Reusable resizable right-side drawer.

    Slides in from the right over a backdrop. Drag the handle on its LEFT edge to
    resize (left = wider, right = narrower); the chosen width is remembered per
    drawer id. Full-width on phones (resize disabled).

    Usage — static content:
        <x-drawer.right-drawer id="myDrawer">
            ... content ...
        </x-drawer.right-drawer>

    Usage — AJAX content (load a partial on demand):
        <x-drawer.right-drawer id="myDrawer" />
        <script>RightDrawer.load('myDrawer', '/some/url');</script>

    JS API (global, defined once):
        RightDrawer.open(id) | RightDrawer.close(id) | RightDrawer.load(id, url)

    Styling is scoped CSS + @media (not Tailwind sm:/md: variants) so it works
    regardless of the compiled build.
--}}
@props([
    'id'    => 'rightDrawer',
    'width' => 480,   // default panel width (px)
    'min'   => 360,   // min resize width (px)
    'max'   => 760,   // max resize width (px)
])

<style>
    .rd { position: fixed; inset: 0; z-index: 60; opacity: 0; pointer-events: none; transition: opacity .2s ease; }
    .rd.rd--open { opacity: 1; pointer-events: auto; }
    .rd__backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, .45); }
    .rd__panel {
        position: absolute; top: 0; right: 0; height: 100%;
        display: flex; flex-direction: column;
        background: #fff; max-width: 100vw;
        box-shadow: -10px 0 36px -12px rgba(15, 23, 42, .4);
        transform: translateX(100%); transition: transform .25s ease;
    }
    .rd.rd--open .rd__panel { transform: translateX(0); }
    .rd__resizer { position: absolute; top: 0; left: 0; width: 9px; height: 100%; cursor: col-resize; z-index: 5; user-select: none; touch-action: none; }
    .rd__resizer::after { content: ''; position: absolute; left: 3px; top: 0; width: 2px; height: 100%; background: #e2e8f0; transition: background .12s ease, width .12s ease; }
    .rd__resizer:hover::after, .rd__resizer:active::after { width: 3px; background: #6366f1; }
    .rd__close {
        position: absolute; top: 14px; right: 14px; z-index: 6;
        display: inline-flex; align-items: center; justify-content: center;
        height: 32px; width: 32px; border-radius: 9999px; color: #64748b; background: transparent;
    }
    .rd__close:hover { background: #f1f5f9; color: #0f172a; }
    .rd__content { flex: 1; overflow-y: auto; overscroll-behavior: contain; }
    .rd__loading { padding: 4rem 1rem; text-align: center; color: #94a3b8; font-size: .875rem; }
    @media (max-width: 767.98px) {
        .rd__panel { width: 100% !important; }
        .rd__resizer { display: none; }
    }
</style>

<div id="{{ $id }}" class="rd" data-rd-min="{{ (int) $min }}" data-rd-max="{{ (int) $max }}" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="rd__backdrop" data-rd-close></div>
    <aside class="rd__panel" style="width: {{ (int) $width }}px;">
        <div class="rd__resizer" title="Drag to resize"></div>
        <button type="button" class="rd__close" data-rd-close aria-label="Close">
            <i data-lucide="x" class="h-5 w-5"></i>
        </button>
        <div class="rd__content" id="{{ $id }}Content">
            @if(trim($slot ?? '') !== '')
                {{ $slot }}
            @else
                <div class="rd__loading">Loading…</div>
            @endif
        </div>
    </aside>
</div>

<script>
window.RightDrawer = window.RightDrawer || (function () {
    const refreshIcons = () => { if (window.lucide && window.lucide.createIcons) window.lucide.createIcons(); };

    const open = (id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('rd--open');
        el.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        refreshIcons();
    };

    const close = (id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('rd--open');
        el.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.rd.rd--open')) document.body.style.overflow = '';
    };

    const load = (id, url) => {
        const el = document.getElementById(id);
        if (!el) return;
        const content = document.getElementById(id + 'Content');
        if (content) content.innerHTML = '<div class="rd__loading">Loading…</div>';
        open(id);
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then((r) => { if (!r.ok) throw new Error(r.status); return r.text(); })
            .then((html) => { if (content) content.innerHTML = html; refreshIcons(); })
            .catch(() => { if (content) content.innerHTML = '<div class="rd__loading">Couldn\'t load this record. Please try again.</div>'; });
    };

    // Close via backdrop / close button / Escape.
    document.addEventListener('click', (e) => {
        const closer = e.target.closest('[data-rd-close]');
        if (!closer) return;
        const root = closer.closest('.rd');
        if (root) close(root.id);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') document.querySelectorAll('.rd.rd--open').forEach((el) => close(el.id));
    });

    // Drag the left-edge handle to resize.
    document.addEventListener('mousedown', (e) => {
        const handle = e.target.closest('.rd__resizer');
        if (!handle) return;
        e.preventDefault();
        const root  = handle.closest('.rd');
        const panel = handle.closest('.rd__panel');
        const min   = parseInt(root.dataset.rdMin, 10) || 360;
        const max   = parseInt(root.dataset.rdMax, 10) || 760;
        const startX = e.clientX;
        const startW = panel.offsetWidth;
        const onMove = (ev) => {
            let w = startW + (startX - ev.clientX); // drag left => wider
            w = Math.max(min, Math.min(max, w));
            panel.style.width = w + 'px';
        };
        const onUp = () => {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
            document.body.style.userSelect = '';
            try { localStorage.setItem('rdw:' + root.id, panel.style.width); } catch (err) {}
        };
        document.body.style.userSelect = 'none';
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });

    // Restore each drawer's remembered width.
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.rd').forEach((root) => {
            try {
                const w = localStorage.getItem('rdw:' + root.id);
                if (w) { const panel = root.querySelector('.rd__panel'); if (panel) panel.style.width = w; }
            } catch (err) {}
        });
    });

    return { open, close, load };
})();
</script>
