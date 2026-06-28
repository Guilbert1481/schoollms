@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-xl font-extrabold mb-1">Billing & Payment Queue</h1>
    <p class="text-sm text-slate-500 mb-4">
        Applicants cleared by the Registrar (Assessed or Provisional) appear here
        for Statement of Account issuance and payment confirmation.
    </p>

    @if(session('success'))
        <div class="mb-3 rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    @php
        $tabs = [
            'sent_billing'           => 'Awaiting Payment',
            'enrolled'               => 'Enrolled',
            'provisionally_enrolled' => 'Provisionally Enrolled',
            'all'                    => 'All',
        ];
    @endphp
    <div class="flex gap-2 mb-3 text-xs">
        @foreach($tabs as $k => $label)
            <a href="{{ route('finance.billing.queue', ['status' => $k]) }}"
               class="px-3 py-1.5 rounded-full font-bold border
                      {{ $status === $k ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="rounded-2xl border bg-white overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-2">Student #</th>
                    <th class="text-left px-4 py-2">Name</th>
                    <th class="text-left px-4 py-2">Program</th>
                    <th class="text-left px-4 py-2">Cleared As</th>
                    <th class="text-left px-4 py-2">Status</th>
                    <th class="text-right px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($enrollments as $e)
                    <tr>
                        <td class="px-4 py-2 font-mono">{{ optional($e->student)->student_number }}</td>
                        <td class="px-4 py-2 font-bold">
                            {{ optional($e->student)->last_name }}, {{ optional($e->student)->first_name }}
                        </td>
                        <td class="px-4 py-2">{{ \App\Support\EnrollmentProgramLabel::for($e) }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold
                                {{ $e->billing_cleared_as === 'provisional' ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700' }}">
                                {{ $e->billing_cleared_as ? ucfirst($e->billing_cleared_as) : '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-700 px-2 py-0.5 text-xs font-bold">
                                {{ ucwords(str_replace('_',' ',$e->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            @if($e->status === 'sent_billing')
                                <form method="POST" action="{{ route('finance.billing.paid', $e) }}"
                                      onsubmit="return confirm('Mark this applicant as paid?')">
                                    @csrf
                                    <button class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold">
                                        Mark as Paid
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No applicants in this queue.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $enrollments->links() }}</div>
</div>
@endsection
