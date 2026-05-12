<?php

namespace App\Modules\AcadEnrolment\Services;

use App\Models\Student;
use App\Models\StudentEnrollmentSubject;
use Illuminate\Support\Facades\DB;

/**
 * Computes a student's academic standing.
 *
 * The full grade-weighted GPA pipeline isn't built yet; this service uses the
 * data that DOES exist (student_enrollment_subjects.grade) and a simple rule:
 *
 *   - GOOD     : no failing grades in the most recent N completed subjects,
 *                or no grade history at all (incoming/new students).
 *   - WARNING  : one failing grade in the most recent window.
 *   - PROBATION: two or more failing grades in the most recent window.
 *
 * Failing threshold (Philippine 5-point scale): grade > 3.0 (where 1.0 = best,
 * 5.0 = fail). Schools using percentages should override `failingThreshold()`.
 */
class AcademicStandingService
{
    public const STANDING_GOOD      = 'good';
    public const STANDING_WARNING   = 'warning';
    public const STANDING_PROBATION = 'probation';

    public function __construct(
        protected int $window = 12,
        protected float $failingThreshold = 3.0,
    ) {}

    public function standingFor(Student $student): string
    {
        $recent = StudentEnrollmentSubject::query()
            ->whereHas('enrollment', fn ($q) => $q->where('student_id', $student->id))
            ->whereNotNull('grade')
            ->orderByDesc('updated_at')
            ->limit($this->window)
            ->pluck('grade');

        if ($recent->isEmpty()) {
            return self::STANDING_GOOD;
        }

        $failing = $recent->filter(fn ($g) => (float) $g > $this->failingThreshold)->count();

        return match (true) {
            $failing >= 2 => self::STANDING_PROBATION,
            $failing === 1 => self::STANDING_WARNING,
            default        => self::STANDING_GOOD,
        };
    }

    public function isInGoodStanding(Student $student): bool
    {
        return $this->standingFor($student) !== self::STANDING_PROBATION;
    }
}
