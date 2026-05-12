@php
    $signupErrors = $errors->getBag('signup');
    $loginErrors = $errors->getBag('login');
@endphp

<div id="enrolModal" class="hidden fixed inset-0 z-[110] bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="mx-auto mt-6 w-full max-w-md rounded-2xl border border-slate-200 bg-[#f3f4f6] shadow-2xl">
        <div class="px-6 pt-5">
            <div class="flex justify-end">
                <button id="closeEnrolModal" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 text-slate-500 hover:bg-slate-200">&times;</button>
            </div>

            <h2 id="enrolFormTitle" class="text-4xl font-extrabold tracking-tight text-slate-900">Login</h2>
        </div>

        <div class="p-6 pt-4">
            <form id="enrolLoginForm" method="POST" action="{{ route('website.enrol.login', ['schoolSlug' => $schoolSlug]) }}" class="space-y-4">
                @csrf

                @if ($loginErrors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                        {{ $loginErrors->first() }}
                    </div>
                @endif

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-600">Email address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-600">Password</label>
                    <input type="password" name="password" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Keep me logged in
                </label>
                <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-3 text-base font-semibold text-white hover:bg-blue-500">Log in</button>

                <p class="text-center text-sm text-slate-600">
                    Don't have an account?
                    <button type="button" class="switch-enrol-tab font-semibold text-slate-900 underline-offset-2 hover:underline" data-target="signup">Sign up now.</button>
                </p>
            </form>

            <form id="enrolSignupForm" method="POST" action="{{ route('website.enrol.signup', ['schoolSlug' => $schoolSlug]) }}" class="hidden space-y-4">
                @csrf

                @if ($signupErrors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                        {{ $signupErrors->first() }}
                    </div>
                @endif

                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-600">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-600">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-600">Middle Name (Optional)</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-600">Contact Number (Optional)</label>
                    <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-600">Email address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-600">Password</label>
                        <input type="password" name="password" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-600">Confirm Password</label>
                        <input type="password" name="password_confirmation" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    </div>
                </div>
                <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-3 text-base font-semibold text-white hover:bg-blue-500">Create account</button>

                <p class="text-center text-sm text-slate-600">
                    Already have an account?
                    <button type="button" class="switch-enrol-tab font-semibold text-slate-900 underline-offset-2 hover:underline" data-target="login">Log in now.</button>
                </p>
            </form>
        </div>
    </div>
</div>
