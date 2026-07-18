<?php

namespace App\Services\Grading;

use App\Models\ClassModel;
use App\Models\ComponentScore;
use App\Models\Test;
use Illuminate\Support\Facades\DB;

/**
 * Rolls OMR-scanned test scores into the gradebook automatically — the test-side
 * twin of {@see HomeworkComponentFeed}. When a test is tagged with a grade
 * component, this recomputes each student's score for that component as
 *
 *     Σ(raw_score) ÷ Σ(max_score) × 100
 *
 * over every graded test they have sat for that component, and writes it into
 * component_scores — the same table the gradebook reads — so a scanned answer sheet
 * turns into a grade with no re-keying.
 *
 * Scores come from omr_results (one per sheet, and a re-scan overwrites its result
 * in place), so a corrected scan is picked up on the next sync. A component the
 * teacher drives with tests should not also be entered by hand; the last write wins.
 */
class TestComponentFeed
{
    public function __construct(private GradebookService $gradebook) {}

    public function sync(Test $test): void
    {
        if (! $test->grade_component_id) {
            return; // untagged test does not feed the gradebook
        }

        $class = $test->class_id ? ClassModel::find($test->class_id) : null;
        if (! $class) {
            return; // a personal test belongs to no class, so it has no gradebook
        }

        $componentId = (int) $test->grade_component_id;

        if ($this->gradebook->classTrack($class) === 'basic') {
            $ctx = $this->gradebook->basicContext($class);
            if (! $ctx) {
                return;
            }
            $period = (int) ($test->grading_period ?: 1);

            $studentIds = DB::table('student_enrollments')
                ->where('section_id', $class->section_id)->where('education_node_id', $ctx['node_id'])
                ->whereIn('status', ['enrolled', 'provisionally_enrolled'])->pluck('student_id');

            $testIds = DB::table('tests')
                ->where('class_id', $class->id)->where('grade_component_id', $componentId)
                ->where('grading_period', $period)->pluck('id');

            $key = fn (int $sid) => [
                'student_id' => $sid, 'grade_component_id' => $componentId, 'class_id' => null,
                'subject_id' => (int) $class->subject_id, 'education_node_id' => $ctx['node_id'], 'grading_period' => $period,
            ];
        } else {
            $studentIds = DB::table('student_enrollment_subjects as ses')
                ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
                ->where('ses.class_id', $class->id)->pluck('e.student_id');

            $testIds = DB::table('tests')
                ->where('class_id', $class->id)->where('grade_component_id', $componentId)->pluck('id');

            $key = fn (int $sid) => [
                'student_id' => $sid, 'grade_component_id' => $componentId, 'class_id' => (int) $class->id,
                'subject_id' => (int) $class->subject_id, 'education_node_id' => null, 'grading_period' => null,
            ];
        }

        if ($testIds->isEmpty()) {
            return;
        }

        foreach ($studentIds as $studentId) {
            $totals = DB::table('omr_results as r')
                ->join('omr_sheets as s', 's.id', '=', 'r.omr_sheet_id')
                ->whereIn('s.test_id', $testIds)
                ->where('s.student_id', $studentId)
                ->selectRaw('COALESCE(SUM(r.raw_score), 0) AS earned, COALESCE(SUM(r.max_score), 0) AS possible')
                ->first();

            $possible = (float) ($totals->possible ?? 0);

            if ($possible <= 0) {
                continue; // nothing scanned for this student yet
            }

            ComponentScore::updateOrCreate($key((int) $studentId), [
                'school_id' => (int) $class->school_id,
                'score' => round((float) $totals->earned / $possible * 100, 2),
            ]);
        }
    }
}
