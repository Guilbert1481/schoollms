<?php

namespace App\Support;

use App\Models\Student;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The one answer to "may THIS logged-in student touch THIS test/attempt?" —
 * shared by every student-facing delivery of an online test (the standard take
 * screen and the Speed Dash game), so the two can never drift apart on
 * authorization.
 *
 * Enrolment resolution matches the assessments list: higher-ed via
 * student_enrollment_subjects, basic ed via the section's classes.
 */
class StudentTestAccess
{
    /** The current user's student record, or 403. */
    public function student(): Student
    {
        $student = Student::where('user_id', Auth::id())->first();
        abort_unless($student, 403);

        return $student;
    }

    /**
     * Full gate for a test page: same school, an online test, and the student
     * is enrolled in the test's class. Returns the student on success.
     */
    public function guardTest(Test $test, bool $mustBeOnline = true): Student
    {
        $student = $this->student();
        abort_unless((int) $test->school_id === (int) $student->school_id, 404);
        if ($mustBeOnline) {
            abort_unless(($test->settings->mode ?? null) === 'online', 404, 'This is not an online test.');
        }
        abort_unless($test->class_id && $this->enrolledIn($student, (int) $test->class_id), 403);

        return $student;
    }

    /** Gate for attempt endpoints: the attempt belongs to this student. */
    public function guardAttempt(TestAttempt $attempt): Student
    {
        $student = $this->student();
        abort_unless((int) $attempt->student_id === (int) $student->id, 403);
        abort_unless((int) $attempt->school_id === (int) $student->school_id, 404);

        return $student;
    }

    /** Every class the student belongs to — higher-ed via subject enrolments, basic ed via section. */
    public function classIds(Student $student): array
    {
        $higher = DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
            ->where('e.student_id', $student->id)->pluck('ses.class_id');

        $sectionIds = DB::table('student_enrollments')
            ->where('student_id', $student->id)
            ->whereIn('status', ['enrolled', 'provisionally_enrolled'])
            ->pluck('section_id')->filter();
        $basic = DB::table('classes')->whereIn('section_id', $sectionIds)->pluck('id');

        return $higher->merge($basic)->map(fn ($i) => (int) $i)->unique()->values()->all();
    }

    public function enrolledIn(Student $student, int $classId): bool
    {
        return in_array($classId, $this->classIds($student), true);
    }
}
