<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\CertificateEvent;
use App\Models\EventAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EventAttendanceController extends Controller
{
    public function show(Request $request, CertificateEvent $event)
    {
        abort_if((int) $event->school_id !== (int) $request->user()->school_id, 403);

        $event->load('recipients:id,event_id,recipient_name,custom_fields');

        $days = $this->buildEventDays($event);
        $selectedDate = (string) $request->query('date', $days[0] ?? now()->toDateString());
        if (!in_array($selectedDate, $days, true) && count($days) > 0) {
            $selectedDate = $days[0];
        }

        $supportsTimeTracking = $this->supportsAttendanceTimeTracking();

        $attendanceSelect = ['recipient_id', 'status'];
        if ($supportsTimeTracking) {
            $attendanceSelect[] = 'time_in_at';
            $attendanceSelect[] = 'time_out_at';
            $attendanceSelect[] = 'capture_source';
        }

        $attendanceMap = EventAttendance::query()
            ->where('event_id', $event->id)
            ->whereDate('attendance_date', $selectedDate)
            ->get($attendanceSelect)
            ->keyBy('recipient_id');

        $historySelect = ['recipient_id', 'attendance_date', 'status'];
        if ($supportsTimeTracking) {
            $historySelect[] = 'time_in_at';
            $historySelect[] = 'time_out_at';
        }

        $eventEndTime = $this->normalizeTimeText($event->end_time);
        $recipientAttendanceHistory = EventAttendance::query()
            ->where('event_id', $event->id)
            ->orderBy('attendance_date')
            ->get($historySelect)
            ->groupBy('recipient_id')
            ->map(function ($rows) use ($supportsTimeTracking, $eventEndTime) {
                return $rows->map(function (EventAttendance $row) use ($supportsTimeTracking, $eventEndTime) {
                    $status = strtolower(trim((string) ($row->status ?? '')));
                    $timeIn = $supportsTimeTracking ? $this->normalizeTimeText($row->time_in_at) : '';
                    $timeOut = $supportsTimeTracking ? $this->normalizeTimeText($row->time_out_at) : '';
                    $resolvedStatus = $this->resolveCompletionLabel($status, $timeOut, $eventEndTime);
                    $resolvedReason = $this->resolveCompletionReason($resolvedStatus, $eventEndTime);

                    return [
                        'date' => $row->attendance_date ? Carbon::parse($row->attendance_date)->toDateString() : '-',
                        'time_in' => $timeIn !== '' ? $timeIn : '-',
                        'time_out' => $timeOut !== '' ? $timeOut : '-',
                        'status' => $resolvedStatus,
                        'reason' => $resolvedReason,
                    ];
                })->values()->all();
            })
            ->toArray();

        $attendanceRows = $event->recipients->map(function ($recipient) use ($attendanceMap, $supportsTimeTracking) {
            $entry = $attendanceMap->get($recipient->id);
            $status = (string) ($entry?->status ?? '');
            $roles = $this->extractRoles((array) ($recipient->custom_fields ?? []));
            $timeIn = $supportsTimeTracking ? $this->normalizeTimeText($entry?->time_in_at) : '';
            $timeOut = $supportsTimeTracking ? $this->normalizeTimeText($entry?->time_out_at) : '';
            $source = $supportsTimeTracking ? strtolower((string) ($entry?->capture_source ?? 'manual')) : 'manual';

            $recipient->setAttribute('roles_label', count($roles) > 0 ? implode(', ', $roles) : '-');
            $recipient->setAttribute('status_value', $status);
            $recipient->setAttribute('status_label', $status !== '' ? ucfirst($status) : 'No record');
            $recipient->setAttribute('time_in_label', $timeIn !== '' ? $timeIn : '-');
            $recipient->setAttribute('time_out_label', $timeOut !== '' ? $timeOut : '-');
            $recipient->setAttribute('capture_source_label', $source !== '' ? ucfirst($source) : 'Manual');
            $recipient->setAttribute('time_in_value', $timeIn);
            $recipient->setAttribute('time_out_value', $timeOut);
            $recipient->setAttribute('capture_source_value', $source !== '' ? $source : 'manual');

            return $recipient;
        })->values();

        $policy = $this->normalizeExtraCreditPolicy($event);
        $summary = $this->buildExtraCreditSummary($event, $policy, $days);

        return view('school.settings.master-data.events.attendance', [
            'event' => $event,
            'eventDays' => $days,
            'selectedDate' => $selectedDate,
            'attendanceRows' => $attendanceRows,
            'recipientAttendanceHistory' => $recipientAttendanceHistory,
            'extraCreditPolicy' => $policy,
            'extraCreditSummary' => $summary,
        ]);
    }

    public function updateAttendance(Request $request, CertificateEvent $event)
    {
        abort_if((int) $event->school_id !== (int) $request->user()->school_id, 403);

        $days = $this->buildEventDays($event);

        $data = $request->validate([
            'attendance_date' => 'required|date',
            'status' => 'nullable|array',
            'status.*' => 'nullable|in:present,absent,late,excused',
            'time_in_at' => 'nullable|array',
            'time_in_at.*' => 'nullable|date_format:H:i',
            'time_out_at' => 'nullable|array',
            'time_out_at.*' => 'nullable|date_format:H:i',
            'capture_source' => 'nullable|array',
            'capture_source.*' => 'nullable|in:device,manual,auto',
        ]);

        $attendanceDate = (string) $data['attendance_date'];
        if (count($days) > 0 && !in_array($attendanceDate, $days, true)) {
            return back()->withErrors([
                'attendance_date' => 'Selected date is outside the event schedule.',
            ]);
        }

        $event->load('recipients:id,event_id,custom_fields');
        $validRecipientIds = $event->recipients->pluck('id')->all();
        $supportsTimeTracking = $this->supportsAttendanceTimeTracking();

        $recipientIds = collect(array_keys((array) ($data['status'] ?? [])))
            ->merge($supportsTimeTracking ? array_keys((array) ($data['time_in_at'] ?? [])) : [])
            ->merge($supportsTimeTracking ? array_keys((array) ($data['time_out_at'] ?? [])) : [])
            ->merge($supportsTimeTracking ? array_keys((array) ($data['capture_source'] ?? [])) : [])
            ->map(fn ($item) => (int) $item)
            ->filter(fn ($item) => in_array($item, $validRecipientIds, true))
            ->unique()
            ->values()
            ->all();

        foreach ($recipientIds as $recipientId) {
            $recipientId = (int) $recipientId;
            $status = (string) data_get($data, 'status.' . $recipientId, '');
            $timeIn = $supportsTimeTracking ? $this->normalizeTimeText(data_get($data, 'time_in_at.' . $recipientId)) : '';
            $timeOut = $supportsTimeTracking ? $this->normalizeTimeText(data_get($data, 'time_out_at.' . $recipientId)) : '';
            $captureSource = $supportsTimeTracking
                ? strtolower((string) data_get($data, 'capture_source.' . $recipientId, 'manual'))
                : 'manual';

            if ($status === '' && ($timeIn !== '' || $timeOut !== '')) {
                $status = 'present';
            }

            if ($status === '' && $timeIn === '' && $timeOut === '') {
                EventAttendance::query()
                    ->where('event_id', $event->id)
                    ->where('recipient_id', $recipientId)
                    ->whereDate('attendance_date', $attendanceDate)
                    ->delete();
                continue;
            }

            $updateData = [
                'status' => $status,
            ];

            if ($supportsTimeTracking) {
                $updateData['time_in_at'] = $timeIn !== '' ? ($timeIn . ':00') : null;
                $updateData['time_out_at'] = $timeOut !== '' ? ($timeOut . ':00') : null;
                $updateData['capture_source'] = in_array($captureSource, ['device', 'manual', 'auto'], true) ? $captureSource : 'manual';
            }

            EventAttendance::query()->updateOrCreate(
                [
                    'event_id' => $event->id,
                    'recipient_id' => $recipientId,
                    'attendance_date' => $attendanceDate,
                ],
                $updateData
            );
        }

        return redirect()
            ->route('school.settings.master-data.events.attendance.show', ['event' => $event->id, 'date' => $attendanceDate])
            ->with('success', 'Attendance updated successfully.');
    }

    public function updatePolicy(Request $request, CertificateEvent $event)
    {
        abort_if((int) $event->school_id !== (int) $request->user()->school_id, 403);

        $validated = $request->validate([
            'winner_points' => 'nullable|numeric|min:0|max:100',
            'participant_points' => 'nullable|numeric|min:0|max:100',
            'attendee_points' => 'nullable|numeric|min:0|max:100',
            'attendee_all_days_points' => 'nullable|numeric|min:0|max:100',
            'attendee_one_day_points' => 'nullable|numeric|min:0|max:100',
            'combination' => 'nullable|in:max,add',
            'max_extra_credit' => 'nullable|numeric|min:0|max:100',
            'apply_to_component' => 'nullable|in:major_exam,performance_task,both,custom',
            'apply_to_component_note' => 'nullable|string|max:255',
            'certificate_rule_type' => 'nullable|in:attendee_min_days,custom_mix',
            'eligible_roles' => 'nullable|string|max:255',
            'minimum_attendance_days' => 'nullable|integer|min:1|max:365',
            'eligibility_match_mode' => 'nullable|in:any,all',
            'require_winner_tag' => 'nullable|boolean',
        ]);

        $metadata = is_array($event->metadata) ? $event->metadata : [];
        $existing = $this->normalizeExtraCreditPolicy($event);

        $metadata['extra_credit_policy'] = [
            'role_points' => [
                'winner' => isset($validated['winner_points']) ? (float) $validated['winner_points'] : (float) $existing['role_points']['winner'],
                'participant' => isset($validated['participant_points']) ? (float) $validated['participant_points'] : (float) $existing['role_points']['participant'],
                'attendee' => isset($validated['attendee_points']) ? (float) $validated['attendee_points'] : (float) $existing['role_points']['attendee'],
            ],
            'attendance_points' => [
                'all_days' => isset($validated['attendee_all_days_points']) ? (float) $validated['attendee_all_days_points'] : (float) $existing['attendance_points']['all_days'],
                'one_or_more_days' => isset($validated['attendee_one_day_points']) ? (float) $validated['attendee_one_day_points'] : (float) $existing['attendance_points']['one_or_more_days'],
            ],
            'combination' => $validated['combination'] ?? $existing['combination'],
            'max_extra_credit' => array_key_exists('max_extra_credit', $validated)
                ? ($validated['max_extra_credit'] === null ? null : (float) $validated['max_extra_credit'])
                : $existing['max_extra_credit'],
            'apply_to_component' => $validated['apply_to_component'] ?? $existing['apply_to_component'],
            'apply_to_component_note' => array_key_exists('apply_to_component_note', $validated)
                ? trim((string) ($validated['apply_to_component_note'] ?? ''))
                : $existing['apply_to_component_note'],
            'certificate_eligibility' => [
                'rule_type' => $validated['certificate_rule_type'] ?? data_get($existing, 'certificate_eligibility.rule_type', 'attendee_min_days'),
                'eligible_roles' => $this->normalizeRoleCsvToArray(
                    array_key_exists('eligible_roles', $validated)
                        ? (string) ($validated['eligible_roles'] ?? '')
                        : implode(', ', (array) data_get($existing, 'certificate_eligibility.eligible_roles', ['attendee']))
                ),
                'minimum_attendance_days' => array_key_exists('minimum_attendance_days', $validated)
                    ? ($validated['minimum_attendance_days'] === null ? null : (int) $validated['minimum_attendance_days'])
                    : data_get($existing, 'certificate_eligibility.minimum_attendance_days'),
                'eligibility_match_mode' => $validated['eligibility_match_mode'] ?? data_get($existing, 'certificate_eligibility.eligibility_match_mode', 'all'),
                'require_winner_tag' => $request->boolean('require_winner_tag', (bool) data_get($existing, 'certificate_eligibility.require_winner_tag', false)),
            ],
        ];

        $event->update([
            'metadata' => $metadata,
        ]);

        return redirect()
            ->route('school.settings.master-data.events.attendance.show', ['event' => $event->id])
            ->with('success', 'Extra credit policy updated successfully.');
    }

    private function buildEventDays(CertificateEvent $event): array
    {
        if (!$event->start_date || !$event->end_date) {
            return [now()->toDateString()];
        }

        $start = Carbon::parse($event->start_date)->startOfDay();
        $end = Carbon::parse($event->end_date)->startOfDay();

        if ($end->lt($start)) {
            return [$start->toDateString()];
        }

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $days;
    }

    private function normalizeExtraCreditPolicy(CertificateEvent $event): array
    {
        $metadata = is_array($event->metadata) ? $event->metadata : [];
        $policy = data_get($metadata, 'extra_credit_policy', []);

        return [
            'role_points' => [
                'winner' => (float) data_get($policy, 'role_points.winner', 20),
                'participant' => (float) data_get($policy, 'role_points.participant', 10),
                'attendee' => (float) data_get($policy, 'role_points.attendee', 0),
            ],
            'attendance_points' => [
                'all_days' => (float) data_get($policy, 'attendance_points.all_days', 5),
                'one_or_more_days' => (float) data_get($policy, 'attendance_points.one_or_more_days', 3),
            ],
            'combination' => (string) data_get($policy, 'combination', 'max'),
            'max_extra_credit' => data_get($policy, 'max_extra_credit'),
            'apply_to_component' => (string) data_get($policy, 'apply_to_component', 'major_exam'),
            'apply_to_component_note' => (string) data_get($policy, 'apply_to_component_note', ''),
            'certificate_eligibility' => [
                'rule_type' => (string) data_get($policy, 'certificate_eligibility.rule_type', 'attendee_min_days'),
                'eligible_roles' => collect((array) data_get($policy, 'certificate_eligibility.eligible_roles', ['attendee']))
                    ->map(fn ($item) => strtolower(trim((string) $item)))
                    ->filter(fn ($item) => $item !== '')
                    ->unique()
                    ->values()
                    ->all(),
                'minimum_attendance_days' => data_get($policy, 'certificate_eligibility.minimum_attendance_days'),
                'eligibility_match_mode' => (string) data_get($policy, 'certificate_eligibility.eligibility_match_mode', 'all'),
                'require_winner_tag' => (bool) data_get($policy, 'certificate_eligibility.require_winner_tag', false),
            ],
        ];
    }

    private function buildExtraCreditSummary(CertificateEvent $event, array $policy, array $days): array
    {
        $attendanceRows = EventAttendance::query()
            ->where('event_id', $event->id)
            ->whereIn('status', ['present', 'late'])
            ->get(['recipient_id', 'attendance_date'])
            ->groupBy('recipient_id');

        $totalDays = max(1, count($days));

        return $event->recipients->map(function ($recipient) use ($attendanceRows, $policy, $totalDays) {
            $roles = $this->extractRoles($recipient->custom_fields ?? []);

            $attendanceCount = (int) ($attendanceRows->get($recipient->id)?->pluck('attendance_date')->unique()->count() ?? 0);

            $rolePoints = 0;
            foreach ($roles as $role) {
                $rolePoints = max($rolePoints, (float) data_get($policy, 'role_points.' . $role, 0));
            }

            $attendancePoints = 0;
            if ($attendanceCount >= $totalDays) {
                $attendancePoints = (float) data_get($policy, 'attendance_points.all_days', 0);
            } elseif ($attendanceCount >= 1) {
                $attendancePoints = (float) data_get($policy, 'attendance_points.one_or_more_days', 0);
            }

            $combined = (string) ($policy['combination'] ?? 'max') === 'add'
                ? ($rolePoints + $attendancePoints)
                : max($rolePoints, $attendancePoints);

            $maxCredit = $policy['max_extra_credit'];
            if ($maxCredit !== null && is_numeric($maxCredit)) {
                $combined = min((float) $combined, (float) $maxCredit);
            }

            $eligibility = $this->evaluateCertificateEligibility(
                $roles,
                $attendanceCount,
                $totalDays,
                (array) ($policy['certificate_eligibility'] ?? [])
            );

            return [
                'recipient_id' => $recipient->id,
                'recipient_name' => $recipient->recipient_name,
                'roles' => $roles,
                'attendance_days' => $attendanceCount,
                'total_days' => $totalDays,
                'role_points' => $rolePoints,
                'attendance_points' => $attendancePoints,
                'extra_credit_points' => (float) $combined,
                'certificate_eligible' => (bool) $eligibility['eligible'],
                'certificate_reason' => (string) $eligibility['reason'],
            ];
        })->values()->all();
    }

    private function evaluateCertificateEligibility(array $roles, int $attendanceDays, int $totalDays, array $rule): array
    {
        $roles = collect($roles)
            ->map(fn ($item) => strtolower(trim((string) $item)))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();

        $ruleType = (string) ($rule['rule_type'] ?? 'attendee_min_days');
        $eligibleRoles = collect((array) ($rule['eligible_roles'] ?? []))
            ->map(fn ($item) => strtolower(trim((string) $item)))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();
        $minimumAttendanceDays = isset($rule['minimum_attendance_days']) && $rule['minimum_attendance_days'] !== null
            ? (int) $rule['minimum_attendance_days']
            : null;
        $matchMode = (string) ($rule['eligibility_match_mode'] ?? 'all');
        $requireWinnerTag = (bool) ($rule['require_winner_tag'] ?? false);

        $hasParticipant = in_array('participant', $roles, true);
        $hasWinner = in_array('winner', $roles, true);
        $hasAttendee = in_array('attendee', $roles, true);

        if ($ruleType === 'winner_only') {
            $eligible = $hasParticipant && $hasWinner;
            return [
                'eligible' => $eligible,
                'reason' => $eligible
                    ? 'Eligible: participant with winner tag.'
                    : 'Not eligible: requires both participant and winner roles.',
            ];
        }

        if ($ruleType === 'participant_and_winner') {
            $eligible = $hasParticipant;
            return [
                'eligible' => $eligible,
                'reason' => $eligible
                    ? 'Eligible: participant role is allowed.'
                    : 'Not eligible: participant role required.',
            ];
        }

        if ($ruleType === 'attendee_min_days') {
            $required = $minimumAttendanceDays ?? $totalDays;
            $eligible = $hasAttendee && $attendanceDays >= $required;

            return [
                'eligible' => $eligible,
                'reason' => $eligible
                    ? 'Eligible: attendee met minimum attendance (' . $attendanceDays . '/' . $required . ').'
                    : 'Not eligible: attendee requires at least ' . $required . ' day(s), current ' . $attendanceDays . '.',
            ];
        }

        // custom_mix
        $checks = [];

        if (count($eligibleRoles) > 0) {
            $checks[] = count(array_intersect($roles, $eligibleRoles)) > 0;
        }

        if ($minimumAttendanceDays !== null) {
            $checks[] = $attendanceDays >= $minimumAttendanceDays;
        }

        if ($requireWinnerTag) {
            $checks[] = $hasWinner;
        }

        if (count($checks) === 0) {
            return [
                'eligible' => false,
                'reason' => 'Not eligible: no custom eligibility checks configured.',
            ];
        }

        $eligible = $matchMode === 'any'
            ? in_array(true, $checks, true)
            : !in_array(false, $checks, true);

        return [
            'eligible' => $eligible,
            'reason' => $eligible
                ? 'Eligible: custom mix rule passed (' . strtoupper($matchMode) . ').'
                : 'Not eligible: custom mix rule failed (' . strtoupper($matchMode) . ').',
        ];
    }

    private function normalizeRoleCsvToArray(string $csv): array
    {
        return collect(explode(',', $csv))
            ->map(fn ($item) => strtolower(trim((string) $item)))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function extractRoles(array $customFields): array
    {
        $explicit = collect(data_get($customFields, 'extra_credit_roles', []))
            ->map(fn ($item) => strtolower(trim((string) $item)))
            ->filter(fn ($item) => $item !== '')
            ->values()
            ->all();

        if (count($explicit) > 0) {
            return array_values(array_unique($explicit));
        }

        $fallback = strtolower(trim((string) data_get($customFields, 'selected_role_type', '')));
        return $fallback !== '' ? [$fallback] : [];
    }

    private function normalizeTimeText(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return '';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $text) === 1) {
            return substr($text, 0, 5);
        }

        if (preg_match('/^\d{2}:\d{2}$/', $text) === 1) {
            return $text;
        }

        return '';
    }

    private function supportsAttendanceTimeTracking(): bool
    {
        return Schema::hasColumns('event_attendances', ['time_in_at', 'time_out_at', 'capture_source']);
    }

    private function resolveCompletionLabel(string $status, string $timeOut, string $eventEndTime): string
    {
        if ($status === '' || $status === 'absent') {
            return 'Absent';
        }

        if ($eventEndTime !== '') {
            if ($timeOut === '' || $timeOut < $eventEndTime) {
                return 'Undertime';
            }
        }

        return 'Completed';
    }

    private function resolveCompletionReason(string $resolvedStatus, string $eventEndTime): string
    {
        if ($resolvedStatus === 'Absent') {
            return 'No attendance record for this date.';
        }

        if ($resolvedStatus === 'Undertime') {
            return $eventEndTime !== ''
                ? ('Timed out earlier than event end time (' . $eventEndTime . ').')
                : 'Timed out earlier than the required session end.';
        }

        return 'Completed required attendance period.';
    }
}
