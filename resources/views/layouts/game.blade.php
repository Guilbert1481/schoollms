{{-- Bare fullscreen layout for embedded games (no sidebar, no header).
     Used by tools/games/play.blade.php when ?embed=1 so the game can run
     distraction-free inside the catalog's fullscreen overlay iframe. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Game')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-white antialiased">

    @yield('content')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
