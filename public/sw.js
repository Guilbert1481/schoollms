/**
 * Minimal service worker to make the portal installable (PWA).
 *
 * Deliberately NETWORK-ONLY — it does not cache pages. This app is dynamic and
 * the OMR scanner must always run the latest code, so we never serve a stale
 * shell; the SW exists only to satisfy the installability criteria.
 */
self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request).catch(() =>
            new Response('You appear to be offline.', {
                status: 503,
                headers: { 'Content-Type': 'text/plain' },
            })
        )
    );
});
