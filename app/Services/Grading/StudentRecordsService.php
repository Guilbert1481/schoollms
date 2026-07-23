<?php

namespace App\Services\Grading;

use App\Models\Student;
use App\Models\User;
use App\Services\Dashboard\StudentDashboardService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the student Records page: one row per graded activity (online test
 * attempt, OMR-scanned paper test, or graded homework), plus the running
 * average computed from the principal's grading scheme (grade_components
 * weights). Attendance is deliberately excluded from the average — it lives in
 * grading_settings.attendance_weight, never in grade_components, so weighting
 * only component scores keeps it out by construction.
 */
class StudentRecordsService
{
    public function __construct(private StudentDashboardService $dashboard) {}

    /* ------------------------------------------------------------------
     |  Rows (union of the three graded sources)
     * -----------------------------------------------------------------*/

    /**
     * Every graded activity for the student, newest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(User $user): Collection
    {
        $student = $this->student($user);
        if (! $student) {
            return collect();
        }

        $rows = $this->onlineTestRows($student)
            ->merge($this->omrTestRows($student))
            ->merge($this->homeworkRows($student));

        $this->attachCompetencies($rows);

        return $rows
            ->sortByDesc(fn ($r) => optional($r['date'])->timestamp ?? 0)
            ->values();
    }

    /**
     * Apply the page filters (search, subject, quarter/term, date range).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $f
     * @return Collection<int, array<string, mixed>>
     */
    public function filter(Collection $rows, array $f): Collection
    {
        $q = mb_strtolower(trim((string) ($f['q'] ?? '')));

        return $rows
            ->when($q !== '', fn ($c) => $c->filter(function ($r) use ($q) {
                $hay = mb_strtolower(implode(' ', array_filter([
                    $r['title'], $r['subject'], $r['subject_code'], $r['competency'], $r['lesson'], $r['type'], $r['component'],
                ])));

                return str_contains($hay, $q);
            }))
            ->when(! empty($f['subject_id']), fn ($c) => $c->filter(
                fn ($r) => (int) $r['subject_id'] === (int) $f['subject_id']
            ))
            ->when(! empty($f['period']), fn ($c) => $c->filter(
                fn ($r) => (int) ($r['period'] ?? 0) === (int) $f['period']
            ))
            ->when(! empty($f['term_id']), fn ($c) => $c->filter(
                fn ($r) => (int) ($r['term_id'] ?? 0) === (int) $f['term_id']
            ))
            ->when(! empty($f['from']), fn ($c) => $c->filter(
                fn ($r) => $r['date'] && $r['date']->gte(Carbon::parse($f['from'])->startOfDay())
            ))
            ->when(! empty($f['to']), fn ($c) => $c->filter(
                fn ($r) => $r['date'] && $r['date']->lte(Carbon::parse($f['to'])->endOfDay())
            ))
            ->values();
    }

    /* ------------------------------------------------------------------
     |  Filter options
     * -----------------------------------------------------------------*/

    /** Distinct subjects across the student's graded rows and current classes. */
    public function subjectOptions(User $user): Collection
    {
        return $this->rows($user)
            ->filter(fn ($r) => $r['subject_id'])
            ->unique('subject_id')
            ->map(fn ($r) => (object) [
                'id' => (int) $r['subject_id'],
                'name' => $r['subject'],
                'code' => $r['subject_code'],
            ])
            ->sortBy('name')
            ->values();
    }

