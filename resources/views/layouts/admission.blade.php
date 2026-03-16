
<!DOCTYPE html>
<html lang="en"
      x-data="{ dark: false }"
      :class="dark ? 'dark' : ''">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tenant->name ?? 'Admissions System' }}</title>

    {{-- Tailwind --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Alpine --}}
    <script src="//unpkg.com/alpinejs" defer></script>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Lucide Icons --}}
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: "{{ $tenant->primary_color ?? '#6366f1' }}"
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-800 transition-all duration-300">

<div class="flex h-screen overflow-hidden">

    {{-- Sidebar --}}
    <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col flex-shrink-0">
        @include('staff.admissions.partials.sidebar')
    </aside>

    {{-- Right Side --}}
    <div class="flex-1 flex flex-col">

        {{-- Header --}}
        <header class="h-16 bg-white dark:bg-slate-900 shadow-sm flex items-center justify-between px-6">
            @include('staff.admissions.partials.header')
        </header>

        {{-- Main Content --}}
        <main class="flex-1 overflow-y-auto p-8">
            @yield('content')
        </main>

        {{-- Footer --}}
        @include('staff.admissions.partials.footer')

    </div>

</div>

{{-- Count Animation --}}
<script>
document.querySelectorAll('.count').forEach(counter => {
    let target = +counter.dataset.target;
    let count = 0;
    let increment = target / 100;

    function update() {
        count += increment;
        if (count < target) {
            counter.innerText = Math.floor(count);
            requestAnimationFrame(update);
        } else {
            counter.innerText = target.toLocaleString();
        }
    }
    update();
});
</script>

{{-- Initialize Lucide --}}
<script>
    document.addEventListener("DOMContentLoaded", function () {
        lucide.createIcons();
    });
</script>

<script src="{{ asset('js/admission/search.js') }}"></script>


</body>
</html>
