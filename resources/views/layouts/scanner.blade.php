<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Scanner PWA — a SEPARATE installable app from the portal. Its own manifest
         (id/scope "/scan") gives it its own home-screen icon and standalone window.
         Shares /sw.js, whose "/" scope already covers "/scan". Camera + install
         both require HTTPS (or localhost). --}}
    <link rel="manifest" href="/scanner.webmanifest">
    <meta name="theme-color" content="#0d9488">
    <link rel="apple-touch-icon" href="/icons/scanner-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Scanner">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js').catch(function () {});
            });
        }
    </script>

    <title>@yield('page-title', 'Scanner') | Sophentis</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Build-independent: the scanner shell must render even if the compiled
           CSS is stale, so its chrome is plain CSS rather than utility classes. */
        body { margin: 0; background: #f1f5f9; color: #0f172a;
               font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        .scn-bar { position: sticky; top: 0; z-index: 30; display: flex; align-items: center; gap: 12px;
                   background: #0d9488; color: #fff;
                   padding: calc(env(safe-area-inset-top, 0px) + 12px) 16px 12px; }
        .scn-bar a.scn-back { color: #fff; text-decoration: none; font-size: 22px; line-height: 1; }
        .scn-title { font-weight: 700; font-size: 16px; }
        .scn-main { padding: 16px; padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 24px); }
        .scn-install { display: none; margin: 12px 16px 0; padding: 12px 14px; border-radius: 12px;
                       background: #ecfdf5; border: 1px solid #99f6e4; color: #0f766e; font-size: 13px; }
        .scn-install button { margin-left: 8px; padding: 6px 12px; border: 0; border-radius: 8px;
                              background: #0d9488; color: #fff; font-weight: 700; font-size: 13px; }
    </style>
</head>
<body>
    <div class="scn-bar">
        @hasSection('back')
            <a class="scn-back" href="@yield('back')" aria-label="Back">&#8592;</a>
        @endif
        <span class="scn-title">@yield('page-title', 'Scanner')</span>
    </div>

    {{-- Android/Chrome install prompt. Hidden unless the browser offers it (i.e.
         already installed, or unsupported like iOS Safari — which uses Share →
         Add to Home Screen instead). --}}
    <div class="scn-install" id="scnInstall">
        Install Scanner as an app on this phone.
        <button type="button" id="scnInstallBtn">Install</button>
    </div>

    <main class="scn-main">
        @yield('content')
    </main>

    <script>
        (function () {
            var deferred = null;
            var box = document.getElementById('scnInstall');
            var btn = document.getElementById('scnInstallBtn');

            window.addEventListener('beforeinstallprompt', function (e) {
                e.preventDefault();
                deferred = e;
                if (box) box.style.display = 'block';
            });

            if (btn) {
                btn.addEventListener('click', function () {
                    if (!deferred) return;
                    deferred.prompt();
                    deferred.userChoice.finally(function () {
                        deferred = null;
                        if (box) box.style.display = 'none';
                    });
                });
            }

            window.addEventListener('appinstalled', function () {
                if (box) box.style.display = 'none';
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
