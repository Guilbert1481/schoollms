<?php

namespace App\Services\Grading;

use App\Models\ClassModel;
use App\Models\GradeActivity;
use App\Models\GradeActivityScore;
use Illuminate\Support\Facades\DB;

/**
 * Records a hand-entered graded item (the "Add Record" flow) — one activity for a
 * grade component plus each student's raw score — then recomputes the component so
 * the new marks fold into the gradebook immediately. All writes are validated
 * against the class's resolved scheme and roster, so a crafted POST can neither
 * score an off-scheme component nor a student who is not in the class.
 */
class GradeActivityService
{
    public function __construct(
        private GradebookService $gradebook,
        private GradingSchemeResolver $resolver,
        private ComponentScoreRecomputer $recomputer,
    ) {}

    /**
     * @param  array<int|string, mixed>  $rawByStudent  studentId => raw score (null/'' = not taken)
     * @return GradeActivity|null null when no scheme resolves or the component is off-scheme
     */
    public function record(ClassModel $class, int $componentId, int $totalItems, ?string $title, array $rawByStudent, ?int $period, ?int $recordedBy): ?GradeActivity
    {
        $track = $this->gradebook->classTrack($class);

        if ($track === 'basic') {
            $ctx = $this->gradebook->basicContext($class);
            if (! $ctx) {
                return null;
            }
            $setting = $ctx['setting'];
            $nodeId = (int) $ctx['node_id'];
            $p = (int) ($period ?: 1);
        } else {
            $setting = $this->resolver->forClass($class);
            if (! $setting) {
                return null;
            }
            $nodeId = null;
            $p = null;
        }

        // Guard: the component must belong to THIS level's scheme.
        $componentIds = $setting->components->pluck('id')->map(fn ($i) => (int) $i)->all();
        if (! in_array($componentId, $componentIds, true)) {
            return null;
        }

        // Guard: only students actually enrolled in this class may be scored.
        $rosterIds = $this->rosterIds($class, $track, $nodeId);

        $activity = DB::transaction(function () use ($class, $componentId, $totalItems, $title, $rawByStudent, $rosterIds, $track, $nodeId, $p, $recordedBy) {
            $activity = GradeActivity::create([
                'school_id' => (int) $class->school_id,
                'grade_component_id' => $componentId,
                'title' => $title ?: null,
                'total_items' => $totalItems,
                'activity_date' => now()->toDateString(),
                'class_id' => $track === 'basic' ? null : (int) $class->id,
                'subject_id' => (int) $class->subject_id,
                'education_node_id' => $nodeId,
                'grading_period' => $p,
                'recorded_by' => $recordedBy,
            ]);

            foreach ($rosterIds as $sid) {
                $raw = $rawByStudent[$sid] ?? null;
                $raw = ($raw === null || $raw === '') ? null : (float) $raw;

                GradeActivityScore::create([
                    'school_id' => (int) $class->school_id,
                    'grade_activity_id' => $activity->id,
                    'student_id' => (int) $sid,
                    'raw_score' => $raw,
                ]);
            }

            return $activity;
        });

        $this->recomputer->recompute($class, $componentId, $p);

        return $activity;
    }

    /** @return array<int, int> student ids enrolled in this class's roster */
    private function rosterIds(ClassModel $class, string $track, ?int $nodeId): array
    {
        if ($track === 'basic') {
            return DB::table('student_enrollments')
                ->where('section_id', $class->section_id)->where('education_node_id', $nodeId)
                ->whereIn('status', ['enrolled', 'provisionally_enrolled'])
                ->pluck('student_id')->map(fn ($i) => (int) $i)->all();
        }

        return DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
            ->where('ses.class_id', $class->id)
            ->pluck('e.student_id')->map(fn ($i) => (int) $i)->all();
    }
}
