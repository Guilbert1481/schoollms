<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Services\FormService;
use App\Models\CertificateEvent;
use App\Models\EventActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class MasterDataEventFormController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = (int) $request->user()->school_id;
        $formSchema = FormService::buildForm('event_form_setup');
        $masterActivities = EventActivity::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $events = CertificateEvent::query()
            ->where('school_id', $schoolId)
            ->with(['eventTypes:id,event_id,name', 'eventRoles:id,event_id,name'])
            ->latest()
            ->limit(12)
            ->get()
            ->map(function (CertificateEvent $event) {
                $event->setAttribute('form_link', URL::signedRoute(
                    'school.settings.master-data.events.public.show',
                    ['event' => $event->id]
                ));

                return $event;
            });

        return view('school.settings.master-data.events.index', compact('events', 'formSchema', 'masterActivities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_name' => 'required|string|max:255',
            'event_type' => 'nullable|string|max:100',
            'event_types' => 'required',
            'selected_activity_ids' => 'nullable',
            'role_types' => 'required',
            'certificate_title_default' => 'nullable|string|max:255',
            'date_issued_default' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
        ]);

        $schoolId = (int) $request->user()->school_id;
        $selectedActivityIds = $this->normalizeIntegerInput($data['selected_activity_ids'] ?? []);
        $masterActivityNames = EventActivity::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $selectedActivityIds)
            ->pluck('name')
            ->all();

        $customEventTypes = $this->normalizeTagInput($data['event_types']);
        $eventTypes = collect(array_merge($masterActivityNames, $customEventTypes))
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();

        $roleTypes = $this->normalizeTagInput($data['role_types']);

        if (count($eventTypes) === 0) {
            return response()->json([
                'message' => 'Please add at least one event category/activity.',
                'errors' => ['event_types' => ['Please add at least one event category/activity.']],
            ], 422);
        }

        if (count($roleTypes) === 0) {
            return response()->json([
                'message' => 'Please add at least one role type.',
                'errors' => ['role_types' => ['Please add at least one role type.']],
            ], 422);
        }

        $fallbackTemplateId = DB::table('certificate_templates')->orderBy('id')->value('id');

        if (!$fallbackTemplateId) {
            return response()->json([
                'message' => 'Please create at least one certificate template first.',
            ], 422);
        }

        $event = CertificateEvent::create([
            'school_id' => $schoolId,
            // The current table requires template_id, so set a safe fallback.
            'template_id' => (int) $fallbackTemplateId,
            'event_name' => $data['event_name'],
            'event_type' => $data['event_type'] ?? 'other',
            'certificate_title_default' => $data['certificate_title_default'] ?? null,
            'date_issued_default' => $data['date_issued_default'] ?? null,
            'metadata' => [
                'event_types' => $eventTypes,
                'role_types' => $roleTypes,
                'description' => $data['description'] ?? null,
            ],
        ]);

        $event->eventTypes()->createMany(
            collect($eventTypes)->map(fn ($name) => ['name' => $name])->all()
        );

        $event->eventRoles()->createMany(
            collect($roleTypes)->map(fn ($name) => ['name' => $name])->all()
        );

        // Promote event categories to school-level master list for reuse.
        EventActivity::query()->upsert(
            collect($eventTypes)
                ->map(fn ($name) => [
                    'school_id' => $schoolId,
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all(),
            ['school_id', 'name'],
            ['updated_at']
        );

        $event->load(['eventTypes:id,event_id,name', 'eventRoles:id,event_id,name']);

        return response()->json([
            'message' => 'Event form created successfully.',
            'data' => $event,
            'form_link' => URL::signedRoute(
                'school.settings.master-data.events.public.show',
                ['event' => $event->id]
            ),
        ], 201);
    }

    public function showPublic(Request $request, CertificateEvent $event)
    {
        $viewer = $request->user();

        abort_if(!$viewer, 403);
        abort_if((int) $viewer->school_id !== (int) $event->school_id, 403);

        $viewer->loadMissing('profile', 'profile.staff', 'profile.student');
        $event->loadMissing(['eventTypes:id,event_id,name', 'eventRoles:id,event_id,name']);

        $prefill = [
            'recipient_name' => trim($viewer->full_name ?: $viewer->name ?? ''),
            'email' => $viewer->email,
            'id_number' => data_get($viewer, 'profile.staff.employee_number')
                ?: data_get($viewer, 'profile.student.government_id_number')
                ?: '',
            'organization_name' => data_get($viewer, 'profile.city') ?: data_get($viewer, 'profile.address') ?: '',
        ];

        $eventTypeOptions = $event->eventTypes
            ->pluck('name')
            ->filter(fn ($v) => filled($v))
            ->values()
            ->all();

        if (count($eventTypeOptions) === 0) {
            $eventTypeOptions = collect(data_get($event->metadata, 'event_types', []))
                ->filter(fn ($v) => filled($v))
                ->values()
                ->all();
        }

        $roleTypeOptions = $event->eventRoles
            ->pluck('name')
            ->filter(fn ($v) => filled($v))
            ->values()
            ->all();

        if (count($roleTypeOptions) === 0) {
            $roleTypeOptions = collect(data_get($event->metadata, 'role_types', []))
                ->filter(fn ($v) => filled($v))
                ->values()
                ->all();
        }

        return view('school.settings.master-data.events.public-form', compact(
            'event',
            'prefill',
            'eventTypeOptions',
            'roleTypeOptions'
        ));
    }

    public function submitPublic(Request $request, CertificateEvent $event)
    {
        $viewer = $request->user();

        abort_if(!$viewer, 403);
        abort_if((int) $viewer->school_id !== (int) $event->school_id, 403);

        $data = $request->validate([
            'selected_event_type' => 'required|string|max:255',
            'selected_role_type' => 'required|string|max:255',
        ]);

        $viewer->loadMissing('profile', 'profile.staff', 'profile.student');
        $event->loadMissing(['eventTypes:id,event_id,name', 'eventRoles:id,event_id,name']);

        $allowedEventTypes = $event->eventTypes->pluck('name')->all();
        $allowedRoleTypes = $event->eventRoles->pluck('name')->all();

        if (count($allowedEventTypes) > 0 && !in_array($data['selected_event_type'], $allowedEventTypes, true)) {
            return back()->withErrors([
                'selected_event_type' => 'Please select a valid event type.',
            ])->withInput();
        }

        if (count($allowedRoleTypes) > 0 && !in_array($data['selected_role_type'], $allowedRoleTypes, true)) {
            return back()->withErrors([
                'selected_role_type' => 'Please select a valid role type.',
            ])->withInput();
        }

        $recipientName = trim($viewer->full_name ?: $viewer->name ?? '');
        $email = $viewer->email;
        $idNumber = data_get($viewer, 'profile.staff.employee_number')
            ?: data_get($viewer, 'profile.student.government_id_number')
            ?: null;
        $organizationName = data_get($viewer, 'profile.city') ?: data_get($viewer, 'profile.address') ?: null;

        $certificateTitle = $event->certificate_title_default ?: $this->resolveCertificateTitleByRole($data['selected_role_type']);

        $certificateTrack = $this->resolveCertificateTrackByRole($data['selected_role_type']);

        $event->recipients()->create([
            'recipient_name' => $recipientName,
            'certificate_title' => $certificateTitle,
            'award_title' => null,
            'activity_name' => $data['selected_event_type'],
            'recognition_reason' => null,
            'organization_name' => $organizationName,
            'issued_date' => $event->date_issued_default,
            'status' => 'pending',
            'custom_fields' => [
                'user_id' => $viewer->id,
                'email' => $email,
                'id_number' => $idNumber,
                'selected_event_type' => $data['selected_event_type'],
                'selected_role_type' => $data['selected_role_type'],
                'certificate_track' => $certificateTrack,
                'role_types' => data_get($event->metadata, 'role_types', []),
                'event_types' => data_get($event->metadata, 'event_types', []),
                'submitted_from' => 'event_form',
            ],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Your response has been submitted.');
    }

    private function normalizeTagInput(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item) => trim((string) $item))
                ->filter(fn ($item) => $item !== '')
                ->unique()
                ->values()
                ->all();
        }

        $string = trim((string) $value);

        if ($string === '') {
            return [];
        }

        $decoded = json_decode($string, true);

        if (is_array($decoded)) {
            return collect($decoded)
                ->map(fn ($item) => trim((string) $item))
                ->filter(fn ($item) => $item !== '')
                ->unique()
                ->values()
                ->all();
        }

        return collect(explode(',', $string))
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function resolveCertificateTitleByRole(string $roleType): string
    {
        $normalized = strtolower(trim($roleType));

        return match (true) {
            str_contains($normalized, 'coach') => 'Certificate of Service',
            str_contains($normalized, 'referee') => 'Certificate of Service',
            str_contains($normalized, 'judge') => 'Certificate of Service',
            str_contains($normalized, 'contestant') => 'Certificate of Contest Participation',
            str_contains($normalized, 'participant') => 'Certificate of Participation',
            str_contains($normalized, 'attendee') => 'Certificate of Attendance',
            default => 'Certificate of Participation',
        };
    }

    private function resolveCertificateTrackByRole(string $roleType): string
    {
        $normalized = strtolower(trim($roleType));

        if (str_contains($normalized, 'coach') || str_contains($normalized, 'referee') || str_contains($normalized, 'judge')) {
            return 'service';
        }

        if (str_contains($normalized, 'contestant')) {
            return 'contestant';
        }

        if (str_contains($normalized, 'attendee')) {
            return 'attendance';
        }

        return 'participant';
    }

    private function normalizeIntegerInput(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item) => (int) $item)
                ->filter(fn ($item) => $item > 0)
                ->unique()
                ->values()
                ->all();
        }

        $string = trim((string) $value);

        if ($string === '') {
            return [];
        }

        $decoded = json_decode($string, true);

        if (is_array($decoded)) {
            return collect($decoded)
                ->map(fn ($item) => (int) $item)
                ->filter(fn ($item) => $item > 0)
                ->unique()
                ->values()
                ->all();
        }

        return collect(explode(',', $string))
            ->map(fn ($item) => (int) trim((string) $item))
            ->filter(fn ($item) => $item > 0)
            ->unique()
            ->values()
            ->all();
    }
}
