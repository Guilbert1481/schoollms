@extends('layouts.superadmin')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-0 py-10">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Login Activity</h1>
            <p class="text-slate-500 text-sm">Append-only record of every sign-in attempt across the platform.</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('superadmin.logs.logins') }}" class="flex flex-wrap items-center gap-3 mb-6">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search email or IP…"
               class="bg-white border-2 border-slate-200 py-2 px-4 rounded-xl text-sm text-slate-700 outline-none focus:border-indigo-500 transition-all w-64">
        <select name="event" onchange="this.form.submit()"
                class="bg-white border-2 border-slate-200 py-2 px-4 rounded-xl font-bold text-sm text-slate-700 outline-none focus:border-indigo-500 transition-all cursor-pointer">
            <option value="">All outcomes</option>
            <option value="success" {{ $event === 'success' ? 'selected' : '' }}>Success</option>
            <option value="failed" {{ $event === 'failed' ? 'selected' : '' }}>Failed</option>
        </select>
        <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2 px-5 rounded-xl transition-all">
            Filter
        </button>
        @if($search !== '' || $event)
            <a href="{{ route('superadmin.logs.logins') }}" class="text-sm font-bold text-slate-400 hover:text-slate-600">Reset</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <div style="overflow-x:auto;">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-bold text-slate-400 uppercase border-b border-slate-100">
                        <th class="py-3 px-4">When</th>
                        <th class="py-3 px-4">Outcome</th>
                        <th class="py-3 px-4">Email</th>
                        <th class="py-3 px-4">School</th>
                        <th class="py-3 px-4">IP</th>
                        <th class="py-3 px-4">Device</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="border-b border-slate-50">
                            <td class="py-3 px-4 whitespace-nowrap text-slate-600">
                                {{ $log->created_at?->format('M d, Y h:i:s A') }}
                            </td>
                            <td class="py-3 px-4">
                                @if($log->event === 'success')
                                    <span class="inline-block text-xs font-bold px-2 py-1 rounded-lg bg-emerald-100 text-emerald-700">Success</span>
                                @else
                                    <span class="inline-block text-xs font-bold px-2 py-1 rounded-lg bg-rose-100 text-rose-700">Failed</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-700">{{ $log->email }}</td>
                            <td class="py-3 px-4 text-slate-500">{{ $log->school?->school_name ?? '—' }}</td>
                            <td class="py-3 px-4 text-slate-500">{{ $log->ip ?? '—' }}</td>
                            <td class="py-3 px-4 text-slate-400 max-w-xs truncate" title="{{ $log->user_agent }}">
                                {{ $log->user_agent ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 px-4 text-center text-slate-400 font-bold">
                                No login activity recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</div>
@endsection
