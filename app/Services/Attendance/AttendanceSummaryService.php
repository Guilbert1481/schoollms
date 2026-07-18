<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use App\Models\StudentEnrollment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregation behind Teacher → Attendance → Summary.
 *
 * One pattern serves basic ed and non-basic ed: the teacher picks an academic
 * year, grade level, section and duration, and the source dropdown decides what
 * is being counted —
 *
 *   - Homeroom  → `daily` records keyed on the section. Denominator = the school
 *                 days attendance was actually taken.
 *   - A subject → `session` records keyed on the class. Denominator = the class
 *                 meetings that were actually marked.
 *
 * The denominator is deliberately "what was marked", not a configured expected
 * day count: there is no per-week expected-days setting, so dividing by marked
 * days is the only figure that cannot overstate a student's attendance. Status
 * credit comes from AttendanceRateService so this tab and the gradebook can
 * never disagree about what a percentage means.
 */
class AttendanceSummaryService
{
    public const DURATION_WEEKLY = 'weekly';

    public const DURATION_MONTHLY = 'monthly';

    public const DURATION_TERM = 'term';

    /** Duration options, in the order the dropdown shows them. */
    public const DURATIONS = [
        self::DURATION_WEEKLY => 'Weekly',
        self::DURATION_MONTHLY => 'Monthly',
        self::DURATION_TERM => 'Per term',
    ];

    /** Source sentinel: the section's daily homeroom register. */
    public const SOURCE_HOMEROOM = 'homeroom';

    /** Source sentinel: every subject this teacher teaches in the section. */
    public const SOURCE_ALL = 'all';

    /** Enrolment statuses that put a student on a live roster. */
    private const ROSTER_STATUSES = [
        StudentEnrollment::STATUS_ENROLLED,
        StudentEnrollment::STATUS_PROVISIONALLY_ENROLLED,
    ];

