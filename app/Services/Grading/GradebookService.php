<?php

namespace App\Services\Grading;

use App\Models\ClassModel;
use App\Models\ComponentScore;
use App\Models\GradingSetting;
use App\Models\ReportCardGrade;
use App\Services\Attendance\AttendanceRateService;
use Illuminate\Support\Facades\DB;

/**
 * The gradebook: teachers save raw component scores as a draft anytime, and a
 * separate "post" step computes the final (via GradingEngine, with the
 * attendance rate) and writes it to the permanent record — but only for grades
 * that are complete, so a partial grade never lands on a transcript.
 *
 *   - higher ed:  scores keyed by class → posted to student_enrollment_subjects.
 *   - basic ed:   scores keyed by education node + learning area + grading
 *                 period → posted to report_card_grades.
 *
 * When no grading scheme resolves for the level, save/post are no-ops — a grade
 * is never computed against a guessed scheme.
 *
 * @phpstan-type ScoreMap array<int|string, array<int|string, mixed>>
 */
class GradebookService
{
    public function __construct(
        private GradingEngine $engine,
        private GradingSchemeResolver $resolver,
        private AttendanceRateService $rates,
    ) {}

    /* --------------------------------------------------------- Higher ed */

    /**
     * Save draft scores for a class. $scores = [studentId => [componentId => score|null]].
     *
     * @param  ScoreMap  $scores
     */
    public function saveClassScores(ClassModel $class, array $scores, ?int $recordedBy = null): void
    {
        $setting = $this->resolver->forClass($class);
        if (! $setting) {
            return;
        }

        $validIds = $setting->components->pluck('id')->map(fn ($i) => (int) $i)->all();

        foreach ($scores as $studentId => $byComponent) {
            foreach ($byComponent as $componentId => $score) {
                if (! in_array((int) $componentId, $validIds, true)) {
                    continue;
                }

                ComponentScore::updateOrCreate(
                    [
                        'student_id' => (int) $studentId,
                        'grade_component_id' => (int) $componentId,
                        'class_id' => (int) $class->id,
                        'subject_id' => $class->subject_id,
                        'education_node_id' => null,
                        'grading_period' => null,
                    ],
                    ['school_id' => $class->school_id, 'score' => $this->num($score), 'recorded_by' => $recordedBy],
                );
            }
        }
    }

