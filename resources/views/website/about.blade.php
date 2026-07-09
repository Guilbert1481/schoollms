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
        <section class="max-w-6xl mx-auto glass rounded-3xl p-8 md:p-12">
            <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-6">About {{ $schoolName }}</h1>
            <p class="text-slate-700 text-lg leading-relaxed mb-6">{{ $schoolMotto ?: 'A future-forward institution committed to strong values, innovation, and learner-centered education.' }}</p>
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <h2 class="text-blue-400 text-sm uppercase tracking-wider mb-3">Mission</h2>
                    <p class="text-slate-700 leading-relaxed">{{ $missionStatement }}</p>
                </div>
                <div>
                    <h2 class="text-indigo-300 text-sm uppercase tracking-wider mb-3">Vision</h2>
                    <p class="text-slate-700 leading-relaxed">{{ $visionStatement }}</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>