    /**
     * Every section this teacher may report on — advised or taught — carrying the
     * academic year, education level and grade label the filters need.
     *
     * This is the single source of truth for BOTH the dropdown options and the
     * authorisation check, so a teacher can never be offered (or hand-craft) a
     * section they have no claim to.
     *
     * @return Collection<int, object>
     */
    public function sectionsFor(int $userId, int $schoolId): Collection
    {
        return DB::table('sections as s')
            ->join('terms as t', 't.id', '=', 's.term_id')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 't.academic_year_id')
            ->where('s.school_id', $schoolId)
            ->where(function ($q) use ($userId) {
                $q->where('s.adviser_id', $userId)
                    ->orWhereExists(fn ($sub) => $sub->select(DB::raw(1))
                        ->from('classes as c')
                        ->whereColumn('c.section_id', 's.id')
                        ->where('c.teacher_id', $userId));
            })
            ->orderBy('s.year_level')
            ->orderBy('s.name')
            ->get([
                's.id', 's.name', 's.year_level', 's.term_id', 's.adviser_id',
                't.education_level', 't.academic_year_id',
                't.start_date as term_start', 't.end_date as term_end',
                'ay.name as academic_year_name',
                'ay.start_date as ay_start', 'ay.end_date as ay_end',
            ])
            ->map(function ($s) use ($userId) {
                $s->is_adviser = (int) $s->adviser_id === $userId;
                $s->is_basic = $s->education_level === 'basic_ed';
                $s->level_label = $this->gradeLabel($s->year_level, $s->is_basic);

                return $s;
            });
    }

    /** "Grade 5" for basic ed, "Year 3" for everything else. */
    public function gradeLabel(?int $yearLevel, bool $isBasic): string
    {
        if ($yearLevel === null) {
            return 'Ungraded';
        }

        return ($isBasic ? 'Grade ' : 'Year ').$yearLevel;
    }

    /**
     * The classes this teacher teaches in a section, for the subject dropdown.
     *
     * @return Collection<int, object>
     */
    public function classesFor(int $userId, int $schoolId, int $sectionId): Collection
    {
        return DB::table('classes as c')
            ->leftJoin('subjects as sub', 'sub.id', '=', 'c.subject_id')
            ->where('c.school_id', $schoolId)
            ->where('c.teacher_id', $userId)
            ->where('c.section_id', $sectionId)
            ->orderBy('sub.name')
            ->get(['c.id', 'c.code', 'sub.name as subject_name'])
            ->map(function ($c) {
                $c->label = $c->subject_name ?: $c->code;

                return $c;
            });
    }

    /**
     * Build the summary for one section.
     *
     * @param  object  $section  a row from sectionsFor() — already ownership-checked
     * @param  string  $source  SOURCE_HOMEROOM, SOURCE_ALL, or a class id as a string
     * @param  Collection<int, object>  $classes  the teacher's classes in this section
     * @return array<string, mixed>
     */
    public function summarize(object $section, string $source, string $duration, Collection $classes, int $schoolId): array
    {
        [$scope, $classIds] = $this->resolveScope($source, $classes);

        [$start, $end] = $this->window($section);
        $terms = $this->termsFor($schoolId, $section);

        $buckets = $this->buildBuckets($duration, $start, $end, $terms);
        $roster = $this->roster($section, $scope, $classIds, $schoolId);
        $records = $this->records($section, $scope, $classIds, $schoolId, $start, $end);

        return $this->rollUp($roster, $records, $buckets, $duration, $scope, $start, $end);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Translate the source dropdown into a record scope + the class ids to read.
     *
     * @return array{0: string, 1: int[]}
     */
    private function resolveScope(string $source, Collection $classes): array
    {
        if ($source === self::SOURCE_HOMEROOM) {
            return [AttendanceRecord::SCOPE_DAILY, []];
        }

        if ($source === self::SOURCE_ALL) {
            return [AttendanceRecord::SCOPE_SESSION, $classes->pluck('id')->map(fn ($id) => (int) $id)->all()];
        }

        // A specific class — only honoured when it is one of the teacher's own.
        $id = (int) $source;
        $owned = $classes->firstWhere('id', $id);

        return $owned
            ? [AttendanceRecord::SCOPE_SESSION, [$id]]
            : [AttendanceRecord::SCOPE_SESSION, $classes->pluck('id')->map(fn ($i) => (int) $i)->all()];
    }

    /**
     * The reporting window: the section's term, falling back to its academic
     * year. Clamped to today so the table never renders empty future buckets.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function window(object $section): array
    {
        $start = $section->term_start ?? $section->ay_start;
        $end = $section->term_end ?? $section->ay_end;

        $start = $start ? Carbon::parse($start)->startOfDay() : now()->startOfYear();
        $end = $end ? Carbon::parse($end)->endOfDay() : now()->endOfYear();

        $today = now()->endOfDay();
        if ($end->gt($today)) {
            $end = $today;
        }

        // A term that has not started yet would invert the window.
        if ($end->lt($start)) {
            $end = $start->copy()->endOfDay();
        }

        return [$start, $end];
    }

    /**
     * Dated terms for this section's academic year and education level — the
     * buckets behind the "Per term" duration.
     *
     * @return Collection<int, object>
     */
    private function termsFor(int $schoolId, object $section): Collection
    {
        return DB::table('terms')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $section->academic_year_id)
            ->where('education_level', $section->education_level)
            ->orderBy('start_date')
            ->get(['id', 'term', 'name', 'start_date', 'end_date']);
    }

    /**
     * Ordered time buckets across the window.
     *
     * @param  Collection<int, object>  $terms
     * @return array<int, array<string, mixed>>
     */
    private function buildBuckets(string $duration, Carbon $start, Carbon $end, Collection $terms): array
    {
        if ($duration === self::DURATION_TERM) {
            $buckets = [];
            foreach ($terms as $t) {
                if (! $t->start_date || ! $t->end_date) {
                    continue;
                }
                $s = Carbon::parse($t->start_date)->startOfDay();
                $e = Carbon::parse($t->end_date)->endOfDay();
                if ($e->lt($start) || $s->gt($end)) {
                    continue;
                }
                $buckets[] = [
                    'key' => 'term-'.$t->id,
                    'label' => $t->name ?: $t->term,
                    'start' => $s->lt($start) ? $start->copy() : $s,
                    'end' => $e->gt($end) ? $end->copy() : $e,
                ];
            }

            // No dated term covers the window — report it as one span rather
            // than inventing boundaries the school never defined.
            return $buckets ?: [[
                'key' => 'whole',
                'label' => 'Whole period',
                'start' => $start->copy(),
                'end' => $end->copy(),
            ]];
        }

        $weekly = $duration === self::DURATION_WEEKLY;
        $cursor = $weekly ? $start->copy()->startOfWeek() : $start->copy()->startOfMonth();

        $buckets = [];
        while ($cursor->lte($end)) {
            $bucketEnd = $weekly ? $cursor->copy()->endOfWeek() : $cursor->copy()->endOfMonth();
            $buckets[] = [
                'key' => $cursor->format('Y-m-d'),
                'label' => $weekly ? $cursor->format('M j') : $cursor->format('M Y'),
                'start' => $cursor->copy(),
                'end' => $bucketEnd,
            ];
            $cursor = $weekly ? $cursor->addWeek() : $cursor->addMonth();
        }

        return $buckets;
    }

    /** Which bucket a date falls in, or null when it falls outside them all. */
    private function bucketKeyFor(string $duration, Carbon $date, array $buckets): ?string
    {
        if ($duration === self::DURATION_WEEKLY) {
            return $date->copy()->startOfWeek()->format('Y-m-d');
        }

        if ($duration === self::DURATION_MONTHLY) {
            return $date->copy()->startOfMonth()->format('Y-m-d');
        }

        foreach ($buckets as $b) {
            if ($date->gte($b['start']) && $date->lte($b['end'])) {
                return $b['key'];
            }
        }

        return null;
    }

    /**
     * Students to show. Homeroom reads the section's enrolment; a subject view
     * reads the class rosters, so a student who joined one class but not another
     * is not reported as absent from a class they were never in.
     *
     * @param  int[]  $classIds
     * @return Collection<int, object>
     */
    private function roster(object $section, string $scope, array $classIds, int $schoolId): Collection
    {
        if ($scope === AttendanceRecord::SCOPE_SESSION) {
            if ($classIds === []) {
                return collect();
            }

            // The pivot keys classes as `class_model_id` (Laravel derives it from
            // the ClassModel class name), not `class_id`.
            return DB::table('class_student as cs')
                ->join('students as st', 'st.id', '=', 'cs.student_id')
                ->whereIn('cs.class_model_id', $classIds)
                ->where('st.school_id', $schoolId)
                ->distinct()
                ->orderBy('st.last_name')
                ->orderBy('st.first_name')
                ->get(['st.id', 'st.first_name', 'st.last_name', 'st.student_number']);
        }

        return DB::table('student_enrollments as se')
            ->join('students as st', 'st.id', '=', 'se.student_id')
            ->where('se.section_id', $section->id)
            ->whereIn('se.status', self::ROSTER_STATUSES)
            ->where('st.school_id', $schoolId)
            ->distinct()
            ->orderBy('st.last_name')
            ->orderBy('st.first_name')
            ->get(['st.id', 'st.first_name', 'st.last_name', 'st.student_number']);
    }

    /**
     * Raw marks in the window. Scoped by school_id explicitly because this uses
     * the query builder, which does not carry the BelongsToSchool global scope.
     *
     * @param  int[]  $classIds
     * @return Collection<int, object>
     */
    private function records(object $section, string $scope, array $classIds, int $schoolId, Carbon $start, Carbon $end): Collection
    {
        if ($scope === AttendanceRecord::SCOPE_SESSION && $classIds === []) {
            return collect();
        }

        return DB::table('attendance_records')
            ->where('school_id', $schoolId)
            ->where('scope', $scope)
            ->when(
                $scope === AttendanceRecord::SCOPE_DAILY,
                fn ($q) => $q->where('section_id', $section->id),
                fn ($q) => $q->whereIn('class_id', $classIds)
            )
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get(['student_id', 'attendance_date', 'status']);
    }

    /**
     * Fold the marks into per-student-per-bucket cells, per-student totals and a
     * section-wide trend.
     *
     * @param  Collection<int, object>  $roster
     * @param  Collection<int, object>  $records
     * @param  array<int, array<string, mixed>>  $buckets
     * @return array<string, mixed>
     */
    private function rollUp(Collection $roster, Collection $records, array $buckets, string $duration, string $scope, Carbon $start, Carbon $end): array
    {
        $blank = [
            'marked' => 0, 'credit' => 0.0,
            AttendanceRecord::STATUS_PRESENT => 0,
            AttendanceRecord::STATUS_ABSENT => 0,
            AttendanceRecord::STATUS_LATE => 0,
            AttendanceRecord::STATUS_EXCUSED => 0,
            AttendanceRecord::STATUS_HALF_DAY => 0,
        ];

        $cells = [];   // [studentId][bucketKey] => tally
        $totals = [];  // [studentId] => tally
        $trend = [];   // [bucketKey] => tally

        foreach ($records as $r) {
            $key = $this->bucketKeyFor($duration, Carbon::parse($r->attendance_date), $buckets);
            if ($key === null) {
                continue;
            }

            $sid = (int) $r->student_id;

            $cells[$sid][$key] = $this->tally($cells[$sid][$key] ?? $blank, $r->status);
            $totals[$sid] = $this->tally($totals[$sid] ?? $blank, $r->status);
            $trend[$key] = $this->tally($trend[$key] ?? $blank, $r->status);
        }

        $rows = $roster->map(function ($s) use ($cells, $totals, $buckets, $blank) {
            $sid = (int) $s->id;
            $total = $totals[$sid] ?? $blank;

            return [
                'student_id' => $sid,
                'name' => trim("{$s->last_name}, {$s->first_name}"),
                'number' => $s->student_number,
                'cells' => collect($buckets)->mapWithKeys(function ($b) use ($cells, $sid, $blank) {
                    $cell = $cells[$sid][$b['key']] ?? $blank;

                    return [$b['key'] => $cell + ['rate' => $this->rate($cell)]];
                })->all(),
                'total' => $total + ['rate' => $this->rate($total)],
            ];
        })->values();

        $sectionTotal = $blank;
        foreach ($trend as $t) {
            $sectionTotal['marked'] += $t['marked'];
            $sectionTotal['credit'] += $t['credit'];
            foreach (AttendanceRecord::STATUSES as $st) {
                $sectionTotal[$st] += $t[$st];
            }
        }

        return [
            'buckets' => array_map(fn ($b) => [
                'key' => $b['key'],
                'label' => $b['label'],
                'range' => $b['start']->format('M j').' – '.$b['end']->format('M j, Y'),
            ], $buckets),
            'trend' => collect($buckets)->map(function ($b) use ($trend, $blank) {
                $t = $trend[$b['key']] ?? $blank;

                return [
                    'key' => $b['key'],
                    'label' => $b['label'],
                    'rate' => $this->rate($t),
                    'marked' => $t['marked'],
                ];
            })->all(),
            'rows' => $rows,
            'section_total' => $sectionTotal + ['rate' => $this->rate($sectionTotal)],
            'unit' => $scope === AttendanceRecord::SCOPE_DAILY ? 'school days' : 'class meetings',
            'window' => ['start' => $start, 'end' => $end],
            'has_data' => $sectionTotal['marked'] > 0,
        ];
    }

    /** Add one mark to a tally, scoring it with the gradebook's credit policy. */
    private function tally(array $tally, ?string $status): array
    {
        $tally['marked']++;
        $tally['credit'] += AttendanceRateService::creditFor($status);

        if ($status !== null && array_key_exists($status, $tally)) {
            $tally[$status]++;
        }

        return $tally;
    }

    /** Percentage attended, or null when nothing was marked to divide by. */
    private function rate(array $tally): ?float
    {
        if (($tally['marked'] ?? 0) <= 0) {
            return null;
        }

        return round($tally['credit'] / $tally['marked'] * 100, 1);
    }
}
