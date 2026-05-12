<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $schoolName }} | {{ $pageTitle }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.78); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(148, 163, 184, 0.22); }
        .bg-executive { background: radial-gradient(circle at top left, #f8fbff 0%, #eef4ff 55%, #eaf0ff 100%); }
    </style>
</head>
<body class="bg-executive text-slate-800 min-h-screen">
    @include('layout.website-header', ['schoolSlug' => $schoolSlug, 'schoolName' => $schoolName, 'schoolLogo' => $schoolLogo, 'activePage' => $activePage, 'loginUrl' => $loginUrl])

    <main class="pt-32 px-6 pb-16">
        <section class="max-w-6xl mx-auto">
            <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-8">Programs</h1>
            <div class="grid md:grid-cols-3 gap-6">
                <article class="glass rounded-2xl p-6"><h3 class="text-slate-900 font-semibold mb-2">STEM Track</h3><p class="text-slate-700 text-sm">Hands-on science, technology, engineering, and math for future innovators.</p></article>
                <article class="glass rounded-2xl p-6"><h3 class="text-slate-900 font-semibold mb-2">Business and Entrepreneurship</h3><p class="text-slate-700 text-sm">Build business acumen, leadership, and startup readiness.</p></article>
                <article class="glass rounded-2xl p-6"><h3 class="text-slate-900 font-semibold mb-2">Arts and Media</h3><p class="text-slate-700 text-sm">Develop creativity, communication, and design thinking.</p></article>
            </div>
        </section>
    </main>
</body>
</html>

