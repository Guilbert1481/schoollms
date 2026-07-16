<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;

/**
 * Turns a single sighting from an unattended capture device (biometric terminal,
 * facial-recognition camera) into an attendance record through the ONE ingestion
 * funnel. This is the seam future hardware plugs into: a device reports
 * "identifier X was seen at time T for context C"; this resolves the identifier
 * to a student within the tenant, confirms the roster, and records with the
 * device's method.
 *
 * The context string matches the QR convention ("session:{classId}" /
 * "daily:{sectionId}") so both self-scan and device capture speak the same
 * shape. The tenant (school) is passed in explicitly — a device request has no
 * authenticated user for BelongsToSchool to infer it from — and every student
 * and context lookup is scoped to it, so a device can never write across tenants.
 *
 * Deliberately NOT here yet (needs real hardware + provisioning):
 *   - a device registry mapping a physical device to its context/location,
 *   - biometric/face template enrollment and matching (done on the device),
 *   - per-device credentials (the transport uses one shared key for now),
 *   - present/late derivation from the level's late rule (shared with the QR
 *     path — both currently record a plain check-in; wiring the level's
 *     late_after/grace is the next step for both).
 */
class DeviceAttendanceService
{
    /** Enrolment statuses that put a student on a live daily roster. */
    private const ROSTER_STATUSES = ['enrolled', 'provisionally_enrolled'];

    public function __construct(private AttendanceIngestionService $ingest) {}

    /**
     * @return array{ok: bool, message: string, status?: string}
     */
    public function record(int $schoolId, string $context, string $studentNumber, string $method, ?string $time = null): array
    {
        if (! in_array($method, [AttendanceRecord::METHOD_BIOMETRIC, AttendanceRecord::METHOD_FACIAL], true)) {
            return $this->fail('Unsupported device method.');
        }

        [$scope, $rawId] = array_pad(explode(':', $context, 2), 2, null);
        $contextId = (int) $rawId;

        if (! $contextId || ! in_array($scope, [AttendanceRecord::SCOPE_DAILY, AttendanceRecord::SCOPE_SESSION], true)) {
            return $this->fail('Unrecognized capture context.');
        }

        // Resolve the identifier to a student WITHIN this tenant.
        $student = DB::table('students')
            ->where('school_id', $schoolId)
            ->where('student_number', $studentNumber)
            ->first(['id']);

        if (! $student) {
            return $this->fail('No student matches that identifier at this school.');
        }

        $timeIn = $time ?: now()->format('H:i');

        return $scope === AttendanceRecord::SCOPE_SESSION
            ? $this->session($schoolId, $contextId, (int) $student->id, $method, $timeIn)
            : $this->daily($schoolId, $contextId, (int) $student->id, $method, $timeIn);
    }

    /* ------------------------------------------------------------------ */

    private function session(int $schoolId, int $classId, int $studentId, string $method, string $timeIn): array
    {
        $class = DB::table('classes')
            ->where('id', $classId)->where('school_id', $schoolId)
            ->first(['id', 'section_id', 'term_id']);

        if (! $class) {
            return $this->fail('This class was not found for this school.');
        }

        $rostered = DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
            ->where('ses.class_id', $classId)
            ->where('e.student_id', $studentId)
            ->exists();

        if (! $rostered) {
            return $this->fail('Student is not enrolled in this class.');
        }

        $record = $this->ingest->record([
            'school_id' => $schoolId,
            'scope' => AttendanceRecord::SCOPE_SESSION,
            'student_id' => $studentId,
            'class_id' => $classId,
            'section_id' => $class->section_id,
            'term_id' => $class->term_id,
            'attendance_date' => now()->toDateString(),
            'time_in' => $timeIn,
            'method' => $method,
        ]);

        return $this->ok($record->status);
    }

    private function daily(int $schoolId, int $sectionId, int $studentId, string $method, string $timeIn): array
    {
        $section = DB::table('sections')
            ->where('id', $sectionId)->where('school_id', $schoolId)
            ->first(['id', 'term_id']);

        $enrollment = DB::table('student_enrollments')
            ->where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->where('student_id', $studentId)
            ->whereIn('status', self::ROSTER_STATUSES)
            ->first(['academic_year_id']);

        if (! $section || ! $enrollment) {
            return $this->fail('Student is not enrolled in this section.');
        }

        $record = $this->ingest->record([
            'school_id' => $schoolId,
            'scope' => AttendanceRecord::SCOPE_DAILY,
            'student_id' => $studentId,
            'section_id' => $sectionId,
            'term_id' => $section->term_id,
            'academic_year_id' => $enrollment->academic_year_id,
            'attendance_date' => now()->toDateString(),
            'time_in' => $timeIn,
            'method' => $method,
        ]);

        return $this->ok($record->status);
    }

    /** @return array{ok: true, message: string, status: string} */
    private function ok(string $status): array
    {
        return ['ok' => true, 'message' => 'Attendance recorded.', 'status' => $status];
    }

    /** @return array{ok: false, message: string} */
    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }
}
