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
            <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-6">Admissions</h1>
            <p class="text-slate-700 mb-8">Application season is now open. Submit requirements, schedule assessments, and track your admission status online.</p>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="rounded-xl border border-slate-200 p-5"><h3 class="font-semibold text-slate-900 mb-2">1. Apply Online</h3><p class="text-slate-700 text-sm">Complete the online application and upload initial requirements.</p></div>
                <div class="rounded-xl border border-slate-200 p-5"><h3 class="font-semibold text-slate-900 mb-2">2. Assessment</h3><p class="text-slate-700 text-sm">Take entrance evaluation and interview on your scheduled date.</p></div>
                <div class="rounded-xl border border-slate-200 p-5"><h3 class="font-semibold text-slate-900 mb-2">3. Enroll</h3><p class="text-slate-700 text-sm">Confirm slot, settle fees, and complete registration.</p></div>
            </div>
            <div class="mt-8">
                <a href="{{ route('website.home', ['schoolSlug' => $schoolSlug]) }}" class="inline-flex px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-500">Back to Home</a>
            </div>
        </section>
    </main>
</body>
</html>

