<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Builder;

/**
 * Computes a student's attendance rate (0-100) for use as a grade input.
 *
 * How each status counts toward "attended" is a policy default, held in one map
 * so it is easy to change: present/late count full, half-day counts half, an
 * excused absence is neutral (does not penalise the rate), and an unexcused
 * absent counts nothing.
 *
 *   - daily (basic ed):  numerator = credit, denominator = the configured
 *                        expected school days for the period.
 *   - session (higher):  numerator = credit, denominator = the sessions the
 *                        student was marked for (one record per held session).
 */
class AttendanceRateService
{
    /** Fraction of "attended" each status is worth. */
    private const CREDIT = [
        AttendanceRecord::STATUS_PRESENT => 1.0,
        AttendanceRecord::STATUS_LATE => 1.0,
        AttendanceRecord::STATUS_HALF_DAY => 0.5,
        AttendanceRecord::STATUS_EXCUSED => 1.0,
        AttendanceRecord::STATUS_ABSENT => 0.0,
    ];

    /**
     * The attendance-credit policy, exposed read-only so reporting (the teacher
     * Attendance → Summary tab) scores a status exactly the way the gradebook
     * does. Without this the two surfaces could drift and show a teacher two
     * different percentages for the same student.
     *
     * @return array<string, float>
     */
    public static function creditMap(): array
    {
        return self::CREDIT;
    }

    /** What one status is worth toward "attended" (0.0 when unrecognised). */
    public static function creditFor(?string $status): float
    {
        return self::CREDIT[$status] ?? 0.0;
    }

    /**
     * Daily homeroom rate over a section, against the configured expected days.
     * Null when there is no denominator to divide by.
     */
    public function dailyRate(int $studentId, int $sectionId, int $expectedDays, ?int $academicYearId = null): ?float
    {
        if ($expectedDays <= 0) {
            return null;
        }

        $credit = $this->credit(
            AttendanceRecord::query()
                ->where('student_id', $studentId)
                ->where('scope', AttendanceRecord::SCOPE_DAILY)
                ->where('section_id', $sectionId)
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
        );

        // Cap at the expected days so make-up/duplicate marks can't exceed 100%.
        return round(min($credit, $expectedDays) / $expectedDays * 100, 2);
    }

    /**
     * Per-subject session rate over a class. The denominator is the number of
     * sessions this student was actually marked for. Null when none.
     */
    public function sessionRate(int $studentId, int $classId): ?float
    {
        $query = AttendanceRecord::query()
            ->where('student_id', $studentId)
            ->where('scope', AttendanceRecord::SCOPE_SESSION)
            ->where('class_id', $classId);

        $held = (clone $query)->count();
        if ($held <= 0) {
            return null;
        }

        return round($this->credit($query) / $held * 100, 2);
    }

    /** Sum the attendance credit for the records the query selects. */
    private function credit(Builder $query): float
    {
        return (float) $query->pluck('status')
            ->sum(fn ($status) => self::CREDIT[$status] ?? 0.0);
    }
}
