<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Academic Dashboard')</title>

    <!-- Tailwind (compiled via Vite) -->
    @vite(['resources/css/app.css'])

    <!-- Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body class="bg-slate-100">

<!-- ========================= -->
<!-- SIDEBAR -->
<!-- ========================= -->
<aside class="fixed inset-y-0 left-0 w-72 bg-gradient-to-b from-slate-900 to-slate-800 text-slate-300 z-40">
    @include('admin.academics.partials.sidebar')
</aside>


<!-- ========================= -->
<!-- HEADER -->
<!-- ========================= -->
<header class="fixed top-0 left-72 right-0 h-16 bg-white border-b border-slate-200 z-30">
    <div class="h-full px-8 flex items-center justify-between">
        @include('admin.academics.partials.header', [
            'title' => 'Dashboard',
            'subtitle' => 'Academic Management System'
        ])
    </div>
</header>


<!-- ========================= -->
<!-- CONTENT WRAPPER -->
<!-- ========================= -->
<div class="ml-72 pt-16">

    <main class="p-8 min-h-screen">
        @yield('content')
    </main>

</div>


<script>
    lucide.createIcons();
</script>

<script src="https://unpkg.com/lucide@latest"></script>


</body>
</html>
