<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $schoolName }} | {{ $pageTitle }}</title>
    @vite(['resources/css/app.css'])
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
            <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-8">School Blog</h1>
            <div class="grid md:grid-cols-3 gap-6">
                <article class="glass rounded-2xl p-6"><p class="text-xs uppercase tracking-wide text-blue-300 mb-3">Campus Life</p><h3 class="text-slate-900 font-semibold mb-2">Inside the New Innovation Lab</h3><p class="text-slate-700 text-sm">A walkthrough of our upgraded lab spaces for project-based learning.</p></article>
                <article class="glass rounded-2xl p-6"><p class="text-xs uppercase tracking-wide text-blue-300 mb-3">Academics</p><h3 class="text-slate-900 font-semibold mb-2">Curriculum Updates for SY 2026</h3><p class="text-slate-700 text-sm">What students and parents can expect this incoming school year.</p></article>
                <article class="glass rounded-2xl p-6"><p class="text-xs uppercase tracking-wide text-blue-300 mb-3">Community</p><h3 class="text-slate-900 font-semibold mb-2">Scholarship Program Highlights</h3><p class="text-slate-700 text-sm">Celebrating student achievements and partner support.</p></article>
            </div>
        </section>
    </main>
</body>
</html>

