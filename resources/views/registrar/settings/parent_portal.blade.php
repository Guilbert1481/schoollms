{{-- Registrar → Settings → Parent Portal: auto-provisioning kill-switch,
     the school's parent↔child access links, and unresolved provisioning
     issues (no email / collisions / failed credential sends). --}}

@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-5xl">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Parent Portal</h1>
        <p class="text-sm text-slate-500 mt-1">
            Parent accounts are created automatically from the guardian email when
            you clear an enrollment. Manage that behaviour and each family's access here.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- ===== Auto-provisioning toggle ===== --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <form method="POST" action="{{ route('registrar.settings.parent-portal.update') }}"
              class="flex items-center justify-between gap-6">
            @csrf
            @method('PUT')

            <div>
                <h2 class="font-semibold text-slate-800">Automatic parent account creation</h2>
                <p class="text-sm text-slate-500 mt-1">
                    When you assess or provisionally approve an enrollment, a parent
                    portal account is created from the primary guardian's email and a
                    one-time password is sent to them. Turn this off to stop new
                    accounts from being created (existing access is not affected).
                </p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="parent_portal_auto_provision" value="1"
                           class="sr-only peer" @checked($setting->parent_portal_auto_provision)>
                    <div class="relative w-11 h-6 bg-slate-200 peer-checked:bg-indigo-600 rounded-full
                                after:content-[''] after:absolute after:top-0.5 after:start-0.5
                                after:bg-white after:rounded-full after:h-5 after:w-5
                                after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
                <button type="submit"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                    Save
                </button>
            </div>
        </form>
    </div>

    {{-- ===== Unresolved provisioning issues ===== --}}
    @if ($issues->isNotEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-6">
            <h2 class="font-semibold text-slate-800 flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500"></i>
                Needs attention ({{ $issues->count() }})
            </h2>
            <p class="text-sm text-slate-500 mt-1">
                These students could not get a parent account automatically. Fix the
                guardian record and re-clear the enrollment, or handle it manually and
                mark the issue resolved.
            </p>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-amber-200">
                            <th class="py-2 pr-4">Student</th>
                            <th class="py-2 pr-4">Issue</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2 pr-4">Details</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($issues as $issue)
                            <tr class="border-b border-amber-100 last:border-0">
                                <td class="py-2.5 pr-4 font-medium text-slate-700">
                                    {{ trim($issue->student_last_name.', '.$issue->student_first_name, ', ') }}
                                    <span class="text-xs text-slate-400 block">{{ $issue->student_number ?: '—' }}</span>
                                </td>
                                <td class="py-2.5 pr-4">
                                    <span class="inline-flex rounded-full bg-amber-100 text-amber-700 px-2.5 py-0.5 text-xs font-semibold">
                                        {{ $issueLabels[$issue->issue] ?? ucfirst(str_replace('_', ' ', $issue->issue)) }}
                                    </span>
                                </td>
                                <td class="py-2.5 pr-4 text-slate-600">{{ $issue->email ?: '—' }}</td>
                                <td class="py-2.5 pr-4 text-slate-500 max-w-xs truncate" title="{{ $issue->details }}">
                                    {{ $issue->details ?: '—' }}
                                </td>
                                <td class="py-2.5 text-right">
                                    <form method="POST"
                                          action="{{ route('registrar.settings.parent-portal.issues.resolve', $issue->id) }}">
                                        @csrf
                                        <button type="submit"
                                                class="text-xs font-semibold text-slate-500 hover:text-slate-700 underline decoration-dotted">
                                            Mark resolved
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===== Access links ===== --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <h2 class="font-semibold text-slate-800">Parent access</h2>
        <p class="text-sm text-slate-500 mt-1">
            One row per parent-and-child link for this school. Disabling a link hides
            that child from the parent; re-issuing credentials resets the parent's
            password portal-wide and emails them a new one-time password.
        </p>

        @if ($links->isEmpty())
            <div class="mt-6 rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">
                No parent accounts have been linked to this school's students yet.
                They will appear here as enrollments are cleared.
            </div>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-4">Parent</th>
                            <th class="py-2 pr-4">Child</th>
                            <th class="py-2 pr-4">Relationship</th>
                            <th class="py-2 pr-4">Credentials</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($links as $link)
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="py-2.5 pr-4">
                                    <span class="font-medium text-slate-700">
                                        {{ trim($link->parent_last_name.', '.$link->parent_first_name, ', ') }}
                                    </span>
                                    <span class="text-xs text-slate-400 block">{{ $link->parent_email }}</span>
                                </td>
                                <td class="py-2.5 pr-4 text-slate-600">
                                    {{ trim($link->student_last_name.', '.$link->student_first_name, ', ') }}
                                    <span class="text-xs text-slate-400 block">{{ $link->student_number ?: '—' }}</span>
                                </td>
                                <td class="py-2.5 pr-4 text-slate-600">
                                    {{ $link->relationship ? ucfirst($link->relationship) : '—' }}
                                </td>
                                <td class="py-2.5 pr-4">
                                    @if ($link->credentials_sent_at)
                                        <span class="text-slate-600 text-xs">
                                            Sent {{ \Illuminate\Support\Carbon::parse($link->credentials_sent_at)->format('M d, Y') }}
                                        </span>
                                        @if ($link->password_change_required)
                                            <span class="text-amber-600 text-xs block">One-time password not yet used</span>
                                        @endif
                                    @else
                                        <span class="inline-flex rounded-full bg-rose-100 text-rose-700 px-2.5 py-0.5 text-xs font-semibold">
                                            Not delivered
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 pr-4">
                                    @if ($link->access_status === 'active')
                                        <span class="inline-flex rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-0.5 text-xs font-semibold">Active</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-200 text-slate-600 px-2.5 py-0.5 text-xs font-semibold">Disabled</span>
                                    @endif
                                </td>
                                <td class="py-2.5 text-right whitespace-nowrap">
                                    <form method="POST" class="inline"
                                          action="{{ route('registrar.settings.parent-portal.links.reissue', $link->id) }}"
                                          onsubmit="return confirm('Reset this parent\'s password and email them a new one-time password? This affects their whole portal account.');">
                                        @csrf
                                        <button type="submit"
                                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 mr-3">
                                            Re-issue credentials
                                        </button>
                                    </form>
                                    <form method="POST" class="inline"
                                          action="{{ route('registrar.settings.parent-portal.links.toggle', $link->id) }}">
                                        @csrf
                                        <button type="submit"
                                                class="text-xs font-semibold {{ $link->access_status === 'active' ? 'text-rose-600 hover:text-rose-800' : 'text-emerald-600 hover:text-emerald-800' }}">
                                            {{ $link->access_status === 'active' ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
