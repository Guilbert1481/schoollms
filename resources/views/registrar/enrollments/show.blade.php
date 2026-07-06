@extends('layouts.app')

@section('content')
<div class="w-full">
    <a href="{{ route('registrar.enrollments.index') }}" class="text-xs text-indigo-600 hover:underline">&larr; Back to queue</a>

    <h1 class="text-xl font-extrabold mt-2 mb-4">Enrollment Review</h1>

    @if(session('success'))
        <div class="mb-3 rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border bg-white p-5 mb-4">
        <dl class="grid grid-cols-2 gap-3 text-sm">
            <div><dt class="text-slate-500">Student #</dt><dd class="font-bold">{{ optional($enrollment->student)->student_number }}</dd></div>
            <div><dt class="text-slate-500">Name</dt><dd class="font-bold">{{ optional($enrollment->student)->last_name }}, {{ optional($enrollment->student)->first_name }}</dd></div>
            <div><dt class="text-slate-500">Program</dt><dd>{{ optional($enrollment->program)->name }}</dd></div>
            <div><dt class="text-slate-500">Term</dt><dd>{{ optional($enrollment->term)->name }}</dd></div>
            <div><dt class="text-slate-500">Type</dt><dd>{{ $enrollment->enrollee_type }}</dd></div>
            <div><dt class="text-slate-500">Total Units</dt><dd>{{ $enrollment->total_units }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="font-bold">{{ ucwords(str_replace('_',' ',$enrollment->status)) }}</dd></div>
            <div><dt class="text-slate-500">Approval Level</dt><dd>{{ $enrollment->approval_level ?: '—' }}</dd></div>
        </dl>

        @if($enrollment->remarks)
            <div class="mt-3 text-sm">
                <dt class="text-slate-500">Remarks</dt>
                <dd>{{ $enrollment->remarks }}</dd>
            </div>
        @endif
    </div>

    @if($enrollment->billing_cleared_as === 'provisional')
        <form method="POST" action="{{ route('registrar.enrollments.documents-complied', $enrollment) }}"
              class="rounded-2xl border border-sky-200 bg-sky-50 p-4 mb-4">
            @csrf
            <h2 class="font-bold mb-2 text-sky-700">Documents Complied</h2>
            <p class="text-[11px] text-slate-600 mb-2">
                This applicant was cleared <b>provisionally</b> (documents pending). Mark the
                requirements as complied to lift the condition — if their downpayment is already
                settled, they are promoted to <b>officially Enrolled</b> automatically.
            </p>
            <textarea name="remarks" rows="2" placeholder="Optional note (e.g. which documents were submitted)"
                      class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
            <button class="mt-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-sm font-bold">
                Mark Documents Complied
            </button>
        </form>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <form method="POST" action="{{ route('registrar.enrollments.validate', $enrollment) }}"
              class="rounded-2xl border bg-white p-4">
            @csrf
            <h2 class="font-bold mb-2 text-emerald-700">Validate</h2>
            <p class="text-[11px] text-slate-500 mb-2">
                All documents complete. Applicant becomes <b>Assessed</b> and
                a Statement of Account is issued.
            </p>
            <textarea name="remarks" rows="3" placeholder="Optional remarks"
                      class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
            <button class="mt-2 w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-bold">
                Validate Enrollment
            </button>
        </form>

        <form method="POST" action="{{ route('registrar.enrollments.provisional', $enrollment) }}"
              class="rounded-2xl border bg-white p-4">
            @csrf
            <h2 class="font-bold mb-2 text-amber-700">Approve Provisionally</h2>
            <p class="text-[11px] text-slate-500 mb-2">
                Documents partially complete. Applicant becomes
                <b>Provisional</b>, a Statement of Account is issued and
                — once paid — they will be <b>Provisionally Enrolled</b>.
            </p>
            <textarea name="remarks" rows="3" placeholder="Pending requirements / conditions"
                      class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
            <button class="mt-2 w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-bold">
                Approve Provisionally
            </button>
        </form>

        <form method="POST" action="{{ route('registrar.enrollments.reject', $enrollment) }}"
              class="rounded-2xl border bg-white p-4">
            @csrf
            <h2 class="font-bold mb-2 text-rose-700">Reject</h2>
            <p class="text-[11px] text-slate-500 mb-2">
                Documents insufficient or applicant ineligible. Applicant
                becomes <b>Rejected</b>.
            </p>
            <textarea name="remarks" rows="3" required placeholder="Reason for rejection (required)"
                      class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
            <button class="mt-2 w-full px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-bold">
                Reject Enrollment
            </button>
        </form>
    </div>
</div>
@endsection
