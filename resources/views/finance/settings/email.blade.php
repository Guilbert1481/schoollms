@extends('layouts.app')

@section('content')
<div class="w-full space-y-5">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Finance Email</h1>
        <p class="text-sm text-slate-500">
            The email account used to send invoices and Statements of Account to students and guardians.
            When no SMTP is configured here, the school's system email account is used instead.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div x-data="{ tab: 'configuration' }">
        <nav class="flex gap-6 border-b border-slate-200 text-sm">
            @foreach(['configuration' => 'Configuration', 'autosend' => 'Auto-Send'] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="-mb-px border-b-2 px-1 py-3 font-semibold">{{ $label }}</button>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('finance.settings.email.update') }}" class="pt-5">
            @csrf
            @method('PUT')

            {{-- ===== Configuration tab: provider preset + SMTP + IMAP ===== --}}
            <div x-show="tab === 'configuration'" class="space-y-5">

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email Provider</label>
                    <select id="emailProvider" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm sm:w-72">
                        <option value="">Custom / manual entry</option>
                        <option value="gmail">Gmail / Google Workspace</option>
                        <option value="microsoft365">Microsoft 365 / Outlook</option>
                        <option value="godaddy">GoDaddy</option>
                        <option value="zoho">Zoho Mail</option>
                        <option value="yahoo">Yahoo Mail</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-400">
                        Selecting a provider fills the SMTP and IMAP servers, ports and encryption for you —
                        enter your own username and password.
                    </p>
                    <p id="providerHint" class="mt-2 hidden rounded-lg bg-sky-50 px-3 py-2 text-xs text-sky-700"></p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-sm font-bold text-slate-800">Sending (SMTP)</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">SMTP Host</label>
                            <input type="text" name="smtp_host" id="smtp_host" value="{{ old('smtp_host', $setting->smtp_host) }}" placeholder="smtp.gmail.com"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Port</label>
                            <input type="number" name="smtp_port" id="smtp_port" value="{{ old('smtp_port', $setting->smtp_port) }}" placeholder="587"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                            <input type="text" name="smtp_username" id="smtp_username" value="{{ old('smtp_username', $setting->smtp_username) }}" autocomplete="off"
                                   placeholder="finance@school.edu.ph"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                            <input type="password" name="smtp_password" value="" autocomplete="new-password"
                                   placeholder="{{ $setting->smtp_password ? '•••••••• (saved — leave blank to keep)' : '' }}"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Encryption</label>
                            <select name="smtp_encryption" id="smtp_encryption" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="" @selected(! $setting->smtp_encryption)>None</option>
                                <option value="tls" @selected($setting->smtp_encryption === 'tls')>TLS</option>
                                <option value="ssl" @selected($setting->smtp_encryption === 'ssl')>SSL</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">From Address</label>
                            <input type="email" name="mail_from_address" id="mail_from_address" value="{{ old('mail_from_address', $setting->mail_from_address) }}" placeholder="finance@school.edu.ph"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">From Name</label>
                            <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $setting->mail_from_name) }}" placeholder="{{ $schoolName }} — Finance Office"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-1 text-sm font-bold text-slate-800">Receiving (IMAP)</h2>
                    <p class="mb-4 text-xs text-slate-400">
                        Incoming-mail account for the finance inbox (e.g. payment confirmations). Stored for upcoming features — sending does not require it.
                    </p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">IMAP Host</label>
                            <input type="text" name="imap_host" id="imap_host" value="{{ old('imap_host', $setting->imap_host) }}" placeholder="imap.gmail.com"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Port</label>
                            <input type="number" name="imap_port" id="imap_port" value="{{ old('imap_port', $setting->imap_port) }}" placeholder="993"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                            <input type="text" name="imap_username" id="imap_username" value="{{ old('imap_username', $setting->imap_username) }}" autocomplete="off"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                            <input type="password" name="imap_password" value="" autocomplete="new-password"
                                   placeholder="{{ $setting->imap_password ? '•••••••• (saved — leave blank to keep)' : '' }}"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Encryption</label>
                            <select name="imap_encryption" id="imap_encryption" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="" @selected(! $setting->imap_encryption)>None</option>
                                <option value="ssl" @selected($setting->imap_encryption === 'ssl')>SSL</option>
                                <option value="tls" @selected($setting->imap_encryption === 'tls')>TLS</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Auto-send tab ===== --}}
            <div x-show="tab === 'autosend'" x-cloak class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <label class="flex items-start gap-3 text-sm text-slate-700">
                    <input type="hidden" name="auto_send_invoices" value="0">
                    <input type="checkbox" name="auto_send_invoices" value="1" @checked($setting->auto_send_invoices)
                           class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-200">
                    <span>
                        <span class="font-semibold">Automatically email invoices on their billing date</span><br>
                        <span class="text-xs text-slate-500">
                            Each invoice PDF is emailed once to the student and their guardians when its billing date arrives
                            (and immediately for regular students' first bill at enrollment submission).
                            Every 3rd invoice emailed to a student also includes their updated Statement of Account.
                        </span>
                    </span>
                </label>
                <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                    Invoices are only sent while this switch is on. Turning it on does not re-send invoices that were already emailed.
                </p>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save Email Settings</button>
            </div>
        </form>

        {{-- ===== Connection tests (use the last-saved settings) ===== --}}
        <div x-show="tab === 'configuration'" class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-sm font-bold text-slate-800">Connection Tests</h2>
            <p class="mb-4 text-xs text-slate-400">
                Tests run against the <b>saved</b> settings — click Save first if you changed anything.
                The test email goes to your own address ({{ auth()->user()->email }}).
            </p>
            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('finance.settings.email.test-smtp') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                        <i data-lucide="send" class="h-4 w-4"></i> Send Test Email (SMTP)
                    </button>
                </form>
                <form method="POST" action="{{ route('finance.settings.email.test-imap') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-100">
                        <i data-lucide="inbox" class="h-4 w-4"></i> Test IMAP Connection
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Provider presets: servers, ports and encryption — never credentials.
    const EMAIL_PROVIDERS = {
        gmail: {
            smtp: { host: 'smtp.gmail.com', port: 587, enc: 'tls' },
            imap: { host: 'imap.gmail.com', port: 993, enc: 'ssl' },
            hint: 'Gmail requires an App Password (Google Account → Security → 2-Step Verification → App passwords) — your normal password will be rejected.',
        },
        microsoft365: {
            smtp: { host: 'smtp.office365.com', port: 587, enc: 'tls' },
            imap: { host: 'outlook.office365.com', port: 993, enc: 'ssl' },
            hint: 'Microsoft 365 may require enabling "Authenticated SMTP" for the mailbox (admin center → user → Mail → Manage email apps).',
        },
        godaddy: {
            smtp: { host: 'smtpout.secureserver.net', port: 465, enc: 'ssl' },
            imap: { host: 'imap.secureserver.net', port: 993, enc: 'ssl' },
            hint: 'GoDaddy Workspace Email credentials are the full email address and its password.',
        },
        zoho: {
            smtp: { host: 'smtp.zoho.com', port: 587, enc: 'tls' },
            imap: { host: 'imap.zoho.com', port: 993, enc: 'ssl' },
            hint: 'Zoho accounts with 2FA need an application-specific password (Zoho Accounts → Security → App Passwords).',
        },
        yahoo: {
            smtp: { host: 'smtp.mail.yahoo.com', port: 465, enc: 'ssl' },
            imap: { host: 'imap.mail.yahoo.com', port: 993, enc: 'ssl' },
            hint: 'Yahoo requires an app password (Yahoo Account Security → Generate app password).',
        },
    };

    document.getElementById('emailProvider').addEventListener('change', function () {
        const p = EMAIL_PROVIDERS[this.value];
        const hint = document.getElementById('providerHint');
        if (! p) { hint.classList.add('hidden'); return; }

        document.getElementById('smtp_host').value = p.smtp.host;
        document.getElementById('smtp_port').value = p.smtp.port;
        document.getElementById('smtp_encryption').value = p.smtp.enc;
        document.getElementById('imap_host').value = p.imap.host;
        document.getElementById('imap_port').value = p.imap.port;
        document.getElementById('imap_encryption').value = p.imap.enc;

        hint.textContent = p.hint;
        hint.classList.remove('hidden');
    });

    // Typing the SMTP username pre-fills the IMAP username and From address
    // when they are still empty (same mailbox for most providers).
    document.getElementById('smtp_username').addEventListener('blur', function () {
        const imapUser = document.getElementById('imap_username');
        const fromAddr = document.getElementById('mail_from_address');
        if (this.value && ! imapUser.value) imapUser.value = this.value;
        if (this.value && this.value.includes('@') && ! fromAddr.value) fromAddr.value = this.value;
    });

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
