@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    @include('school.settings.partials.master-data._header')

    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.master_data')
    ])

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @if(session('success'))
            <p class="mt-4 rounded-lg bg-emerald-100 px-3 py-2 text-sm text-emerald-800">{{ session('success') }}</p>
        @endif

        <x-modal.form id="extraCreditPolicyModal" title="Extra Credit Policy" widthClass="w-[92vw] max-w-4xl">
            <form method="POST" action="{{ route('school.settings.master-data.events.attendance.policy.update', $event) }}" class="grid max-h-[70vh] grid-cols-1 gap-3 overflow-y-auto pr-1 md:grid-cols-2">
                @csrf
                @method('PUT')

                <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <h4 class="text-sm font-semibold text-slate-800">Certificate Eligibility Rules</h4>
                    <p class="mt-1 text-xs text-slate-500">Configure attendee certificate eligibility using minimum attendance days or custom attendee checks.</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-700">Certificate Rule Type</label>
                    <select name="certificate_rule_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="attendee_min_days" {{ (data_get($extraCreditPolicy, 'certificate_eligibility.rule_type', 'attendee_min_days') === 'attendee_min_days') ? 'selected' : '' }}>Attendee Minimum Days</option>
                        <option value="custom_mix" {{ (data_get($extraCreditPolicy, 'certificate_eligibility.rule_type', 'attendee_min_days') === 'custom_mix') ? 'selected' : '' }}>Custom Attendee Mix</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-700">Eligible Roles (CSV)</label>
                    <input type="text" name="eligible_roles" value="{{ implode(', ', data_get($extraCreditPolicy, 'certificate_eligibility.eligible_roles', ['attendee'])) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="attendee">
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-700">Minimum Attendance Days</label>
                    <input type="number" min="1" max="365" name="minimum_attendance_days" value="{{ data_get($extraCreditPolicy, 'certificate_eligibility.minimum_attendance_days') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="e.g. 2">
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-700">Custom Match Mode</label>
                    <select name="eligibility_match_mode" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="all" {{ (data_get($extraCreditPolicy, 'certificate_eligibility.eligibility_match_mode', 'all') === 'all') ? 'selected' : '' }}>ALL checks must pass</option>
                        <option value="any" {{ (data_get($extraCreditPolicy, 'certificate_eligibility.eligibility_match_mode', 'all') === 'any') ? 'selected' : '' }}>ANY check can pass</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="require_winner_tag" value="0">
                        <input type="checkbox" name="require_winner_tag" value="1" {{ data_get($extraCreditPolicy, 'certificate_eligibility.require_winner_tag', false) ? 'checked' : '' }}>
                        Require winner tag for eligibility checks
                    </label>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-700">Winner Points</label>
                    <input type="number" step="0.01" min="0" max="100" name="winner_points" value="{{ data_get($extraCreditPolicy, 'role_points.winner', 20) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-700">Participant Points</label>
                    <input type="number" step="0.01" min="0" max="100" name="participant_points" value="{{ data_get($extraCreditPolicy, 'role_points.participant', 10) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-700">Attendee Role Points</label>
                    <input type="number" step="0.01" min="0" max="100" name="attendee_points" value="{{ data_get($extraCreditPolicy, 'role_points.attendee', 0) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-700">Attendee All Days Points</label>
                    <input type="number" step="0.01" min="0" max="100" name="attendee_all_days_points" value="{{ data_get($extraCreditPolicy, 'attendance_points.all_days', 5) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-700">Attendee One+ Day Points</label>
                    <input type="number" step="0.01" min="0" max="100" name="attendee_one_day_points" value="{{ data_get($extraCreditPolicy, 'attendance_points.one_or_more_days', 3) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-700">Combination</label>
                    <select name="combination" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="max" {{ (data_get($extraCreditPolicy, 'combination', 'max') === 'max') ? 'selected' : '' }}>Use Higher Value (Max)</option>
                        <option value="add" {{ (data_get($extraCreditPolicy, 'combination', 'max') === 'add') ? 'selected' : '' }}>Add Role + Attendance</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-700">Apply Extra Credit To</label>
                    <select name="apply_to_component" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="major_exam" {{ (data_get($extraCreditPolicy, 'apply_to_component', 'major_exam') === 'major_exam') ? 'selected' : '' }}>Major Exam</option>
                        <option value="performance_task" {{ (data_get($extraCreditPolicy, 'apply_to_component', 'major_exam') === 'performance_task') ? 'selected' : '' }}>Performance Task</option>
                        <option value="both" {{ (data_get($extraCreditPolicy, 'apply_to_component', 'major_exam') === 'both') ? 'selected' : '' }}>Both Major Exam and Performance Task</option>
                        <option value="custom" {{ (data_get($extraCreditPolicy, 'apply_to_component', 'major_exam') === 'custom') ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm text-slate-700">Maximum Extra Credit (optional)</label>
                    <input type="number" step="0.01" min="0" max="100" name="max_extra_credit" value="{{ data_get($extraCreditPolicy, 'max_extra_credit') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Leave blank for no cap">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm text-slate-700">Apply-To Note (optional)</label>
                    <input type="text" name="apply_to_component_note" value="{{ data_get($extraCreditPolicy, 'apply_to_component_note', '') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Example: Use on Quarterly Exam only">
                </div>
            </form>
        </x-modal.form>

        <div class="mt-6 rounded-xl border border-slate-200 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h3 class="text-base font-semibold text-slate-900">Attendance & Computed Extra Credit Preview</h3>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('school.settings.master-data.events.index', ['mode' => 'attendance']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Back to Event Management</a>
                    <button type="button" onclick="openModal('extraCreditPolicyModal')" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Add Credit</button>
                </div>
            </div>
            <p class="mt-1 text-xs text-slate-500">
                Event: {{ $event->event_name }} |
                Apply target: {{ str_replace('_', ' ', data_get($extraCreditPolicy, 'apply_to_component', 'major_exam')) }}
                @if(filled(data_get($extraCreditPolicy, 'apply_to_component_note', '')))
                    | Note: {{ data_get($extraCreditPolicy, 'apply_to_component_note') }}
                @endif
            </p>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                <input
                    id="attendanceExtraCreditFilter"
                    type="text"
                    placeholder="Filter..."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm md:w-72"
                >
                <button
                    type="button"
                    id="attendanceExtraCreditShareFilterBtn"
                    class="rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"
                >
                    Share Filter
                </button>
            </div>
            <div class="mt-3 overflow-x-auto rounded-lg border border-slate-200">
                <table id="attendanceExtraCreditTable" class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Recipient</th>
                            <th class="px-3 py-2 text-left font-semibold">Roles</th>
                            <th class="px-3 py-2 text-left font-semibold">Attendance</th>
                            <th class="px-3 py-2 text-left font-semibold">Certificate Eligibility</th>
                            <th class="px-3 py-2 text-left font-semibold">Role Points</th>
                            <th class="px-3 py-2 text-left font-semibold">Attendance Points</th>
                            <th class="px-3 py-2 text-left font-semibold">Extra Credit</th>
                            <th class="px-3 py-2 text-left font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($extraCreditSummary as $row)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $row['recipient_name'] }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ count($row['roles']) ? implode(', ', $row['roles']) : '-' }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $row['attendance_days'] }}/{{ $row['total_days'] }}</td>
                                <td class="px-3 py-2 text-slate-700">
                                    @if($row['certificate_eligible'])
                                        <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Eligible</span>
                                    @else
                                        <button
                                            type="button"
                                            data-eligibility-reason="{{ $row['certificate_reason'] }}"
                                            data-recipient-name="{{ $row['recipient_name'] }}"
                                            class="rounded bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-200"
                                        >
                                            Not Eligible
                                        </button>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-slate-700">{{ $row['role_points'] }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $row['attendance_points'] }}</td>
                                <td class="px-3 py-2 font-semibold text-indigo-700">{{ $row['extra_credit_points'] }}</td>
                                <td class="px-3 py-2">
                                    <button
                                        type="button"
                                        data-attendance-view="1"
                                        data-recipient-id="{{ (int) $row['recipient_id'] }}"
                                        data-recipient-name="{{ $row['recipient_name'] }}"
                                        class="rounded border border-blue-300 bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                                    >
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-3 py-6 text-center text-slate-500">No recipients yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-modal.view id="certificateEligibilityReasonModal" title="Certificate Eligibility Reason">
            <p id="certificateEligibilityRecipient" class="mb-2 text-sm font-semibold text-slate-700"></p>
            <p id="certificateEligibilityReasonText" class="text-sm text-slate-600"></p>
        </x-modal.view>

        <x-modal.view id="recipientAttendanceHistoryModal" title="Recipient Attendance History">
            <p id="recipientAttendanceHistoryTitle" class="mb-3 text-sm text-slate-600"></p>

            <div class="max-h-[50vh] overflow-y-auto overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Dates</th>
                            <th class="px-3 py-2 text-left font-semibold">Time In</th>
                            <th class="px-3 py-2 text-left font-semibold">Time Out</th>
                            <th class="px-3 py-2 text-left font-semibold">Status</th>
                            <th class="px-3 py-2 text-left font-semibold">Reason</th>
                        </tr>
                    </thead>
                    <tbody id="recipientAttendanceHistoryBody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </x-modal.view>
    </div>
</div>

<script>
(function () {
    const attendanceHistoryByRecipient = @json($recipientAttendanceHistory ?? []);
    const historyBody = document.getElementById('recipientAttendanceHistoryBody');
    const historyTitle = document.getElementById('recipientAttendanceHistoryTitle');
    const historyModal = document.getElementById('recipientAttendanceHistoryModal');
    const eligibilityReasonModal = document.getElementById('certificateEligibilityReasonModal');
    const eligibilityRecipient = document.getElementById('certificateEligibilityRecipient');
    const eligibilityReasonText = document.getElementById('certificateEligibilityReasonText');
    const extraCreditTable = document.getElementById('attendanceExtraCreditTable');
    const extraCreditFilterInput = document.getElementById('attendanceExtraCreditFilter');
    const extraCreditShareFilterBtn = document.getElementById('attendanceExtraCreditShareFilterBtn');

    if (!historyBody || !historyTitle || !historyModal) {
        return;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    let extraCreditPagination = null;
    if (extraCreditTable && typeof TablePagination === 'function') {
        extraCreditPagination = new TablePagination('attendanceExtraCreditTable', 10);
    }

    function applyExtraCreditFilter(rawValue, syncUrl) {
        const value = String(rawValue || '').toLowerCase();

        if (extraCreditPagination) {
            extraCreditPagination.filter(function (row) {
                return row.innerText.toLowerCase().includes(value);
            });
        } else if (extraCreditTable) {
            const rows = Array.from(extraCreditTable.querySelectorAll('tbody tr'));
            rows.forEach(function (row) {
                row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
            });
        }

        if (syncUrl) {
            const url = new URL(window.location.href);
            if (value) {
                url.searchParams.set('ec_filter', value);
            } else {
                url.searchParams.delete('ec_filter');
            }
            window.history.replaceState({}, '', url.toString());
        }
    }

    if (extraCreditFilterInput) {
        const initialFilter = new URL(window.location.href).searchParams.get('ec_filter') || '';
        extraCreditFilterInput.value = initialFilter;
        applyExtraCreditFilter(initialFilter, false);

        extraCreditFilterInput.addEventListener('input', function () {
            applyExtraCreditFilter(extraCreditFilterInput.value, true);
        });
    }

    if (extraCreditShareFilterBtn) {
        extraCreditShareFilterBtn.addEventListener('click', async function () {
            const shareUrl = window.location.href;
            try {
                await navigator.clipboard.writeText(shareUrl);
                if (typeof showInfo === 'function') {
                    showInfo('Share link copied.');
                }
            } catch (error) {
                // No-op fallback for blocked clipboard writes.
            }
        });
    }

    function openRecipientAttendanceModal(recipientId, recipientName) {
        const stringKey = String(recipientId);
        const directRows = attendanceHistoryByRecipient[stringKey] ?? attendanceHistoryByRecipient[recipientId];
        const rows = Array.isArray(directRows) ? directRows : [];

        historyTitle.textContent = 'Recipient: ' + (recipientName || 'Unknown');

        if (rows.length === 0) {
            historyBody.innerHTML = '<tr><td colspan="5" class="px-3 py-5 text-center text-slate-500">No attendance history yet for this recipient.</td></tr>';
        } else {
            historyBody.innerHTML = rows.map(function (row) {
                return '' +
                    '<tr>' +
                        '<td class="px-3 py-2 text-slate-700">' + escapeHtml(row.date || '-') + '</td>' +
                        '<td class="px-3 py-2 text-slate-700">' + escapeHtml(row.time_in || '-') + '</td>' +
                        '<td class="px-3 py-2 text-slate-700">' + escapeHtml(row.time_out || '-') + '</td>' +
                        '<td class="px-3 py-2 text-slate-700">' + escapeHtml(row.status || '-') + '</td>' +
                        '<td class="px-3 py-2 text-slate-700">' + escapeHtml(row.reason || '-') + '</td>' +
                    '</tr>';
            }).join('');
        }

        if (typeof openModal === 'function') {
            openModal('recipientAttendanceHistoryModal');
        } else {
            historyModal.classList.remove('hidden');
            historyModal.classList.add('active');
        }
    }

    window.openRecipientAttendanceModal = openRecipientAttendanceModal;

    document.querySelectorAll('[data-attendance-view]').forEach(function (button) {
        button.addEventListener('click', function () {
            const recipientId = Number(button.getAttribute('data-recipient-id') || 0);
            const recipientName = button.getAttribute('data-recipient-name') || '';
            openRecipientAttendanceModal(recipientId, recipientName);
        });
    });

    document.querySelectorAll('[data-eligibility-reason]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!eligibilityReasonModal || !eligibilityRecipient || !eligibilityReasonText) {
                return;
            }

            const recipientName = button.getAttribute('data-recipient-name') || 'Unknown Recipient';
            const reason = button.getAttribute('data-eligibility-reason') || 'No eligibility reason available.';

            eligibilityRecipient.textContent = 'Recipient: ' + recipientName;
            eligibilityReasonText.textContent = reason;

            if (typeof openModal === 'function') {
                openModal('certificateEligibilityReasonModal');
            } else {
                eligibilityReasonModal.classList.remove('hidden');
                eligibilityReasonModal.classList.add('active');
            }
        });
    });
})();
</script>
@endsection
