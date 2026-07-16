<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\Attendance\AttendanceIngestionService;
use App\Services\Attendance\AttendanceSettingResolver;
use App\Services\Attendance\QrAttendanceTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Student endpoint hit by scanning the teacher's live QR. Validates the rotating
 * token, confirms the (authenticated) student is on the roster for that context,
 * and records a QR check-in through the one ingestion funnel. Never trusts the
 * scanned context beyond what the token signs and the roster confirms.
 */
class ScanAttendanceController extends Controller
{
    public function scan(Request $request, QrAttendanceTokenService $tokens, AttendanceIngestionService $ingest, AttendanceSettingResolver $settings)
    {
        $context = (string) $request->query('c', '');
        $token = (string) $request->query('t', '');

        if ($context === '' || $token === '' || ! $tokens->verify($context, $token, QrAttendanceTokenService::DEFAULT_WINDOW)) {
            return $this->result(false, 'This code has expired. Ask your teacher for the current one.');
        }

        $student = Student::where('user_id', Auth::id())->first();
        if (! $student) {
            return $this->result(false, 'No student profile is linked to your account.');
        }

        [$scope, $id] = array_pad(explode(':', $context, 2), 2, null);

        return match ($scope) {
            'session' => $this->recordSession($student, (int) $id, $ingest, $settings),
            'daily' => $this->recordDaily($student, (int) $id, $ingest, $settings),
            default => $this->result(false, 'Unrecognized code.'),
        };
    }

    /* ------------------------------------------------------------------ */

    private function recordSession(Student $student, int $classId, AttendanceIngestionService $ingest, AttendanceSettingResolver $settings)
    {
        $class = DB::table('classes')->where('id', $classId)->first(['id', 'section_id', 'term_id', 'subject_id']);
        if (! $class) {
            return $this->result(false, 'This class no longer exists.');
        }

        $rostered = DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
            ->where('ses.class_id', $classId)->where('e.student_id', $student->id)->exists();

        if (! $rostered) {
            return $this->result(false, "You're not enrolled in this class.");
        }

        // The level's late rule turns the scan time into present-vs-late.
        $setting = $settings->forContext((int) $student->school_id, 'session', $classId);

        $ingest->record([
            'scope' => 'session',
            'student_id' => $student->id,
            'class_id' => $classId,
            'section_id' => $class->section_id,
            'term_id' => $class->term_id,
            'attendance_date' => now()->toDateString(),
            'time_in' => now()->format('H:i'),
            'method' => 'qr',
            'late_after' => $setting->late_after,
            'grace_minutes' => (int) $setting->grace_minutes,
        ]);

        return $this->result(true, 'Checked in. Your attendance is recorded.');
    }

    private function recordDaily(Student $student, int $sectionId, AttendanceIngestionService $ingest, AttendanceSettingResolver $settings)
    {
        $section = DB::table('sections')->where('id', $sectionId)->first(['id', 'term_id']);
        $enrollment = DB::table('student_enrollments')
            ->where('section_id', $sectionId)->where('student_id', $student->id)
            ->whereIn('status', ['enrolled', 'provisionally_enrolled'])
            ->first(['academic_year_id']);

        if (! $section || ! $enrollment) {
            return $this->result(false, "You're not enrolled in this section.");
        }

        // The level's late rule turns the scan time into present-vs-late.
        $setting = $settings->forContext((int) $student->school_id, 'daily', $sectionId);

        $ingest->record([
            'scope' => 'daily',
            'student_id' => $student->id,
            'section_id' => $sectionId,
            'term_id' => $section->term_id,
            'academic_year_id' => $enrollment->academic_year_id,
            'attendance_date' => now()->toDateString(),
            'time_in' => now()->format('H:i'),
            'method' => 'qr',
            'late_after' => $setting->late_after,
            'grace_minutes' => (int) $setting->grace_minutes,
        ]);

        return $this->result(true, 'Checked in. Your attendance is recorded.');
    }

    private function result(bool $ok, string $message)
    {
        return view('student.attendance.scanned', ['ok' => $ok, 'message' => $message]);
    }
}
