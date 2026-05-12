<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\CertificateEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EventCertificatePolicyController extends Controller
{
    public function show(Request $request, CertificateEvent $event)
    {
        abort_if((int) $event->school_id !== (int) $request->user()->school_id, 403);

        $controls = $this->normalizedControls($event);

        return response()->json([
            'event_id' => $event->id,
            'event_name' => $event->event_name,
            'total_event_days' => $this->totalEventDays($event),
            'certificate_controls' => $controls,
        ]);
    }

    public function update(Request $request, CertificateEvent $event)
    {
        abort_if((int) $event->school_id !== (int) $request->user()->school_id, 403);

        $totalDays = $this->totalEventDays($event);

        $validated = $request->validate([
            'required_attendance_days_for_certificate' => [
                'nullable',
                'integer',
                'min:1',
                'max:' . max(1, $totalDays),
            ],
            'winner_attendance_required_for_issuance' => 'nullable|boolean',
            'track_winner_attendance' => 'nullable|boolean',
            'multi_role_strategy' => 'nullable|in:any_eligible_role,strict_all_roles,priority_role',
        ]);

        $metadata = is_array($event->metadata) ? $event->metadata : [];
        $existing = $this->normalizedControls($event);

        $metadata['certificate_controls'] = [
            'required_attendance_days_for_certificate' => $validated['required_attendance_days_for_certificate']
                ?? $existing['required_attendance_days_for_certificate'],
            'winner_attendance_required_for_issuance' => array_key_exists('winner_attendance_required_for_issuance', $validated)
                ? (bool) $validated['winner_attendance_required_for_issuance']
                : $existing['winner_attendance_required_for_issuance'],
            'track_winner_attendance' => array_key_exists('track_winner_attendance', $validated)
                ? (bool) $validated['track_winner_attendance']
                : $existing['track_winner_attendance'],
            'multi_role_strategy' => $validated['multi_role_strategy'] ?? $existing['multi_role_strategy'],
        ];

        $event->update([
            'metadata' => $metadata,
        ]);

        return response()->json([
            'message' => 'Certificate attendance policy updated successfully.',
            'event_id' => $event->id,
            'total_event_days' => $totalDays,
            'certificate_controls' => $metadata['certificate_controls'],
        ]);
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

    private function normalizedControls(CertificateEvent $event): array
    {
        $metadata = is_array($event->metadata) ? $event->metadata : [];
        $controls = data_get($metadata, 'certificate_controls', []);

        return [
            'required_attendance_days_for_certificate' => isset($controls['required_attendance_days_for_certificate'])
                ? (int) $controls['required_attendance_days_for_certificate']
                : null,
            'winner_attendance_required_for_issuance' => (bool) ($controls['winner_attendance_required_for_issuance'] ?? false),
            'track_winner_attendance' => (bool) ($controls['track_winner_attendance'] ?? true),
            'multi_role_strategy' => (string) ($controls['multi_role_strategy'] ?? 'any_eligible_role'),
        ];
    }
}
