<?php

namespace App\Support;

use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\Tests\TestAvailability;
use Illuminate\Support\Facades\DB;

/**
 * Virtual (derived) bell notifications for online tests. A row appears once a test
 * is published AND its window is open for the student, and disappears the moment the
 * student submits it or the window closes — so it stays red until Submit, without any
 * stored row or scheduled job. Recomputed on every page load, the same way
 * {@see BillingNotifications} works.
 */
class AssessmentNotifications
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forUser(?User $user): array
    {
        if (! $user || $user->role !== 'student') {
            return [];
        }

        $student = $user->student ?? null;
        if (! $student) {
            return [];
        }

        $classIds = self::classIds((int) $student->id);
        if ($classIds === []) {
            return [];
        }

        $availability = new TestAvailability;

        $tests = Test::whereIn('class_id', $classIds)
            ->where('status', 'published')
            ->whereHas('settings', fn ($q) => $q->where('mode', 'online'))
            ->with(['settings', 'subject'])
            ->get();

        // Which of these tests the student has already finished — resolved in ONE
        // query up front (not one per test) so the header stays cheap on every load.
        $submittedTestIds = array_flip(
            TestAttempt::where('student_id', $student->id)
                ->whereIn('test_id', $tests->pluck('id'))
                ->whereIn('status', ['submitted', 'graded'])
                ->pluck('test_id')
                ->all()
        );

        $rows = [];
        foreach ($tests as $test) {
            if (! $availability->isOpen($test)) {
                continue;
            }

            if (isset($submittedTestIds[$test->id])) {
                continue;
            }

            $closes = $availability->closesAt($test);

            $rows[] = [
                'id' => 'assessment-'.$test->id,
                'title' => 'Test open: '.$test->title,
                'message' => ($test->subject?->name ? $test->subject->name.' · ' : '')
                    .'Take it now'.($closes ? ' · closes '.$closes->format('M j, g:i A') : ''),
                'type' => 'assessment_open',
                'reference_id' => (int) $test->id,
                'read' => false,
                'urgent' => true,
                'days_left' => null,
            ];
        }

        return $rows;
    }

    /** Classes the student belongs to — higher-ed via subject enrolments, basic ed via section. */
    private static function classIds(int $studentId): array
    {
        $higher = DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
            ->where('e.student_id', $studentId)->pluck('ses.class_id');

        $sectionIds = DB::table('student_enrollments')
            ->where('student_id', $studentId)
            ->whereIn('status', ['enrolled', 'provisionally_enrolled'])
            ->pluck('section_id')->filter();
        $basic = DB::table('classes')->whereIn('section_id', $sectionIds)->pluck('id');

        return $higher->merge($basic)->map(fn ($i) => (int) $i)->unique()->values()->all();
    }
}
