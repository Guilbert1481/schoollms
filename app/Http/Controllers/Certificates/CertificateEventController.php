<?php

namespace App\Http\Controllers\Certificates;

use App\Http\Controllers\Controller;
use App\Models\CertificateEvent;
use App\Models\CertificateEventRecipient;
use App\Models\EventAttendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $schoolId = (int) $request->user()->school_id;

        $events = CertificateEvent::query()
            ->where('school_id', $schoolId)
            ->with('template:id,name,category')
            ->withCount('recipients')
            ->latest()
            ->get();

        return response()->json(['data' => $events]);
    }

    public function show(Request $request, int $eventId): JsonResponse
    {
        $event = $this->getSchoolEventOrFail($request, $eventId);
        $event->load('template:id,name,category', 'recipients', 'eventTypes:id,event_id,name');

        return response()->json(['data' => $event]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'template_id' => 'required|integer|exists:certificate_templates,id',
            'participant_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'winner_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'champion_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'first_place_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'second_place_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'third_place_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'event_name' => 'required|string|max:255',
            'event_type' => 'nullable|string|max:50',
            'certificate_title_default' => 'nullable|string|max:255',
            'date_issued_default' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        $data['school_id'] = (int) $request->user()->school_id;
        $data['event_type'] = $data['event_type'] ?? 'other';
        $data['metadata'] = $this->mergeTemplateMapIntoMetadata($data);

        unset(
            $data['participant_template_id'],
            $data['winner_template_id'],
            $data['champion_template_id'],
            $data['first_place_template_id'],
            $data['second_place_template_id'],
            $data['third_place_template_id'],
        );

        $event = CertificateEvent::create($data);

        return response()->json([
            'message' => 'Certificate event created successfully.',
            'data' => $event,
        ], 201);
    }

    public function update(Request $request, int $eventId): JsonResponse
    {
        $event = $this->getSchoolEventOrFail($request, $eventId);

        $data = $request->validate([
            'template_id' => 'sometimes|integer|exists:certificate_templates,id',
            'participant_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'winner_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'champion_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'first_place_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'second_place_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'third_place_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'event_name' => 'sometimes|string|max:255',
            'event_type' => 'sometimes|string|max:50',
            'certificate_title_default' => 'nullable|string|max:255',
            'date_issued_default' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        $data['metadata'] = $this->mergeTemplateMapIntoMetadata($data, $event);

        unset(
            $data['participant_template_id'],
            $data['winner_template_id'],
            $data['champion_template_id'],
            $data['first_place_template_id'],
            $data['second_place_template_id'],
            $data['third_place_template_id'],
        );

        $event->update($data);

        return response()->json([
            'message' => 'Certificate event updated successfully.',
            'data' => $event,
        ]);
    }

    public function destroy(Request $request, int $eventId): JsonResponse
    {
        $event = $this->getSchoolEventOrFail($request, $eventId);
        $event->delete();

        return response()->json(['message' => 'Certificate event deleted successfully.']);
    }

    public function storeRecipient(Request $request, int $eventId): JsonResponse
    {
        $event = $this->getSchoolEventOrFail($request, $eventId);

        $data = $request->validate([
            'recipient_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'recipient_name' => 'required|string|max:255',
            'certificate_title' => 'nullable|string|max:255',
            'award_title' => 'nullable|string|max:255',
            'activity_name' => 'nullable|string|max:255',
            'recognition_reason' => 'nullable|string',
            'organization_name' => 'nullable|string|max:255',
            'signatory_name' => 'nullable|string|max:255',
            'issued_date' => 'nullable|date',
            'custom_fields' => 'nullable|array',
            'generated_file_path' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,generated,failed',
        ]);

        $data['status'] = $data['status'] ?? 'pending';

        if ($this->isAutoGeneratedAwardTitle($data['award_title'] ?? null)) {
            return response()->json([
                'message' => 'Participant/Participation/Attendee/Attendance are auto-generated from attendance setup and cannot be entered manually.',
                'errors' => [
                    'award_title' => ['This award type is auto-generated and cannot be entered manually.'],
                ],
            ], 422);
        }

        if (($data['status'] ?? 'pending') === 'generated') {
            $eligibility = $this->evaluateRecipientEligibility($event, [
                'custom_fields' => $data['custom_fields'] ?? [],
            ]);

            if (!$eligibility['eligible']) {
                return response()->json([
                    'message' => 'Recipient is not eligible for certificate issuance.',
                    'errors' => [
                        'status' => [$eligibility['reason']],
                    ],
                ], 422);
            }
        }

        $data['recipient_template_id'] = $this->resolveRecipientTemplateId($event, $data);

        $recipient = $event->recipients()->create($data);

        return response()->json([
            'message' => 'Recipient added successfully.',
            'data' => $recipient,
        ], 201);
    }

    public function bulkStoreRecipients(Request $request, int $eventId): JsonResponse
    {
        $event = $this->getSchoolEventOrFail($request, $eventId);

        $validated = $request->validate([
            'recipients' => 'required|array|min:1',
            'recipients.*.recipient_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'recipients.*.recipient_name' => 'required|string|max:255',
            'recipients.*.certificate_title' => 'nullable|string|max:255',
            'recipients.*.award_title' => 'nullable|string|max:255',
            'recipients.*.activity_name' => 'nullable|string|max:255',
            'recipients.*.recognition_reason' => 'nullable|string',
            'recipients.*.organization_name' => 'nullable|string|max:255',
            'recipients.*.signatory_name' => 'nullable|string|max:255',
            'recipients.*.issued_date' => 'nullable|date',
            'recipients.*.custom_fields' => 'nullable|array',
            'recipients.*.generated_file_path' => 'nullable|string|max:255',
            'recipients.*.status' => 'nullable|in:pending,generated,failed',
        ]);

        $created = [];

        foreach ($validated['recipients'] as $recipientData) {
            $recipientData['status'] = $recipientData['status'] ?? 'pending';

            if ($this->isAutoGeneratedAwardTitle($recipientData['award_title'] ?? null)) {
                return response()->json([
                    'message' => 'One or more recipients use an auto-generated award type that cannot be entered manually.',
                    'errors' => [
                        'recipients' => ['Participant/Participation/Attendee/Attendance must be auto-generated from attendance setup.'],
                    ],
                ], 422);
            }

            if (($recipientData['status'] ?? 'pending') === 'generated') {
                $eligibility = $this->evaluateRecipientEligibility($event, [
                    'custom_fields' => $recipientData['custom_fields'] ?? [],
                ]);

                if (!$eligibility['eligible']) {
                    return response()->json([
                        'message' => 'One or more recipients are not eligible for certificate issuance.',
                        'errors' => [
                            'recipients' => [$eligibility['reason']],
                        ],
                    ], 422);
                }
            }

            $recipientData['recipient_template_id'] = $this->resolveRecipientTemplateId($event, $recipientData);

            $created[] = $event->recipients()->create($recipientData);
        }

        return response()->json([
            'message' => 'Recipients imported successfully.',
            'count' => count($created),
            'data' => $created,
        ], 201);
    }

    public function updateRecipient(Request $request, int $eventId, int $recipientId): JsonResponse
    {
        $event = $this->getSchoolEventOrFail($request, $eventId);

        $recipient = $event->recipients()->whereKey($recipientId)->firstOrFail();

        $data = $request->validate([
            'recipient_template_id' => 'nullable|integer|exists:certificate_templates,id',
            'recipient_name' => 'sometimes|string|max:255',
            'certificate_title' => 'nullable|string|max:255',
            'award_title' => 'nullable|string|max:255',
            'activity_name' => 'nullable|string|max:255',
            'recognition_reason' => 'nullable|string',
            'organization_name' => 'nullable|string|max:255',
            'signatory_name' => 'nullable|string|max:255',
            'issued_date' => 'nullable|date',
            'custom_fields' => 'nullable|array',
            'generated_file_path' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,generated,failed',
        ]);

        $nextStatus = $data['status'] ?? $recipient->status;
        if (array_key_exists('award_title', $data) && $this->isAutoGeneratedAwardTitle($data['award_title'])) {
            return response()->json([
                'message' => 'Participant/Participation/Attendee/Attendance are auto-generated from attendance setup and cannot be entered manually.',
                'errors' => [
                    'award_title' => ['This award type is auto-generated and cannot be entered manually.'],
                ],
            ], 422);
        }

        if ($nextStatus === 'generated') {
            $candidateCustomFields = array_key_exists('custom_fields', $data)
                ? ($data['custom_fields'] ?? [])
                : ($recipient->custom_fields ?? []);

            $eligibility = $this->evaluateRecipientEligibility($event, [
                'id' => $recipient->id,
                'custom_fields' => $candidateCustomFields,
            ]);

            if (!$eligibility['eligible']) {
                return response()->json([
                    'message' => 'Recipient is not eligible for certificate issuance.',
                    'errors' => [
                        'status' => [$eligibility['reason']],
                    ],
                ], 422);
            }
        }

        $candidateAwardTitle = array_key_exists('award_title', $data)
            ? ($data['award_title'] ?? null)
            : $recipient->award_title;
        $candidateCustomFields = array_key_exists('custom_fields', $data)
            ? ($data['custom_fields'] ?? [])
            : ($recipient->custom_fields ?? []);

        $data['recipient_template_id'] = $this->resolveRecipientTemplateId($event, [
            'recipient_template_id' => $data['recipient_template_id'] ?? null,
            'award_title' => $candidateAwardTitle,
            'custom_fields' => $candidateCustomFields,
        ], $recipient);

        $recipient->update($data);

        return response()->json([
            'message' => 'Recipient updated successfully.',
            'data' => $recipient,
        ]);
    }

    public function destroyRecipient(Request $request, int $eventId, int $recipientId): JsonResponse
    {
        $event = $this->getSchoolEventOrFail($request, $eventId);

        $recipient = $event->recipients()->whereKey($recipientId)->firstOrFail();
        $recipient->delete();

        return response()->json(['message' => 'Recipient deleted successfully.']);
    }

    private function getSchoolEventOrFail(Request $request, int $eventId): CertificateEvent
    {
        return CertificateEvent::query()
            ->where('school_id', (int) $request->user()->school_id)
            ->findOrFail($eventId);
    }

    private function evaluateRecipientEligibility(CertificateEvent $event, array $recipient): array
    {
        $customFields = is_array($recipient['custom_fields'] ?? null) ? $recipient['custom_fields'] : [];
        $roles = $this->extractRoles($customFields);

        $policy = (array) data_get($event->metadata, 'extra_credit_policy.certificate_eligibility', []);
        $ruleType = (string) ($policy['rule_type'] ?? 'attendee_min_days');
        $eligibleRoles = collect((array) ($policy['eligible_roles'] ?? ['attendee']))
            ->map(fn ($item) => strtolower(trim((string) $item)))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();
        $minimumAttendanceDays = isset($policy['minimum_attendance_days']) && $policy['minimum_attendance_days'] !== null
            ? (int) $policy['minimum_attendance_days']
            : null;
        $matchMode = (string) ($policy['eligibility_match_mode'] ?? 'all');
        $requireWinnerTag = (bool) ($policy['require_winner_tag'] ?? false);

        $attendanceDays = $this->attendanceDaysForRecipient($event, (int) ($recipient['id'] ?? 0));
        $totalDays = $this->totalEventDays($event);

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

    private function attendanceDaysForRecipient(CertificateEvent $event, int $recipientId): int
    {
        if ($recipientId <= 0) {
            return 0;
        }

        return EventAttendance::query()
            ->where('event_id', $event->id)
            ->where('recipient_id', $recipientId)
            ->whereIn('status', ['present', 'late'])
            ->distinct('attendance_date')
            ->count('attendance_date');
    }

    private function totalEventDays(CertificateEvent $event): int
    {
        if (!$event->start_date || !$event->end_date) {
            return 1;
        }

        $start = Carbon::parse($event->start_date)->startOfDay();
        $end = Carbon::parse($event->end_date)->startOfDay();

        if ($end->lt($start)) {
            return 1;
        }

        return $start->diffInDays($end) + 1;
    }

    private function mergeTemplateMapIntoMetadata(array $data, ?CertificateEvent $event = null): array
    {
        $existing = $event && is_array($event->metadata) ? $event->metadata : [];
        $incoming = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];

        $templateMap = [
            'participant_template_id' => isset($data['participant_template_id']) ? (int) $data['participant_template_id'] : (int) data_get($existing, 'template_map.participant_template_id', 0),
            'winner_template_id' => isset($data['winner_template_id']) ? (int) $data['winner_template_id'] : (int) data_get($existing, 'template_map.winner_template_id', 0),
            'champion_template_id' => isset($data['champion_template_id']) ? (int) $data['champion_template_id'] : (int) data_get($existing, 'template_map.champion_template_id', 0),
            'first_place_template_id' => isset($data['first_place_template_id']) ? (int) $data['first_place_template_id'] : (int) data_get($existing, 'template_map.first_place_template_id', 0),
            'second_place_template_id' => isset($data['second_place_template_id']) ? (int) $data['second_place_template_id'] : (int) data_get($existing, 'template_map.second_place_template_id', 0),
            'third_place_template_id' => isset($data['third_place_template_id']) ? (int) $data['third_place_template_id'] : (int) data_get($existing, 'template_map.third_place_template_id', 0),
        ];

        $templateMap = collect($templateMap)
            ->map(fn ($value) => $value > 0 ? $value : null)
            ->filter(fn ($value) => $value !== null)
            ->all();

        $merged = array_merge($existing, $incoming);
        $merged['template_map'] = $templateMap;

        return $merged;
    }

    private function resolveRecipientTemplateId(CertificateEvent $event, array $recipientData, ?CertificateEventRecipient $existingRecipient = null): int
    {
        if (isset($recipientData['recipient_template_id']) && (int) $recipientData['recipient_template_id'] > 0) {
            return (int) $recipientData['recipient_template_id'];
        }

        $templateMap = (array) data_get($event->metadata, 'template_map', []);
        $awardTitle = strtolower(trim((string) ($recipientData['award_title'] ?? '')));
        $roles = $this->extractRoles(is_array($recipientData['custom_fields'] ?? null) ? $recipientData['custom_fields'] : []);

        if ($awardTitle !== '') {
            if (str_contains($awardTitle, 'champion')) {
                $candidate = (int) ($templateMap['champion_template_id'] ?? 0);
                if ($candidate > 0) {
                    return $candidate;
                }
            }

            if (str_contains($awardTitle, '1st') || str_contains($awardTitle, 'first')) {
                $candidate = (int) ($templateMap['first_place_template_id'] ?? 0);
                if ($candidate > 0) {
                    return $candidate;
                }
            }

            if (str_contains($awardTitle, '2nd') || str_contains($awardTitle, 'second')) {
                $candidate = (int) ($templateMap['second_place_template_id'] ?? 0);
                if ($candidate > 0) {
                    return $candidate;
                }
            }

            if (str_contains($awardTitle, '3rd') || str_contains($awardTitle, 'third')) {
                $candidate = (int) ($templateMap['third_place_template_id'] ?? 0);
                if ($candidate > 0) {
                    return $candidate;
                }
            }

            if (str_contains($awardTitle, 'winner')) {
                $candidate = (int) ($templateMap['winner_template_id'] ?? 0);
                if ($candidate > 0) {
                    return $candidate;
                }
            }

            if (str_contains($awardTitle, 'participant') || str_contains($awardTitle, 'attendee')) {
                $candidate = (int) ($templateMap['participant_template_id'] ?? 0);
                if ($candidate > 0) {
                    return $candidate;
                }
            }
        }

        if (in_array('participant', $roles, true) || in_array('contestant', $roles, true) || in_array('attendee', $roles, true)) {
            $candidate = (int) ($templateMap['participant_template_id'] ?? 0);
            if ($candidate > 0) {
                return $candidate;
            }
        }

        if ($existingRecipient && (int) ($existingRecipient->recipient_template_id ?? 0) > 0) {
            return (int) $existingRecipient->recipient_template_id;
        }

        return (int) $event->template_id;
    }

    private function isAutoGeneratedAwardTitle(?string $awardTitle): bool
    {
        $normalized = strtolower(trim((string) $awardTitle));
        return in_array($normalized, ['participant', 'participation', 'attendee', 'attendance'], true);
    }
}
