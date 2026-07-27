<?php

namespace App\Services\Grading;

use App\Models\ClassModel;
use App\Models\GradeComponent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Assembles one student's detailed grade ledger for a class (and, for basic ed,
 * a grading period) — every individual graded item behind the aggregate that the
 * gradebook shows. Each item becomes one dated line under its grade component:
 *
 *   - manual   : a hand-entered {@see \App\Models\GradeActivity} + this student's raw score.
 *   - homework : a graded submission (score ÷ points), dated when submitted.
 *   - test     : the student's OMR result or latest online attempt (raw ÷ max).
 *
 * Read-only and scoped to the class's resolved context, so a teacher only ever
 * sees the items for the students and level they teach.
 */
class StudentGradeLedger
{
    public function __construct(private GradebookService $gradebook, private GradingSchemeResolver $resolver) {}

    /**
     * @return array{
     *     components: Collection<int, GradeComponent>,
     *     lines: array<int, array{component_id:int, type:string, title:string, date:?string, raw:?float, total:?float, source:string}>,
     *     aggregates: array<int, float|null>,
     *     period: int|null,
     *     track: string
     * }|null
     */
    public function forStudent(ClassModel $class, int $studentId, ?int $period = null): ?array
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

        $components = $setting->components->sortBy('sort_order')->values();
        $componentIds = $components->pluck('id')->map(fn ($i) => (int) $i)->all();
        $typeById = $components->pluck('name', 'id');

        if ($componentIds === []) {
            return ['components' => $components, 'lines' => [], 'aggregates' => [], 'period' => $p, 'track' => $track];
        }

        $lines = array_merge(
            $this->manualLines($class, $componentIds, $studentId, $track, $nodeId, $p),
            $this->homeworkLines($class, $componentIds, $studentId, $track, $p),
            $this->testLines($class, $componentIds, $studentId, $track, $p),
        );

        foreach ($lines as &$line) {
            $line['type'] = (string) ($typeById[$line['component_id']] ?? '—');
        }
        unset($line);

        // Newest first; undated (never taken) lines sink to the bottom.
        usort($lines, fn ($a, $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));

        return [
            'components' => $components,
            'lines' => $lines,
            'aggregates' => $this->aggregates($class, $componentIds, $studentId, $track, $nodeId, $p),
            'period' => $p,
            'track' => $track,
        ];
    }

    /* ------------------------------------------------------------------ */

    /** @return array<int, array<string, mixed>> */
    private function manualLines(ClassModel $class, array $componentIds, int $studentId, string $track, ?int $nodeId, ?int $period): array
    {
        $q = DB::table('grade_activities as a')
            ->leftJoin('grade_activity_scores as s', function ($j) use ($studentId) {
                $j->on('s.grade_activity_id', '=', 'a.id')->where('s.student_id', '=', $studentId);
            })
            ->where('a.school_id', $class->school_id)
            ->whereIn('a.grade_component_id', $componentIds);

        if ($track === 'basic') {
            $q->whereNull('a.class_id')->where('a.education_node_id', $nodeId)
                ->where('a.subject_id', $class->subject_id)->where('a.grading_period', $period);
        } else {
            $q->where('a.class_id', $class->id);
        }

        return $q->get(['a.id', 'a.grade_component_id', 'a.title', 'a.total_items', 'a.activity_date', 's.raw_score'])
            ->map(fn ($r) => [
                'component_id' => (int) $r->grade_component_id,
                'type' => '',
                'title' => $r->title ?: 'Manual entry',
                'date' => $r->activity_date ? substr((string) $r->activity_date, 0, 10) : null,
                'raw' => $r->raw_score === null ? null : (float) $r->raw_score,
                'total' => (float) $r->total_items,
                'source' => 'manual',
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function homeworkLines(ClassModel $class, array $componentIds, int $studentId, string $track, ?int $period): array
    {
        $q = DB::table('homework as h')
            ->join('homework_submissions as hs', function ($j) use ($studentId) {
                $j->on('hs.homework_id', '=', 'h.id')->where('hs.student_id', '=', $studentId);
            })
            ->where('h.class_id', $class->id)
            ->whereIn('h.grade_component_id', $componentIds)
            ->whereNotNull('hs.score');

        if ($track === 'basic') {
            $q->where('h.grading_period', $period);
        }

        return $q->get(['h.id', 'h.grade_component_id', 'h.title', 'h.points', 'hs.score', 'hs.submitted_at', 'h.due_at'])
            ->map(fn ($r) => [
                'component_id' => (int) $r->grade_component_id,
                'type' => '',
                'title' => $r->title ?: 'Homework',
                'date' => substr((string) ($r->submitted_at ?: $r->due_at ?: ''), 0, 10) ?: null,
                'raw' => $r->score === null ? null : (float) $r->score,
                'total' => (float) $r->points,
                'source' => 'homework',
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function testLines(ClassModel $class, array $componentIds, int $studentId, string $track, ?int $period): array
    {
        $q = DB::table('tests')->where('class_id', $class->id)->whereIn('grade_component_id', $componentIds);
        if ($track === 'basic') {
            $q->where('grading_period', $period);
        }
        $tests = $q->get(['id', 'grade_component_id', 'title']);

        $lines = [];
        foreach ($tests as $test) {
            $omr = DB::table('omr_results as r')
                ->join('omr_sheets as s', 's.id', '=', 'r.omr_sheet_id')
                ->where('s.test_id', $test->id)->where('s.student_id', $studentId)
                ->selectRaw('COALESCE(SUM(r.raw_score),0) AS earned, COALESCE(SUM(r.max_score),0) AS possible, MAX(r.graded_at) AS at')
                ->first();

            $latestId = DB::table('test_attempts')
                ->where('test_id', $test->id)->where('student_id', $studentId)
                ->whereIn('status', ['submitted', 'graded'])->max('id');
            $online = $latestId
                ? DB::table('test_attempts')->where('id', $latestId)->first(['raw_score', 'max_score', 'submitted_at'])
                : null;

            $earned = (float) $omr->earned + (float) ($online->raw_score ?? 0);
            $possible = (float) $omr->possible + (float) ($online->max_score ?? 0);
            if ($possible <= 0) {
                continue; // student has no result for this test yet
            }

            $date = $online->submitted_at ?? $omr->at;
            $lines[] = [
                'component_id' => (int) $test->grade_component_id,
                'type' => '',
                'title' => $test->title ?: 'Test',
                'date' => $date ? substr((string) $date, 0, 10) : null,
                'raw' => $earned,
                'total' => $possible,
                'source' => 'test',
            ];
        }

        return $lines;
    }

    /**
     * The current per-component aggregate the gradebook shows (from component_scores).
     *
     * @return array<int, float|null>
     */
    private function aggregates(ClassModel $class, array $componentIds, int $studentId, string $track, ?int $nodeId, ?int $period): array
    {
        $q = DB::table('component_scores')
            ->where('student_id', $studentId)
            ->whereIn('grade_component_id', $componentIds);

        if ($track === 'basic') {
            $q->whereNull('class_id')->where('education_node_id', $nodeId)
                ->where('subject_id', $class->subject_id)->where('grading_period', $period);
        } else {
            $q->where('class_id', $class->id);
        }

        $found = $q->pluck('score', 'grade_component_id');

        $out = [];
        foreach ($componentIds as $cid) {
            $out[$cid] = isset($found[$cid]) ? (float) $found[$cid] : null;
        }

        return $out;
    }
}
