@extends('layouts.app')

@section('page-title', 'Profile Settings')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <div class="p-3 bg-indigo-50 rounded-2xl text-indigo-600">
            <i data-lucide="settings" class="w-6 h-6"></i>
        </div>
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Profile Settings</h1>
            <p class="text-sm text-slate-500">Manage your account, security and (for admins) school-wide email &amp; SMS gateways.</p>
        </div>
    </div>

    {{-- Flash --}}
    @if (session('status'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="flex flex-wrap border-b border-slate-200 bg-slate-50" id="profile-tabs">
            <button type="button" data-tab="account"
                class="px-5 py-3 text-sm font-bold text-slate-600 hover:text-indigo-600 border-b-2 border-transparent data-[active=true]:text-indigo-600 data-[active=true]:border-indigo-600 data-[active=true]:bg-white"
                data-active="true">
                <i data-lucide="user" class="w-4 h-4 inline -mt-0.5 mr-1"></i> Account
            </button>
            <button type="button" data-tab="security"
                class="px-5 py-3 text-sm font-bold text-slate-600 hover:text-indigo-600 border-b-2 border-transparent data-[active=true]:text-indigo-600 data-[active=true]:border-indigo-600 data-[active=true]:bg-white">
                <i data-lucide="lock" class="w-4 h-4 inline -mt-0.5 mr-1"></i> Security
            </button>
            @if ($isAdmin)
                <button type="button" data-tab="email"
                    class="px-5 py-3 text-sm font-bold text-slate-600 hover:text-indigo-600 border-b-2 border-transparent data-[active=true]:text-indigo-600 data-[active=true]:border-indigo-600 data-[active=true]:bg-white">
                    <i data-lucide="mail" class="w-4 h-4 inline -mt-0.5 mr-1"></i> Email / SMTP
                </button>
                <button type="button" data-tab="sms"
                    class="px-5 py-3 text-sm font-bold text-slate-600 hover:text-indigo-600 border-b-2 border-transparent data-[active=true]:text-indigo-600 data-[active=true]:border-indigo-600 data-[active=true]:bg-white">
                    <i data-lucide="message-square" class="w-4 h-4 inline -mt-0.5 mr-1"></i> SMS Gateway
                </button>
            @endif
        </div>

        {{-- =============================== ACCOUNT =============================== --}}
        <section data-pane="account" class="p-6 md:p-8">
            <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                {{-- Photo --}}
                <div class="flex items-center gap-5">
                    @php $photo = Auth::user()->profile_photo; @endphp
                    <div class="w-20 h-20 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden">
                        @if ($photo)
                            <img src="{{ asset('storage/'.$photo) }}" class="w-full h-full object-cover">
                        @else
                            <i data-lucide="user" class="w-8 h-8 text-slate-400"></i>
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Profile Photo</label>
                        <input type="file" name="profile_photo" accept="image/*"
                            class="text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="text-xs text-slate-400 mt-1">PNG / JPG, max 5MB.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">First Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Middle Name</label>
                        <input type="text" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Last Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Phone / Mobile Number</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                            placeholder="+63 9xx xxx xxxx"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <div class="text-xs text-slate-400">Role:
                        <span class="font-bold text-slate-600 uppercase">{{ str_replace('_',' ', $user->role) }}</span>
                    </div>
                    <div class="ml-auto">
                        <button type="submit"
                            class="px-6 py-2.5 bg-slate-900 text-white rounded-lg font-bold text-sm hover:bg-slate-800 shadow-sm">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </section>

        {{-- =============================== SECURITY =============================== --}}
        <section data-pane="security" class="p-6 md:p-8 hidden">
            <form method="POST" action="{{ route('settings.profile.password') }}" class="space-y-5 max-w-xl">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Current Password</label>
                    <input type="password" name="current_password" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">New Password</label>
                    <input type="password" name="password" required minlength="8"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Confirm New Password</label>
                    <input type="password" name="password_confirmation" required minlength="8"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <button type="submit"
                    class="px-6 py-2.5 bg-slate-900 text-white rounded-lg font-bold text-sm hover:bg-slate-800 shadow-sm">
                    Update Password
                </button>
            </form>
        </section>

        @if ($isAdmin)
        {{-- =============================== EMAIL / SMTP =============================== --}}
        <section data-pane="email" class="p-6 md:p-8 hidden">
            <div class="mb-5 p-4 rounded-xl bg-indigo-50 border border-indigo-100 text-sm text-indigo-800">
                <strong>Outgoing email (SMTP)</strong> — these credentials are used when the system emails
                parents and guardians (e.g. enrolment confirmations, billing notices). Leave the password
                field empty to keep the previously-saved password.
            </div>

            {{-- Currently saved in DB --}}
            <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50/60">
                <div class="px-4 py-2.5 border-b border-slate-200 flex items-center justify-between">
                    <div class="text-xs font-extrabold uppercase tracking-widest text-slate-500">
                        Currently saved in database
                    </div>
                    @if ($settings && $settings->updated_at)
                        <div class="text-[11px] text-slate-400">
                            Last updated {{ $settings->updated_at->format('M d, Y h:i A') }}
                        </div>
                    @endif
                </div>
                @php
                    $hasSmtp = $settings && ($settings->smtp_host || $settings->smtp_username || $settings->smtp_from_address);
                @endphp
                @if (! $hasSmtp)
                    <div class="px-4 py-3 text-sm text-rose-600">
                        <i data-lucide="alert-triangle" class="w-4 h-4 inline -mt-0.5 mr-1"></i>
                        No SMTP settings have been saved yet. Fill in the form below and click <strong>Save Email Settings</strong> first.
                    </div>
                @else
                    <dl class="px-4 py-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-2 text-xs">
                        <div><dt class="text-slate-500 font-semibold">SMTP Host</dt>
                             <dd class="text-slate-800 font-mono">{{ $settings->smtp_host ?: '—' }}</dd></div>
                        <div><dt class="text-slate-500 font-semibold">Port</dt>
                             <dd class="text-slate-800 font-mono">{{ $settings->smtp_port ?: '—' }}</dd></div>
                        <div><dt class="text-slate-500 font-semibold">Encryption</dt>
                             <dd class="text-slate-800 font-mono">{{ strtoupper($settings->smtp_encryption ?: 'none') }}</dd></div>
                        <div><dt class="text-slate-500 font-semibold">Username</dt>
                             <dd class="text-slate-800 font-mono break-all">{{ $settings->smtp_username ?: '—' }}</dd></div>
                        <div><dt class="text-slate-500 font-semibold">Password</dt>
                             <dd class="text-slate-800 font-mono">
                                {{ $settings->smtp_password ? str_repeat('•', 12).' (set)' : 'not set' }}
                             </dd></div>
                        <div><dt class="text-slate-500 font-semibold">From Address</dt>
                             <dd class="text-slate-800 font-mono break-all">{{ $settings->smtp_from_address ?: '—' }}</dd></div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <dt class="text-slate-500 font-semibold">From Name</dt>
                            <dd class="text-slate-800 font-mono">{{ $settings->smtp_from_name ?: '—' }}</dd>
                        </div>
                    </dl>
                    <div class="px-4 pb-3 text-[11px] text-slate-500">
                        <i data-lucide="info" class="w-3.5 h-3.5 inline -mt-0.5 mr-1"></i>
                        Note: <strong>Send Test</strong> uses these saved values — not what you type in the form. Click <strong>Save Email Settings</strong> first whenever you change something.
                    </div>
                @endif
            </div>

            <form method="POST" action="{{ route('settings.profile.smtp') }}" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">SMTP Host</label>
                        <input type="text" name="smtp_host" value="{{ old('smtp_host', $settings->smtp_host ?? '') }}"
                            placeholder="smtp.gmail.com"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Port</label>
                        <input type="number" name="smtp_port" value="{{ old('smtp_port', $settings->smtp_port ?? 587) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Username</label>
                        <input type="text" name="smtp_username" value="{{ old('smtp_username', $settings->smtp_username ?? '') }}"
                            autocomplete="off"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Password</label>
                        <input type="password" name="smtp_password" value="" autocomplete="new-password"
                            placeholder="{{ $settings?->smtp_password ? '•••••••• (unchanged)' : 'App password' }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Encryption</label>
                        @php $enc = old('smtp_encryption', $settings->smtp_encryption ?? 'tls'); @endphp
                        <select name="smtp_encryption" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="tls"  @selected($enc === 'tls')>TLS</option>
                            <option value="ssl"  @selected($enc === 'ssl')>SSL</option>
                            <option value="none" @selected(!$enc || $enc === 'none')>None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">From Address</label>
                        <input type="email" name="smtp_from_address" value="{{ old('smtp_from_address', $settings->smtp_from_address ?? '') }}"
                            placeholder="noreply@school.edu.ph"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">From Name</label>
                        <input type="text" name="smtp_from_name" value="{{ old('smtp_from_name', $settings->smtp_from_name ?? '') }}"
                            placeholder="School Registrar"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                        class="px-6 py-2.5 bg-slate-900 text-white rounded-lg font-bold text-sm hover:bg-slate-800 shadow-sm">
                        Save Email Settings
                    </button>
                </div>
            </form>

            {{-- Test email --}}
            <div class="mt-8 pt-6 border-t border-slate-200">
                <h3 class="font-bold text-slate-800 text-sm mb-3">Send Test Email</h3>
                <form method="POST" action="{{ route('settings.profile.smtp.test') }}" class="flex flex-col md:flex-row gap-3 max-w-2xl">
                    @csrf
                    <input type="email" name="to" required placeholder="you@example.com"
                        value="{{ old('to', $settings->smtp_from_address ?? '') }}"
                        class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-bold text-sm hover:bg-indigo-700 shadow-sm whitespace-nowrap">
                        <i data-lucide="send" class="w-4 h-4 inline -mt-0.5 mr-1"></i> Send Test
                    </button>
                </form>
            </div>
        </section>

        {{-- =============================== SMS =============================== --}}
        <section data-pane="sms" class="p-6 md:p-8 hidden">
            <div class="mb-5 p-4 rounded-xl bg-indigo-50 border border-indigo-100 text-sm text-indigo-800">
                <strong>SMS gateway</strong> — credentials used to send text messages to students and parents
                (e.g. urgent announcements, attendance alerts). Supported providers: Semaphore, Movider,
                iTexMo, Twilio, Vonage, or any custom HTTP gateway.
            </div>

            <form method="POST" action="{{ route('settings.profile.sms') }}" class="space-y-5">
                @csrf

                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="sms_enabled" value="0">
                    <input type="checkbox" name="sms_enabled" value="1"
                        @checked(old('sms_enabled', $settings->sms_enabled ?? false))
                        class="w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-bold text-slate-700">Enable SMS notifications</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Provider</label>
                        @php $prov = old('sms_provider', $settings->sms_provider ?? ''); @endphp
                        <select name="sms_provider" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">— Select provider —</option>
                            @foreach (['semaphore'=>'Semaphore (PH)','movider'=>'Movider','itexmo'=>'iTexMo (PH)','twilio'=>'Twilio','vonage'=>'Vonage / Nexmo','custom'=>'Custom HTTP gateway'] as $val=>$label)
                                <option value="{{ $val }}" @selected($prov === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Sender Name / ID</label>
                        <input type="text" name="sms_sender_name" value="{{ old('sms_sender_name', $settings->sms_sender_name ?? '') }}"
                            placeholder="SCHOOL-LMS"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">API Key</label>
                        <input type="password" name="sms_api_key" value="" autocomplete="off"
                            placeholder="{{ $settings?->sms_api_key ? '•••••••• (unchanged)' : 'Provider API key' }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">API Secret <span class="text-slate-400 normal-case">(if needed)</span></label>
                        <input type="password" name="sms_api_secret" value="" autocomplete="off"
                            placeholder="{{ $settings?->sms_api_secret ? '•••••••• (unchanged)' : '' }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">From Number <span class="text-slate-400 normal-case">(Twilio/Vonage)</span></label>
                        <input type="text" name="sms_from_number" value="{{ old('sms_from_number', $settings->sms_from_number ?? '') }}"
                            placeholder="+1234567890"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="px-6 py-2.5 bg-slate-900 text-white rounded-lg font-bold text-sm hover:bg-slate-800 shadow-sm">
                        Save SMS Settings
                    </button>
                </div>
            </form>
        </section>
        @endif
    </div>
</div>

<script>
(function () {
    const root = document.getElementById('profile-tabs');
    if (!root) return;
    const buttons = root.querySelectorAll('[data-tab]');
    const panes   = document.querySelectorAll('[data-pane]');

    function show(tab) {
        buttons.forEach(b => b.setAttribute('data-active', b.dataset.tab === tab ? 'true' : 'false'));
        panes.forEach(p => p.classList.toggle('hidden', p.dataset.pane !== tab));
        try { history.replaceState(null, '', '#' + tab); } catch (_) {}
    }

    buttons.forEach(b => b.addEventListener('click', () => show(b.dataset.tab)));

    // Honor URL hash on load (e.g. #email).
    const initial = (location.hash || '').replace('#','') || 'account';
    if ([...buttons].some(b => b.dataset.tab === initial)) show(initial);

    if (window.lucide) lucide.createIcons();
})();
</script>
@endsection
