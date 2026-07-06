{{-- resources/views/finance/enrollment_queue.blade.php
     Finance decision queue: gate-approved enrollments awaiting the official
     call. Payments advance them automatically; this page is the manual path
     (with or without payment), fully audited. --}}

@extends('layouts.app')

@section('content')
<div class="w-full">

    <div class="mb-4">
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">Enrollment Queue</h1>
        <p class="text-sm text-gray-500">
            Students cleared by the gate and awaiting the official enrollment decision.
            Paying the first-due invoice decides automatically; use <b>Decide</b> for
            manual calls (scholarships, promissory notes, rejections).
        </p>
    </div>

    @if(session('success'))
        <div class="mb-3 rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="mb-3 rounded-lg bg-sky-50 border border-sky-200 px-3 py-2 text-sm text-sky-700">{{ session('info') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-sm text-rose-700">{{ $errors->first() }}</div>
    @endif

    <x-table.table
        tableKey="enrollment_queue"
        :columns="$columns"
        :data="$rows"
        :hideActions="true"
        perPage="20"
        emptyMessage="No enrollments are waiting for a decision."
    />
</div>

{{-- ===== Decision modal (reusable draggable + resizable) ===== --}}
<x-modal.form id="enrollmentDecisionModal" title="Enrollment Decision" widthClass="w-full max-w-md" :hideFooter="true">
    <form id="enrollmentDecisionForm" method="POST" action="" class="space-y-4">
        @csrf
        @method('PUT')

        <p class="text-sm text-gray-600 dark:text-gray-300">
            Decision for <span id="decisionStudentName" class="font-bold"></span>
        </p>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Decision</label>
            <select name="decision" required class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600">
                <option value="officially">Officially Enrolled</option>
                <option value="provisionally">Provisionally Enrolled (documents pending)</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Note</label>
            <textarea name="note" rows="3" maxlength="500"
                      placeholder="Reason / basis for this decision (required when rejecting)"
                      class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600"></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" onclick="closeModal('enrollmentDecisionModal')"
                    class="rounded-lg border px-4 py-2 text-sm font-semibold text-gray-600 dark:text-gray-300">Cancel</button>
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">Save Decision</button>
        </div>
    </form>
</x-modal.form>

<script>
    const DECIDE_URL = @json(route('finance.enrollment-queue.decide', ['enrollment' => '__ID__']));

    function openEnrollmentDecision(id, name) {
        const form = document.getElementById('enrollmentDecisionForm');
        form.action = DECIDE_URL.replace('__ID__', id);
        form.reset();
        document.getElementById('decisionStudentName').textContent = name;
        openModal('enrollmentDecisionModal');
    }
</script>
@endsection
