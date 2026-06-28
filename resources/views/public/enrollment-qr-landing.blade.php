@php
    $schoolName = $term->school->name ?? config('app.name');
    $termLabel  = $term->name ?? ('Term #'.$term->id);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrolment — {{ $schoolName }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-emerald-50 min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">

        <div class="p-6 bg-slate-900 text-white text-center">
            <div class="text-xs uppercase tracking-widest opacity-70">{{ $schoolName }}</div>
            <h1 class="text-xl font-black mt-1">Online Enrolment</h1>
            <p class="text-sm opacity-80 mt-1">{{ $termLabel }}</p>
        </div>

        @if (!empty($wrongRole))
            <div class="p-6 space-y-4">
                <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
                    You are signed in as
                    <strong>{{ str_replace('_',' ', auth()->user()->role) }}</strong>.
                    Only <strong>student</strong> accounts can fill out the
                    enrolment form.
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full px-4 py-2.5 bg-slate-900 text-white rounded-lg font-bold text-sm hover:bg-slate-800">
                        Sign out and continue as student
                    </button>
                </form>
            </div>
        @else
            <div class="p-6 space-y-6">

                @if ($errors->any())
                    <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                {{-- Tabs --}}
                <div class="flex border-b border-slate-200 -mt-2">
                    <button type="button" data-qr-tab="login"
                        class="flex-1 px-4 py-2.5 text-sm font-bold text-slate-600 border-b-2 border-transparent data-[active=true]:text-indigo-600 data-[active=true]:border-indigo-600"
                        data-active="true">
                        Sign in
                    </button>
                    <button type="button" data-qr-tab="register"
                        class="flex-1 px-4 py-2.5 text-sm font-bold text-slate-600 border-b-2 border-transparent data-[active=true]:text-indigo-600 data-[active=true]:border-indigo-600">
                        Create account
                    </button>
                </div>

                {{-- Login --}}
                <form method="POST" action="{{ route('public.apply.qr.login', $term->id) }}"
                      data-qr-pane="login" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Email</label>
                        <input type="email" name="email" required value="{{ old('email') }}"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Password</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <button type="submit"
                            class="w-full px-4 py-2.5 bg-slate-900 text-white rounded-lg font-bold text-sm hover:bg-slate-800">
                        Sign in &amp; continue
                    </button>
                </form>

                {{-- Register --}}
                <form method="POST" action="{{ route('public.apply.qr.register', $term->id) }}"
                      data-qr-pane="register" class="space-y-4 hidden">
                    @csrf
                    <p class="text-xs text-slate-500">
                        Don't have an account? Create one in seconds — you'll be
                        signed in automatically and taken straight to the
                        enrolment form.
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">First Name</label>
                            <input type="text" name="first_name" required value="{{ old('first_name') }}"
                                   class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Last Name</label>
                            <input type="text" name="last_name" required value="{{ old('last_name') }}"
                                   class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Middle Name <span class="text-slate-400 normal-case">(optional)</span></label>
                        <input type="text" name="middle_name" value="{{ old('middle_name') }}"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Email</label>
                        <input type="email" name="email" required value="{{ old('email') }}"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Password</label>
                        <input type="password" name="password" required minlength="8"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                        <p class="text-[11px] text-slate-400 mt-1">Minimum 8 characters.</p>
                    </div>
                    <button type="submit"
                            class="w-full px-4 py-2.5 bg-indigo-600 text-white rounded-lg font-bold text-sm hover:bg-indigo-700">
                        Sign up &amp; continue
                    </button>
                </form>
            </div>
        @endif
    </div>

    <script>
    (function () {
        const tabs  = document.querySelectorAll('[data-qr-tab]');
        const panes = document.querySelectorAll('[data-qr-pane]');
        tabs.forEach(btn => btn.addEventListener('click', () => {
            const t = btn.dataset.qrTab;
            tabs.forEach(b => b.dataset.active = (b === btn) ? 'true' : 'false');
            panes.forEach(p => p.classList.toggle('hidden', p.dataset.qrPane !== t));
        }));
    })();
    </script>
</body>
</html>
