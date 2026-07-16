<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use InvalidArgumentException;

/**
 * The single write path for attendance. Every capture method — manual roster
 * entry today, QR scan and biometric/facial devices later — funnels through
 * record() so the stored row is identical regardless of source and a student
 * can never end up with duplicate rows for the same context/date.
 *
 * Grade computation and reporting read attendance_records and stay blind to how
 * the mark was captured.
 */
class AttendanceIngestionService
{
    public function __construct(
        private AttendanceStatusResolver $statusResolver = new AttendanceStatusResolver,
    ) {}

    /**
     * Record (create or update) one attendance mark.
     *
     * Required keys:
     *   - scope            'daily' | 'session'
     *   - student_id       int
     *   - attendance_date  Y-m-d string (or Carbon)
     *   - section_id       required when scope=daily
     *   - class_id         required when scope=session
     *
     * Optional keys: class_session_id, section_id (session), academic_year_id,
     * term_id, time_in, time_out, status, method, remarks, recorded_by,
     * late_after (HH:MM), grace_minutes — the last two let device/QR capture
     * derive present-vs-late from the level's rule.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): AttendanceRecord
    {
        $scope = $data['scope'] ?? null;

        if (! in_array($scope, [AttendanceRecord::SCOPE_DAILY, AttendanceRecord::SCOPE_SESSION], true)) {
            throw new InvalidArgumentException("Attendance scope must be 'daily' or 'session'.");
        }

        if (empty($data['student_id']) || empty($data['attendance_date'])) {
            throw new InvalidArgumentException('Attendance requires student_id and attendance_date.');
        }

        $isDaily = $scope === AttendanceRecord::SCOPE_DAILY;

        if ($isDaily && empty($data['section_id'])) {
            throw new InvalidArgumentException('Daily attendance requires a section_id.');
        }

        if (! $isDaily && empty($data['class_id'])) {
            throw new InvalidArgumentException('Session attendance requires a class_id.');
        }

        $status = $this->resolveStatus($data);
        $method = $data['method'] ?? AttendanceRecord::METHOD_MANUAL;

        if (! in_array($method, AttendanceRecord::METHODS, true)) {
            throw new InvalidArgumentException("Unknown attendance method: {$method}.");
        }

        // Natural key: what identifies "this student's mark for this context/date".
        // Keyed on section_id for daily, class_id for session so the upsert is
        // stable regardless of the descriptive columns.
        $match = [
            'student_id' => (int) $data['student_id'],
            'attendance_date' => $data['attendance_date'],
            'scope' => $scope,
            'class_id' => $isDaily ? null : (int) $data['class_id'],
            'section_id' => $isDaily ? (int) $data['section_id'] : ($data['section_id'] ?? null),
        ];

        $values = [
            'class_session_id' => $data['class_session_id'] ?? null,
            'academic_year_id' => $data['academic_year_id'] ?? null,
            'term_id' => $data['term_id'] ?? null,
            'time_in' => $data['time_in'] ?? null,
            'time_out' => $data['time_out'] ?? null,
            'status' => $status,
            'method' => $method,
            'remarks' => $data['remarks'] ?? null,
            'recorded_by' => $data['recorded_by'] ?? null,
        ];

        // BelongsToSchool auto-scopes the lookup and stamps school_id on create,
        // so this can never read or write across tenants.
        return AttendanceRecord::updateOrCreate($match, $values);
    }

    /**
     * Explicit status wins. Otherwise derive it from the time-in against the
     * level's late rule (late_after + grace_minutes), when supplied.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveStatus(array $data): string
    {
        if (! empty($data['status'])) {
            if (! in_array($data['status'], AttendanceRecord::STATUSES, true)) {
                throw new InvalidArgumentException("Unknown attendance status: {$data['status']}.");
            }

            return $data['status'];
        }

        return $this->statusResolver->derive(
            $data['time_in'] ?? null,
            $data['late_after'] ?? null,
            (int) ($data['grace_minutes'] ?? 0),
        );
    }
}
