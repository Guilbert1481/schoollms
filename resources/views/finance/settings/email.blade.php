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
    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div x-data="{ tab: 'smtp' }">
        <nav class="flex gap-6 border-b border-slate-200 text-sm">
            @foreach(['smtp' => 'Sending (SMTP)', 'imap' => 'Receiving (IMAP)', 'autosend' => 'Auto-Send'] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="-mb-px border-b-2 px-1 py-3 font-semibold">{{ $label }}</button>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('finance.settings.email.update') }}" class="pt-5">
            @csrf
            @method('PUT')

            {{-- ===== SMTP tab ===== --}}
            <div x-show="tab === 'smtp'" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">SMTP Host</label>
                        <input type="text" name="smtp_host" value="{{ old('smtp_host', $setting->smtp_host) }}" placeholder="smtp.gmail.com"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Port</label>
                        <input type="number" name="smtp_port" value="{{ old('smtp_port', $setting->smtp_port) }}" placeholder="587"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                        <input type="text" name="smtp_username" value="{{ old('smtp_username', $setting->smtp_username) }}" autocomplete="off"
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
                        <select name="smtp_encryption" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="" @selected(! $setting->smtp_encryption)>None</option>
                            <option value="tls" @selected($setting->smtp_encryption === 'tls')>TLS</option>
                            <option value="ssl" @selected($setting->smtp_encryption === 'ssl')>SSL</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">From Address</label>
                        <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $setting->mail_from_address) }}" placeholder="finance@school.edu.ph"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">From Name</label>
                        <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $setting->mail_from_name) }}" placeholder="{{ $schoolName }} — Finance Office"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            {{-- ===== IMAP tab ===== --}}
            <div x-show="tab === 'imap'" x-cloak class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs text-slate-400">
                    Incoming-mail account for the finance inbox (e.g. payment confirmations). Stored for upcoming features — sending does not require it.
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">IMAP Host</label>
                        <input type="text" name="imap_host" value="{{ old('imap_host', $setting->imap_host) }}" placeholder="imap.gmail.com"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Port</label>
                        <input type="number" name="imap_port" value="{{ old('imap_port', $setting->imap_port) }}" placeholder="993"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                        <input type="text" name="imap_username" value="{{ old('imap_username', $setting->imap_username) }}" autocomplete="off"
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
                        <select name="imap_encryption" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="" @selected(! $setting->imap_encryption)>None</option>
                            <option value="ssl" @selected($setting->imap_encryption === 'ssl')>SSL</option>
                            <option value="tls" @selected($setting->imap_encryption === 'tls')>TLS</option>
                        </select>
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
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
