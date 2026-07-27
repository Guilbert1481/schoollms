<?php

namespace App\Services\Grading;

use App\Models\ClassModel;
use App\Models\ComponentScore;
use Illuminate\Support\Facades\DB;

/**
 * The single writer of component_scores. For one grade component in one class
 * context, it recomputes each student's score as
 *
 *     Σ(raw earned) ÷ Σ(highest possible) × 100
 *
 * summed across EVERY source that feeds the component — hand-entered activities
 * ({@see \App\Models\GradeActivity}), graded homework, and tests (F2F OMR + the
 * latest online attempt per test). Combining the sources here is what lets a
 * teacher mix manual and online work in one component without the sources
 * clobbering each other's score — the failure mode of three independent writers.
 *
 * {@see TestComponentFeed} and {@see HomeworkComponentFeed} are thin triggers
 * that delegate here; the manual entry path calls it too. A student with nothing
 * taken yet (possible ≤ 0) is left untouched — never written as a zero.
 */
class ComponentScoreRecomputer
{
    public function __construct(private GradebookService $gradebook) {}

    public function recompute(ClassModel $class, int $componentId, ?int $period = null): void
    {
        if ($componentId <= 0) {
            return;
        }

        if ($this->gradebook->classTrack($class) === 'basic') {
            $ctx = $this->gradebook->basicContext($class);
            if (! $ctx) {
                return;
            }
            $scope = [
                'track' => 'basic',
                'class_id' => (int) $class->id,
                'subject_id' => (int) $class->subject_id,
                'node_id' => (int) $ctx['node_id'],
                'period' => (int) ($period ?: 1),
            ];
            $studentIds = DB::table('student_enrollments')
                ->where('section_id', $class->section_id)->where('education_node_id', $scope['node_id'])
                ->whereIn('status', ['enrolled', 'provisionally_enrolled'])->pluck('student_id');
            $key = fn (int $sid) => [
                'student_id' => $sid, 'grade_component_id' => $componentId, 'class_id' => null,
                'subject_id' => $scope['subject_id'], 'education_node_id' => $scope['node_id'], 'grading_period' => $scope['period'],
            ];
        } else {
            $scope = [
                'track' => 'higher',
                'class_id' => (int) $class->id,
                'subject_id' => (int) $class->subject_id,
                'node_id' => null,
                'period' => null,
            ];
            $studentIds = DB::table('student_enrollment_subjects as ses')
                ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
                ->where('ses.class_id', $class->id)->pluck('e.student_id');
            $key = fn (int $sid) => [
                'student_id' => $sid, 'grade_component_id' => $componentId, 'class_id' => (int) $class->id,
                'subject_id' => $scope['subject_id'], 'education_node_id' => null, 'grading_period' => null,
            ];
        }

        foreach ($studentIds as $studentId) {
            $sid = (int) $studentId;

            [$me, $mp] = $this->manual($class, $componentId, $scope, $sid);
            [$he, $hp] = $this->homework($class, $componentId, $scope, $sid);
            [$te, $tp] = $this->tests($class, $componentId, $scope, $sid);

            $earned = $me + $he + $te;
            $possible = $mp + $hp + $tp;

            if ($possible <= 0) {
                continue; // nothing taken yet — leave any existing value alone, never zero it
            }

            ComponentScore::updateOrCreate($key($sid), [
                'school_id' => (int) $class->school_id,
                'score' => round($earned / $possible * 100, 2),
            ]);
        }
    }

    /* ------------------------------------------------------------------ */

    /**
     * @param  array{track: string, class_id: int, subject_id: int, node_id: int|null, period: int|null}  $scope
     * @return array{0: float, 1: float} [earned, possible]
     */
    private function manual(ClassModel $class, int $componentId, array $scope, int $studentId): array
    {
        $q = DB::table('grade_activity_scores as s')
            ->join('grade_activities as a', 'a.id', '=', 's.grade_activity_id')
            ->where('a.school_id', $class->school_id)
            ->where('a.grade_component_id', $componentId)
            ->where('s.student_id', $studentId)
            ->whereNotNull('s.raw_score');

        if ($scope['track'] === 'basic') {
            $q->whereNull('a.class_id')
                ->where('a.education_node_id', $scope['node_id'])
                ->where('a.subject_id', $scope['subject_id'])
                ->where('a.grading_period', $scope['period']);
        } else {
            $q->where('a.class_id', $scope['class_id']);
        }

        $row = $q->selectRaw('COALESCE(SUM(s.raw_score),0) AS earned, COALESCE(SUM(a.total_items),0) AS possible')->first();

        return [(float) $row->earned, (float) $row->possible];
    }

    /**
     * @param  array{track: string, class_id: int, subject_id: int, node_id: int|null, period: int|null}  $scope
     * @return array{0: float, 1: float}
     */
    private function homework(ClassModel $class, int $componentId, array $scope, int $studentId): array
    {
        $hw = DB::table('homework')->where('class_id', $class->id)->where('grade_component_id', $componentId);
        if ($scope['track'] === 'basic') {
            $hw->where('grading_period', $scope['period']);
        }
        $points = $hw->pluck('points', 'id');
        if ($points->isEmpty()) {
            return [0.0, 0.0];
        }

        $earned = 0.0;
        $possible = 0.0;
        foreach (DB::table('homework_submissions')
            ->where('student_id', $studentId)->whereIn('homework_id', $points->keys())->whereNotNull('score')
            ->get(['homework_id', 'score']) as $sub) {
            $earned += (float) $sub->score;
            $possible += (float) ($points[$sub->homework_id] ?? 0);
        }

        return [$earned, $possible];
    }

    /**
     * @param  array{track: string, class_id: int, subject_id: int, node_id: int|null, period: int|null}  $scope
     * @return array{0: float, 1: float}
     */
    private function tests(ClassModel $class, int $componentId, array $scope, int $studentId): array
    {
        $t = DB::table('tests')->where('class_id', $class->id)->where('grade_component_id', $componentId);
        if ($scope['track'] === 'basic') {
            $t->where('grading_period', $scope['period']);
        }
        $testIds = $t->pluck('id');
        if ($testIds->isEmpty()) {
            return [0.0, 0.0];
        }

        // F2F: sum the OMR results for this student's sheets.
        $omr = DB::table('omr_results as r')
            ->join('omr_sheets as s', 's.id', '=', 'r.omr_sheet_id')
            ->whereIn('s.test_id', $testIds)->where('s.student_id', $studentId)
            ->selectRaw('COALESCE(SUM(r.raw_score),0) AS earned, COALESCE(SUM(r.max_score),0) AS possible')->first();

        // Online: the student's LATEST submitted attempt per test (retakes → last one).
        $latestAttemptIds = DB::table('test_attempts')
            ->whereIn('test_id', $testIds)->where('student_id', $studentId)
            ->whereIn('status', ['submitted', 'graded'])
            ->groupBy('test_id')->selectRaw('MAX(id) AS id')->pluck('id');

        $online = DB::table('test_attempts')
            ->whereIn('id', $latestAttemptIds)
            ->selectRaw('COALESCE(SUM(raw_score),0) AS earned, COALESCE(SUM(max_score),0) AS possible')->first();

        return [(float) $omr->earned + (float) $online->earned, (float) $omr->possible + (float) $online->possible];
    }
}
