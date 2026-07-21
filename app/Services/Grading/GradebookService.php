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

    /* ------------------------------------- Basic ed, per section (a class) */

    /**
     * Which track a class grades on. Basic ed is authoritative: a basic-ed
     * section carries its grade level directly on sections.education_node_id
     * (higher-ed sections use program_id instead — "exactly one of the two is
     * set", per the 2026-07-20 migration). When that tag is present we trust it,
     * so a section that is between rosters (no active enrolments yet) still
     * grades on the basic track instead of silently falling through to higher ed.
     *
     * When the tag is absent (legacy sections the column never backfilled) we
     * fall back to the roster: any active student here enrolled under an
     * education node means a basic-ed grade level. That still wins over a stray
     * higher-ed student_enrollment_subjects row, so a basic student wrongly
     * materialised into one can't misroute the class and hide its grading scheme.
     * Defaults to higher when no signal is present.
     */
    public function classTrack(ClassModel $class): string
    {
        // Authoritative tag on the section itself.
        $sectionNode = DB::table('sections')->where('id', $class->section_id)->value('education_node_id');
        if ($sectionNode !== null) {
            return 'basic';
        }

        // Fallback: infer from the active roster.
        $basic = DB::table('student_enrollments')
            ->where('section_id', $class->section_id)
            ->whereNotNull('education_node_id')
            ->whereIn('status', ['enrolled', 'provisionally_enrolled'])
            ->exists();

        return $basic ? 'basic' : 'higher';
    }

    /**
     * The basic-ed context for a class: the section's education node, its academic
     * year, and the resolved grading scheme. Null when the section has no basic-ed
     * enrolments or no scheme is configured. A section is taken to be one grade
     * level, so the first enrolment's node represents the whole roster.
     *
     * @return array{node_id: int, academic_year_id: int, setting: GradingSetting}|null
     */
    public function basicContext(ClassModel $class): ?array
    {
        $rep = DB::table('student_enrollments')
            ->where('section_id', $class->section_id)
            ->whereNotNull('education_node_id')
            ->whereIn('status', ['enrolled', 'provisionally_enrolled'])
            ->orderBy('id')
            ->first(['education_node_id', 'academic_year_id']);

        if (! $rep) {
            return null;
        }

        $setting = $this->resolver->forNode((int) $class->school_id, (int) $rep->education_node_id);
        if (! $setting) {
            return null;
        }

        return [
            'node_id' => (int) $rep->education_node_id,
            'academic_year_id' => (int) $rep->academic_year_id,
            'setting' => $setting,
        ];
    }

    /**
     * Save draft scores for a basic-ed class in a grading period. Delegates to the
     * node-scoped saver using the section's node + the class's learning area.
     *
     * @param  ScoreMap  $scores
     */
    public function saveClassBasicScores(ClassModel $class, int $period, array $scores, ?int $recordedBy = null): void
    {
        $ctx = $this->basicContext($class);
        if (! $ctx) {
            return;
        }

        $this->saveNodeScores((int) $class->school_id, $ctx['node_id'], (int) $class->subject_id, $period, $scores, $recordedBy);
    }

    /**
     * Post finals for a basic-ed class/period into report_card_grades — scoped to
     * THIS class's section (not the whole grade level), so a teacher only grades
     * the students they teach. Only complete grades are written.
     *
     * @return array<int, GradeResult>
     */
    public function postClassBasic(ClassModel $class, int $period, ?int $recordedBy = null): array
    {
        $results = [];
        foreach ($this->computeClassBasic($class, $period) as $row) {
            $results[$row['student_id']] = $row['result'];

            if ($row['result']->isComplete && $row['result']->final !== null) {
                ReportCardGrade::updateOrCreate(
                    [
                        'student_id' => $row['student_id'],
                        'education_node_id' => $row['node_id'],
                        'subject_id' => (int) $class->subject_id,
                        'academic_year_id' => $row['academic_year_id'],
                        'grading_period' => $period,
                    ],
                    ['school_id' => (int) $class->school_id, 'final_grade' => $row['result']->final, 'recorded_by' => $recordedBy],
                );
            }
        }

        return $results;
    }

    /** @return array<int, GradeResult> */
    public function previewClassBasic(ClassModel $class, int $period): array
    {
        $results = [];
        foreach ($this->computeClassBasic($class, $period) as $row) {
            $results[$row['student_id']] = $row['result'];
        }

        return $results;
    }

    /**
     * Compute loop for a basic-ed class/period over the section's roster.
     *
     * @return list<array{student_id: int, node_id: int, academic_year_id: int, result: GradeResult}>
     */
    private function computeClassBasic(ClassModel $class, int $period): array
    {
        $ctx = $this->basicContext($class);
        if (! $ctx) {
            return [];
        }

        $setting = $ctx['setting'];
        $weights = $this->weights($setting);
        $attWeight = (float) $setting->attendance_weight;
        $expectedDays = $attWeight > 0 ? $this->expectedDaysForNode((int) $class->school_id, $ctx['node_id']) : 0;

        $students = DB::table('student_enrollments')
            ->where('section_id', $class->section_id)
            ->where('education_node_id', $ctx['node_id'])
            ->whereIn('status', ['enrolled', 'provisionally_enrolled'])
            ->get(['student_id', 'section_id', 'academic_year_id']);

        $out = [];
        foreach ($students as $e) {
            $scores = $this->scoresFor(
                [
                    'education_node_id' => $ctx['node_id'],
                    'subject_id' => (int) $class->subject_id,
                    'grading_period' => $period,
                    'student_id' => $e->student_id,
                ],
                array_keys($weights),
            );

            $rate = ($attWeight > 0 && $expectedDays > 0 && $e->section_id)
                ? $this->rates->dailyRate((int) $e->student_id, (int) $e->section_id, $expectedDays, (int) $e->academic_year_id)
                : null;

            $out[] = [
                'student_id' => (int) $e->student_id,
                'node_id' => $ctx['node_id'],
                'academic_year_id' => (int) $e->academic_year_id,
                'result' => $this->engine->compute($weights, $scores, (float) $setting->passing_mark, $attWeight, $rate),
            ];
        }

        return $out;
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
