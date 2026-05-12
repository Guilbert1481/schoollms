<?php

namespace App\Modules\AcadEnrolment\Services\Contracts;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
use Illuminate\Support\Collection;

/**
 * Immutable input passed to every enrolment validator.
 *
 * `selectedClassIds` is the set of `classes.id` the student wants to
 * enrol in. For Basic Ed where students take a fixed section, this is
 * the auto-derived list of all classes inside the chosen section.
 */
class EnrolmentContext
{
    public function __construct(
        public readonly Student $student,
        public readonly Term $term,
        public readonly int $programId,
        public readonly string $educationLevel,
        public readonly string $programType,
        public readonly string $enrolleeType,
        public readonly Collection $selectedClassIds,
        public readonly ?int $sectionId = null,
        public readonly ?StudentEnrollment $enrollment = null,
        public readonly ?int $schoolId = null,
        public readonly ?int $homeSchoolId = null,
    ) {}

    public function effectiveSchoolId(): int
    {
        return $this->schoolId ?? (int) $this->student->school_id;
    }
}