    /** Terms (semesters) the student's rows span — the non-basic-ed dropdown. */
    public function termOptions(User $user): Collection
    {
        $termIds = $this->rows($user)->pluck('term_id')->filter()->unique()->values();
        if ($termIds->isEmpty()) {
            return collect();
        }

        return DB::table('terms')
            ->whereIn('id', $termIds)
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'title', 'academic_year'])
            ->map(fn ($t) => (object) [
                'id' => (int) $t->id,
                'label' => trim(($t->title ?: $t->name).' '.($t->academic_year ? '('.$t->academic_year.')' : '')),
            ]);
    }

    /* ------------------------------------------------------------------
     |  Running average (grading scheme, attendance excluded)
     * -----------------------------------------------------------------*/

    /**
     * Running average across subjects using the principal's component weights:
     * per subject bucket, each component's scores average across periods, the
     * bucket grade is Σ(component avg × weight) ÷ Σ(weight), and the overall
     * value is the mean of bucket grades. Null until something is scored.
     */
    public function runningAverage(User $user): ?float
    {
        $student = $this->student($user);
        if (! $student) {
            return null;
        }

        $scores = DB::table('component_scores as cs')
            ->join('grade_components as gc', 'gc.id', '=', 'cs.grade_component_id')
            ->where('cs.student_id', $student->id)
            ->where('cs.school_id', $student->school_id)
            ->whereNotNull('cs.score')
            ->get(['cs.class_id', 'cs.subject_id', 'cs.grade_component_id', 'cs.score', 'gc.weight']);

        if ($scores->isEmpty()) {
            return null;
        }

        $buckets = $scores
            ->groupBy(fn ($r) => $r->class_id ? 'c'.$r->class_id : 's'.$r->subject_id)
            ->map(function ($bucket) {
                $weightedSum = 0.0;
                $totalWeight = 0.0;
                $bucket->groupBy('grade_component_id')->each(function ($rows) use (&$weightedSum, &$totalWeight) {
                    $weight = (float) $rows->first()->weight;
                    if ($weight <= 0) {
                        return;
                    }
                    $weightedSum += (float) $rows->avg('score') * $weight;
                    $totalWeight += $weight;
                });

                return $totalWeight > 0 ? $weightedSum / $totalWeight : null;
            })
            ->filter(fn ($v) => $v !== null);

        return $buckets->isEmpty() ? null : round($buckets->avg(), 2);
    }

    /** At-risk subjects, delegated so the Records modal matches the dashboard. */
    public function atRisk(User $user): array
    {
        return $this->dashboard->subjectsAtRisk($user);
    }

    /* ------------------------------------------------------------------
     |  Sources
     * -----------------------------------------------------------------*/

    /** Latest submitted/graded online attempt per test. */
    private function onlineTestRows(Student $student): Collection
    {
        return DB::table('test_attempts as ta')
            ->join('tests as t', 't.id', '=', 'ta.test_id')
            ->leftJoin('test_settings as s', 's.test_id', '=', 't.id')
            ->leftJoin('classes as c', 'c.id', '=', 't.class_id')
            ->leftJoin('subjects as sub', function ($join) {
                $join->on('sub.id', '=', DB::raw('COALESCE(t.subject_id, c.subject_id)'));
            })
            ->leftJoin('grade_components as gc', 'gc.id', '=', 't.grade_component_id')
            ->where('ta.student_id', $student->id)
            ->where('t.school_id', $student->school_id)
            ->whereIn('ta.status', ['submitted', 'graded'])
            ->orderBy('ta.id')
            ->get([
                'ta.id as attempt_id', 'ta.test_id', 'ta.raw_score', 'ta.max_score', 'ta.percentage',
                'ta.needs_manual', 'ta.submitted_at', 't.title', 't.grading_period', 't.created_at as test_created_at',
                's.assessment_type', 's.start_at', 'c.term_id',
                'sub.id as subject_id', 'sub.name as subject_name', 'sub.code as subject_code',
                'gc.name as component_name',
            ])
            ->groupBy('test_id')
            ->map(fn ($g) => $g->last())   // latest attempt per test
            ->values()
            ->map(fn ($r) => $this->row(
                key: 'test-'.$r->test_id,
                source: 'Online test',
                testId: (int) $r->test_id,
                title: (string) $r->title,
                r: $r,
                type: $this->typeLabel($r->assessment_type, 'Test'),
                assigned: $r->start_at ?? $r->test_created_at,
                taken: $r->submitted_at,
                raw: $r->raw_score,
                max: $r->max_score,
                pct: $r->percentage,
                status: $r->needs_manual ? 'provisional' : 'graded',
            ));
    }

    /** One current OMR result per paper-test sheet. */
    private function omrTestRows(Student $student): Collection
    {
        return DB::table('omr_sheets as sh')
            ->join('omr_results as r', 'r.omr_sheet_id', '=', 'sh.id')
            ->join('tests as t', 't.id', '=', 'sh.test_id')
            ->leftJoin('test_settings as s', 's.test_id', '=', 't.id')
            ->leftJoin('classes as c', 'c.id', '=', 't.class_id')
            ->leftJoin('subjects as sub', function ($join) {
                $join->on('sub.id', '=', DB::raw('COALESCE(t.subject_id, c.subject_id)'));
            })
            ->leftJoin('grade_components as gc', 'gc.id', '=', 't.grade_component_id')
            ->where('sh.student_id', $student->id)
            ->where('sh.school_id', $student->school_id)
            ->get([
                'sh.test_id', 'r.raw_score', 'r.max_score', 'r.percentage', 'r.graded_at',
                't.title', 't.grading_period', 't.created_at as test_created_at',
                's.assessment_type', 's.start_at', 'c.term_id',
                'sub.id as subject_id', 'sub.name as subject_name', 'sub.code as subject_code',
                'gc.name as component_name',
            ])
            ->map(fn ($r) => $this->row(
                key: 'omr-'.$r->test_id,
                source: 'Paper test',
                testId: (int) $r->test_id,
                title: (string) $r->title,
                r: $r,
                type: $this->typeLabel($r->assessment_type, 'Test'),
                assigned: $r->start_at ?? $r->test_created_at,
                taken: $r->graded_at,
                raw: $r->raw_score,
                max: $r->max_score,
                pct: $r->percentage,
                status: 'graded',
            ));
    }

    /** Graded homework submissions. */
    private function homeworkRows(Student $student): Collection
    {
        return DB::table('homework_submissions as hs')
            ->join('homework as h', 'h.id', '=', 'hs.homework_id')
            ->leftJoin('classes as c', 'c.id', '=', 'h.class_id')
            ->leftJoin('subjects as sub', 'sub.id', '=', 'c.subject_id')
            ->leftJoin('grade_components as gc', 'gc.id', '=', 'h.grade_component_id')
            ->where('hs.student_id', $student->id)
            ->where('h.school_id', $student->school_id)
            ->whereNotNull('hs.score')
            ->get([
                'h.id as homework_id', 'h.title', 'h.points', 'h.due_at', 'h.grading_period',
                'h.created_at as hw_created_at', 'hs.score', 'hs.submitted_at', 'c.term_id',
                'sub.id as subject_id', 'sub.name as subject_name', 'sub.code as subject_code',
                'gc.name as component_name',
            ])
            ->map(fn ($r) => $this->row(
                key: 'hw-'.$r->homework_id,
                source: 'Homework',
                testId: null,
                title: (string) $r->title,
                r: $r,
                type: 'Homework',
                assigned: $r->due_at ?? $r->hw_created_at,
                taken: $r->submitted_at,
                raw: $r->score,
                max: $r->points,
                pct: ((float) $r->points) > 0 ? round((float) $r->score / (float) $r->points * 100, 2) : null,
                status: 'graded',
            ));
    }

    /** Shared row shape for the table and the detail modal. */
    private function row(
        string $key, string $source, ?int $testId, string $title, object $r, string $type,
        ?string $assigned, ?string $taken, $raw, $max, $pct, string $status,
    ): array {
        return [
            'key' => $key,
            'source' => $source,
            'test_id' => $testId,
            'title' => $title,
            'subject' => $r->subject_name ?? '—',
            'subject_code' => $r->subject_code ?? null,
            'subject_id' => $r->subject_id ? (int) $r->subject_id : null,
            'competency' => null,   // filled by attachCompetencies() for tests
            'lesson' => null,
            'type' => $type,
            'component' => $r->component_name ?? null,
            'period' => $r->grading_period ?? null,
            'term_id' => $r->term_id ?? null,
            'date' => $assigned ? Carbon::parse($assigned) : null,
            'taken_at' => $taken ? Carbon::parse($taken) : null,
            'raw' => $raw !== null ? (float) $raw : null,
            'max' => $max !== null ? (float) $max : null,
            'pct' => $pct !== null ? (float) $pct : null,
            'status' => $status,
        ];
    }

    /** 'long_test' → 'Long Test'; null → the fallback. */
    private function typeLabel(?string $assessmentType, string $fallback): string
    {
        return $assessmentType ? ucwords(str_replace('_', ' ', $assessmentType)) : $fallback;
    }

    /**
     * Resolve competency + lesson labels for test rows via test_sources
     * (tests carry no competency columns — the link is the source rows).
     *
     * @param  Collection<int, array<string, mixed>>  $rows  mutated in place
     */
    private function attachCompetencies(Collection $rows): void
    {
        $testIds = $rows->pluck('test_id')->filter()->unique()->values();
        if ($testIds->isEmpty()) {
            return;
        }

        $sources = DB::table('test_sources')
            ->whereIn('test_id', $testIds)
            ->whereIn('source_type', ['competency', 'lesson'])
            ->get(['test_id', 'source_type', 'source_id']);

        $competencies = DB::table('competencies')
            ->whereIn('id', $sources->where('source_type', 'competency')->pluck('source_id')->unique())
            ->pluck('name', 'id');
        $lessons = DB::table('lessons')
            ->whereIn('id', $sources->where('source_type', 'lesson')->pluck('source_id')->unique())
            ->pluck('name', 'id');

        // Lessons implied by competencies (competencies.lesson_id) fill the
        // lesson column when the test wasn't sourced from a lesson directly.
        $competencyLessons = DB::table('competencies')
            ->whereIn('id', $sources->where('source_type', 'competency')->pluck('source_id')->unique())
            ->whereNotNull('lesson_id')
            ->pluck('lesson_id', 'id');
        $impliedLessonNames = DB::table('lessons')
            ->whereIn('id', $competencyLessons->values()->unique())
            ->pluck('name', 'id');

        $byTest = $sources->groupBy('test_id');

        $rows->transform(function ($row) use ($byTest, $competencies, $lessons, $competencyLessons, $impliedLessonNames) {
            if (! $row['test_id'] || ! $byTest->has($row['test_id'])) {
                return $row;
            }
            $src = $byTest[$row['test_id']];

            $comp = $src->where('source_type', 'competency')->pluck('source_id')
                ->map(fn ($id) => $competencies[$id] ?? null)->filter()->unique()->values();
            $less = $src->where('source_type', 'lesson')->pluck('source_id')
                ->map(fn ($id) => $lessons[$id] ?? null)->filter()->unique()->values();

            if ($less->isEmpty()) {
                $less = $src->where('source_type', 'competency')->pluck('source_id')
                    ->map(fn ($id) => isset($competencyLessons[$id]) ? ($impliedLessonNames[$competencyLessons[$id]] ?? null) : null)
                    ->filter()->unique()->values();
            }

            $row['competency'] = $this->joinLabels($comp);
            $row['lesson'] = $this->joinLabels($less);

            return $row;
        });
    }

    /** "A, B +2 more" style label join. */
    private function joinLabels(Collection $labels): ?string
    {
        if ($labels->isEmpty()) {
            return null;
        }
        $shown = $labels->take(2)->implode(', ');
        $extra = $labels->count() - 2;

        return $extra > 0 ? $shown.' +'.$extra.' more' : $shown;
    }

    private function student(User $user): ?Student
    {
        return Student::query()
            ->where('user_id', $user->id)
            ->where('school_id', $user->school_id)
            ->first();
    }
}
