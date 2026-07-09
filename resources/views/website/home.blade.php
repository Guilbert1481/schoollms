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
        .glass {
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(148, 163, 184, 0.22);
        }
        .bg-executive {
            background: radial-gradient(circle at top left, #f8fbff 0%, #eef4ff 55%, #eaf0ff 100%);
        }
    </style>
</head>
<body class="bg-executive text-slate-800 min-h-screen">

    @if(session('warning'))
        <div id="schoolAlertModal"
             class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-[fadeIn_0.2s_ease-out]">
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white text-2xl">!</div>
                    <h3 class="text-white font-bold text-lg">Account Notice</h3>
                </div>
                <div class="px-6 py-6">
                    <p class="text-slate-700 leading-relaxed">{{ session('warning') }}</p>
                    <p class="text-xs text-slate-400 mt-3">
                        You have been automatically signed out. If you believe this is a mistake, please contact the platform administrator.
                    </p>
                </div>
                <div class="px-6 py-4 bg-slate-50 flex justify-end">
                    <button type="button"
                            onclick="document.getElementById('schoolAlertModal').remove()"
                            class="px-5 py-2 rounded-lg bg-slate-800 text-white text-sm font-semibold hover:bg-slate-700">
                        Understood
                    </button>
                </div>
            </div>
        </div>
    @endif

    @include('layout.website-header', ['schoolSlug' => $schoolSlug, 'schoolName' => $schoolName, 'schoolLogo' => $schoolLogo, 'activePage' => $activePage, 'loginUrl' => $loginUrl])

    @if(!empty($schoolHero))
        <section class="relative w-full pt-[72px]">
            <div class="relative">
                <img src="{{ $schoolHero }}" alt="{{ $schoolName }} hero"
                     class="w-full h-[320px] md:h-[560px] object-cover">

                {{-- Dark gradient for readable overlay text --}}
                <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-transparent"></div>

                {{-- Overlay content --}}
                <div class="absolute inset-0 flex items-center">
                    <div class="max-w-7xl mx-auto w-full px-6 md:px-12">
                        <div class="max-w-2xl text-left">
                            <div class="inline-block px-4 py-1.5 rounded-full text-xs font-semibold tracking-widest uppercase text-white/90 mb-6 border border-white/30 backdrop-blur-sm bg-white/10">
                                Welcome to {{ $schoolName }}
                            </div>
                            <h1 class="text-4xl md:text-6xl font-bold text-white leading-tight drop-shadow-lg">
                                Empowering the Next <br/>
                                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-300 to-indigo-200">Generation of Leaders</span>
                            </h1>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="relative pt-12 pb-16 px-6 overflow-hidden">
        <div class="max-w-5xl mx-auto text-center relative z-10">
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('website.admissions', ['schoolSlug' => $schoolSlug]) }}" class="px-8 py-4 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-500 transition-all shadow-xl shadow-blue-600/20">Apply Now</a>
                <a href="{{ route('website.about', ['schoolSlug' => $schoolSlug]) }}" class="px-8 py-4 glass rounded-xl font-bold hover:bg-slate-100 transition-all">Visit Campus</a>
            </div>
        </div>

        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-blue-600/10 blur-[120px] rounded-full -z-10"></div>
    </section>

    <section class="py-10 px-6">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="glass p-6 rounded-2xl flex items-center gap-5">
                <div class="p-3 bg-blue-500/10 rounded-lg text-blue-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <h4 class="text-slate-900 font-semibold">Active Enrollment</h4>
                    <p class="text-xs text-slate-400">AY 2026-2027 Open</p>
                </div>
            </div>

            <div class="glass p-6 rounded-2xl flex items-center gap-5">
                <div class="p-3 bg-green-500/10 rounded-lg text-green-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
                </div>
                <div>
                    <h4 class="text-slate-900 font-semibold">School Calendar</h4>
                    <p class="text-xs text-slate-400">Exam Week: Oct 12</p>
                </div>
            </div>

            <div class="glass p-6 rounded-2xl flex items-center gap-5">
                <div class="p-3 bg-purple-500/10 rounded-lg text-purple-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                </div>
                <div>
                    <h4 class="text-slate-900 font-semibold">Virtual Library</h4>
                    <p class="text-xs text-slate-400">20,000+ Resources</p>
                </div>
            </div>
        </div>
    </section>

</body>
</html>