    /**
     * Compute and post finals for a class into student_enrollment_subjects. Only
     * complete grades are written. Returns [studentId => GradeResult] for display.
     *
     * @return array<int, GradeResult>
     */
    public function postClass(ClassModel $class): array
    {
        $results = [];
        foreach ($this->computeClass($class) as $row) {
            $results[$row['student_id']] = $row['result'];

            if ($row['result']->isComplete && $row['result']->final !== null) {
                DB::table('student_enrollment_subjects')->where('id', $row['ses_id'])->update([
                    'final_grade' => $row['result']->final,
                    'grade' => $row['result']->final,
                    'status' => $row['result']->passed ? 'passed' : 'failed',
                    'updated_at' => now(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Compute (without writing) the current finals for a class, so the gradebook
     * can show the standing before a teacher posts.
     *
     * @return array<int, GradeResult>
     */
    public function previewClass(ClassModel $class): array
    {
        $results = [];
        foreach ($this->computeClass($class) as $row) {
            $results[$row['student_id']] = $row['result'];
        }

        return $results;
    }

    /**
     * The compute loop shared by post and preview: each roster row with its
     * enrolment-subject id and computed GradeResult. Empty when no scheme.
     *
     * @return list<array{student_id: int, ses_id: int, result: GradeResult}>
     */
    private function computeClass(ClassModel $class): array
    {
        $setting = $this->resolver->forClass($class);
        if (! $setting) {
            return [];
        }

        $weights = $this->weights($setting);
        $attWeight = (float) $setting->attendance_weight;

        $rows = DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
            ->where('ses.class_id', $class->id)
            ->get(['ses.id as ses_id', 'e.student_id']);

        $out = [];
        foreach ($rows as $row) {
            $scores = $this->scoresFor(
                ['class_id' => $class->id, 'student_id' => $row->student_id],
                array_keys($weights),
            );
            $rate = $attWeight > 0 ? $this->rates->sessionRate((int) $row->student_id, (int) $class->id) : null;

            $out[] = [
                'student_id' => (int) $row->student_id,
                'ses_id' => (int) $row->ses_id,
                'result' => $this->engine->compute($weights, $scores, (float) $setting->passing_mark, $attWeight, $rate),
            ];
        }

        return $out;
    }

    /* ---------------------------------------------------------- Basic ed */

    /**
     * Save draft scores for a basic-ed learning area in a grading period.
     *
     * @param  ScoreMap  $scores
     */
    public function saveNodeScores(int $schoolId, int $educationNodeId, int $subjectId, int $period, array $scores, ?int $recordedBy = null): void
    {
        $setting = $this->resolver->forNode($schoolId, $educationNodeId);
        if (! $setting) {
            return;
        }

        $validIds = $setting->components->pluck('id')->map(fn ($i) => (int) $i)->all();

        foreach ($scores as $studentId => $byComponent) {
            foreach ($byComponent as $componentId => $score) {
                if (! in_array((int) $componentId, $validIds, true)) {
                    continue;
                }

                ComponentScore::updateOrCreate(
                    [
                        'student_id' => (int) $studentId,
                        'grade_component_id' => (int) $componentId,
                        'class_id' => null,
                        'subject_id' => $subjectId,
                        'education_node_id' => $educationNodeId,
                        'grading_period' => $period,
                    ],
                    ['school_id' => $schoolId, 'score' => $this->num($score), 'recorded_by' => $recordedBy],
                );
            }
        }
    }

    /**
     * Compute and post finals for a basic-ed learning area/period into
     * report_card_grades. Only complete grades are written.
     *
     * @return array<int, GradeResult>
     */
    public function postNode(int $schoolId, int $educationNodeId, int $subjectId, int $period, int $academicYearId, ?int $recordedBy = null): array
    {
        $setting = $this->resolver->forNode($schoolId, $educationNodeId);
        if (! $setting) {
            return [];
        }

        $weights = $this->weights($setting);
        $attWeight = (float) $setting->attendance_weight;
        $expectedDays = $attWeight > 0 ? $this->expectedDaysForNode($schoolId, $educationNodeId) : 0;

        $enrollments = DB::table('student_enrollments')
            ->where('education_node_id', $educationNodeId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('status', ['enrolled', 'provisionally_enrolled'])
            ->get(['student_id', 'section_id']);

        $results = [];
        foreach ($enrollments as $e) {
            $scores = $this->scoresFor(
                [
                    'education_node_id' => $educationNodeId,
                    'subject_id' => $subjectId,
                    'grading_period' => $period,
                    'student_id' => $e->student_id,
                ],
                array_keys($weights),
            );

            $rate = ($attWeight > 0 && $expectedDays > 0 && $e->section_id)
                ? $this->rates->dailyRate((int) $e->student_id, (int) $e->section_id, $expectedDays, $academicYearId)
                : null;

            $result = $this->engine->compute($weights, $scores, (float) $setting->passing_mark, $attWeight, $rate);
            $results[(int) $e->student_id] = $result;

            if ($result->isComplete && $result->final !== null) {
                ReportCardGrade::updateOrCreate(
                    [
                        'student_id' => (int) $e->student_id,
                        'education_node_id' => $educationNodeId,
                        'subject_id' => $subjectId,
                        'academic_year_id' => $academicYearId,
                        'grading_period' => $period,
                    ],
                    ['school_id' => $schoolId, 'final_grade' => $result->final, 'recorded_by' => $recordedBy],
                );
            }
        }

        return $results;
    }

    /* ------------------------------------------------------------------ */

    /** @return array<int, float> [componentId => weight] */
    private function weights(GradingSetting $setting): array
    {
        return $setting->components
            ->pluck('weight', 'id')
            ->map(fn ($w) => (float) $w)
            ->all();
    }

    /**
     * Raw scores for a context, as [componentId => score|null] over the given
     * component ids (missing rows read as null → engine marks incomplete).
     *
     * @param  array<string, mixed>  $where
     * @param  array<int, int>  $componentIds
     * @return array<int, float|null>
     */
    private function scoresFor(array $where, array $componentIds): array
    {
        $found = ComponentScore::where($where)->pluck('score', 'grade_component_id');

        $out = [];
        foreach ($componentIds as $cid) {
            $score = $found[$cid] ?? null;
            $out[$cid] = $score === null ? null : (float) $score;
        }

        return $out;
    }

    private function expectedDaysForNode(int $schoolId, int $educationNodeId): int
    {
        $name = DB::table('education_nodes')->where('id', $educationNodeId)->value('name');
        if (! $name) {
            return 0;
        }

        $levelId = DB::table('academic_levels')
            ->where('school_id', $schoolId)->where('type', 'basic')->where('name', $name)
            ->value('id');

        if (! $levelId) {
            return 0;
        }

        return (int) DB::table('attendance_settings')
            ->where('school_id', $schoolId)->where('academic_level_id', $levelId)
            ->value('expected_days_per_period');
    }

    private function num(mixed $value): ?float
    {
        return ($value === null || $value === '') ? null : (float) $value;
    }
}
