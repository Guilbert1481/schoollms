<?php

namespace App\Services\Grading;

use App\Models\ClassModel;
use App\Models\ComponentScore;
use App\Models\Homework;
use Illuminate\Support\Facades\DB;

/**
 * Rolls graded homework into the gradebook automatically. When a homework is
 * tagged with a grade component, this recomputes each student's score for that
 * component as
 *
 *     Σ(points earned) ÷ Σ(points possible) × 100
 *
 * over all their graded homework for the component, and writes it into
 * component_scores — the same table the gradebook reads — so the computed grade
 * folds homework in with no double entry. A component the teacher drives with
 * homework should not also be entered by hand; the last write wins.
 */
class HomeworkComponentFeed
{
    public function __construct(private GradebookService $gradebook) {}

    public function sync(Homework $homework): void
    {
        if (! $homework->grade_component_id) {
            return; // untagged homework does not feed the gradebook
        }

        $class = ClassModel::find($homework->class_id);
        if (! $class) {
            return;
        }

        $componentId = (int) $homework->grade_component_id;

        if ($this->gradebook->classTrack($class) === 'basic') {
            $ctx = $this->gradebook->basicContext($class);
            if (! $ctx) {
                return;
            }
            $period = (int) ($homework->grading_period ?: 1);

            $studentIds = DB::table('student_enrollments')
                ->where('section_id', $class->section_id)->where('education_node_id', $ctx['node_id'])
                ->whereIn('status', ['enrolled', 'provisionally_enrolled'])->pluck('student_id');

            $points = DB::table('homework')
                ->where('class_id', $class->id)->where('grade_component_id', $componentId)->where('grading_period', $period)
                ->pluck('points', 'id');

            $key = fn (int $sid) => [
                'student_id' => $sid, 'grade_component_id' => $componentId, 'class_id' => null,
                'subject_id' => (int) $class->subject_id, 'education_node_id' => $ctx['node_id'], 'grading_period' => $period,
            ];
        } else {
            $studentIds = DB::table('student_enrollment_subjects as ses')
                ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
                ->where('ses.class_id', $class->id)->pluck('e.student_id');

            $points = DB::table('homework')
                ->where('class_id', $class->id)->where('grade_component_id', $componentId)
                ->pluck('points', 'id');

            $key = fn (int $sid) => [
                'student_id' => $sid, 'grade_component_id' => $componentId, 'class_id' => (int) $class->id,
                'subject_id' => (int) $class->subject_id, 'education_node_id' => null, 'grading_period' => null,
            ];
        }

        $homeworkIds = $points->keys()->all();
        if ($homeworkIds === []) {
            return;
        }

        foreach ($studentIds as $studentId) {
            $earned = 0.0;
            $possible = 0.0;
            foreach (DB::table('homework_submissions')
                ->where('student_id', $studentId)->whereIn('homework_id', $homeworkIds)->whereNotNull('score')
                ->get(['homework_id', 'score']) as $sub) {
                $earned += (float) $sub->score;
                $possible += (float) ($points[$sub->homework_id] ?? 0);
            }

            if ($possible <= 0) {
                continue; // nothing graded for this student yet
            }

            ComponentScore::updateOrCreate($key((int) $studentId), [
                'school_id' => (int) $class->school_id,
                'score' => round($earned / $possible * 100, 2),
            ]);
        }
    }
}
