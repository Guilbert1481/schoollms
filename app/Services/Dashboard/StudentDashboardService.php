<?php

namespace App\Services\Dashboard;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentEnrollmentSubject;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes every widget on the student dashboard from live data:
 * KPI summary values, grade trend, today's schedule, deadlines,
 * announcements, per-subject performance/progress, billing overview,
 * calendar events and the rule-based "Study Coach" snapshot.
 *
 * All queries are scoped to the authenticated student's school and
 * current enrollment. Results are memoised per user for the request so
 * KPIRegistry and the dashboard controller share one computation.
 */
class StudentDashboardService
{
    /** Philippine passing threshold; grades below this mark a subject at risk. */
    public const PASSING_GRADE = 75.0;

    /** Attendance rate (%) below which attendance is flagged as a risk factor. */
    public const ATTENDANCE_AT_RISK = 80;

    private array $memo = [];

    /* ------------------------------------------------------------------
     |  KPI summary (consumed by KPIRegistry + the dashboard header)
     * -----------------------------------------------------------------*/

    /** @return array<string, array{value: mixed, subtitle: ?string}> keyed by card key. */
    public function summary(User $user): array
    {
        return $this->remember($user, 'summary', function () use ($user) {
            $subjects = $this->subjectRows($user);
            $deadlines = $this->deadlineCounts($user);
            $billing = $this->billing($user);

            $posted = $subjects->filter(fn ($s) => $s->posted_grade !== null);
            $gwa = $this->weightedAverage($posted);
            // Same multi-source detection as the modal, so the tile count and
            // the breakdown can never disagree.
            $risk = count($this->subjectsAtRisk($user));
            $units = $subjects->sum(fn ($s) => (float) ($s->units ?? 0));
            $progress = $subjects->count()
                ? (int) round($subjects->avg(fn ($s) => (int) $s->progress_percentage))
                : 0;

            return [
                'active_subjects_student' => [
                    'value' => $subjects->count(),
                    'subtitle' => 'This term',
                ],
                'pending_assignments_student' => [
                    'value' => $deadlines['pending'],
                    'subtitle' => $deadlines['due_this_week'].' due this week',
                ],
                'progress_student' => [
                    'value' => $progress.'%',
                    'subtitle' => $this->isBasicEd($user) ? 'Term progress' : 'Semester progress',
                ],
                'GWA_student' => [
                    'value' => $gwa !== null ? number_format($gwa, 2) : '—',
                    'subtitle' => $this->gwaLabel($gwa),
                ],
                'task_student' => [
                    'value' => $deadlines['completed'],
                    'subtitle' => 'Tasks completed',
                ],
                'subject_at_risk' => [
                    'value' => $risk,
                    'subtitle' => $risk > 0 ? 'Needs attention' : 'All clear',
                ],
                'outstanding_student' => [
                    // Only what's currently payable (billed + any unpaid past dues),
                    // NOT the whole year's scheduled installments — showing the full
                    // annual payable is an unnecessary emotional burden.
                    'value' => '₱'.number_format($billing['current_due'], 2),
                    'subtitle' => $billing['due_subtitle'],
                ],
                'units_student' => [
                    'value' => $units > 0 ? rtrim(rtrim(number_format($units, 2, '.', ''), '0'), '.') : 0,
                    'subtitle' => 'Enrolled units',
                ],
            ];
        });
    }

    /* ------------------------------------------------------------------
     |  Full widget payload for the dashboard view
     * -----------------------------------------------------------------*/

    public function widgets(User $user): array
    {
        $subjects = $this->subjectRows($user);
        $deadlines = $this->deadlineCounts($user);
        $billing = $this->billing($user);
        $posted = $subjects->filter(fn ($s) => $s->posted_grade !== null);

        return [
            'term_label' => $this->termLabel($user),
            'on_track' => $subjects->count()
                ? (int) round($subjects->avg(fn ($s) => (int) $s->progress_percentage))
                : 0,
            'grade_trend' => $this->gradeTrend($user),
            'schedule' => $this->todaySchedule($user),
            'deadline_list' => $this->upcomingDeadlines($user),
            'announcements' => $this->announcements($user),
            'performance' => $posted->map(fn ($s) => [
                'label' => $s->subject_name,
                'value' => (float) $s->posted_grade,
            ])->values()->all(),
            'assignments' => $deadlines,
            'recent_grades' => $posted->sortByDesc('graded_at')->take(5)->map(fn ($s) => [
                'subject' => $s->subject_name,
                'code' => $s->subject_code,
                'grade' => number_format((float) $s->posted_grade, 2),
                'at_risk' => (float) $s->posted_grade < self::PASSING_GRADE,
                'date' => $s->graded_at ? Carbon::parse($s->graded_at)->format('M d') : '—',
            ])->values()->all(),
            'billing' => $billing,
            'subject_cards' => $subjects->take(4)->map(fn ($s) => [
                'name' => $s->subject_name,
                'code' => $s->subject_code,
                'progress' => (int) $s->progress_percentage,
                'grade' => $s->posted_grade !== null ? number_format((float) $s->posted_grade, 0) : '—',
            ])->values()->all(),
            'calendar' => $this->calendar($user),
            'snapshot' => $this->snapshot($user),
        ];
    }

    /* ------------------------------------------------------------------
     |  My Schedule (weekly timetable page)
     * -----------------------------------------------------------------*/

    /**
     * The student's weekly class timetable, grouped by weekday, from the recurring
     * class_schedules pattern for every class they're in this term (higher-ed
     * subject enrolments + basic-ed section classes).
     *
     * @return array{days: array<string, array<int, array<string, mixed>>>, has_any: bool}
     */
    public function weeklySchedule(User $user): array
    {
        $order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $days = array_fill_keys($order, []);

        $classIds = $this->currentClassIds($user);
        if (empty($classIds)) {
            return ['days' => $days, 'has_any' => false];
        }

        $rows = DB::table('class_schedules as sch')
            ->join('classes as c', 'c.id', '=', 'sch.class_id')
            ->join('subjects as s', 's.id', '=', 'c.subject_id')
            ->leftJoin('users as t', 't.id', '=', 'c.teacher_id')
            ->whereIn('sch.class_id', $classIds)
            ->orderBy('sch.start_time')
            ->get([
                'sch.day_of_week', 'sch.start_time', 'sch.end_time', 'c.room',
                's.name as subject_name', 's.code as subject_code',
                't.first_name', 't.last_name',
            ]);

        $todayName = now()->format('l');
        $nowTime = now()->format('H:i:s');

        foreach ($rows as $r) {
            if (! array_key_exists($r->day_of_week, $days)) {
                continue;
            }
            $days[$r->day_of_week][] = [
                'time' => Carbon::parse($r->start_time)->format('g:i A'),
                'end' => Carbon::parse($r->end_time)->format('g:i A'),
                'subject' => $r->subject_name,
                'code' => $r->subject_code,
                'room' => $r->room ?: '—',
                'teacher' => trim(($r->first_name ?? '').' '.($r->last_name ?? '')) ?: '—',
                'now' => $r->day_of_week === $todayName
                              && $r->start_time <= $nowTime && $nowTime < $r->end_time,
            ];
        }

        return ['days' => $days, 'has_any' => collect($days)->flatten(1)->isNotEmpty()];
    }

    /** Class ids the student is in for the active enrolment (higher-ed subjects + basic-ed section). */
    private function currentClassIds(User $user): array
    {
        return $this->remember($user, 'current_class_ids', function () use ($user) {
            $enrollment = $this->enrollment($user);
            if (! $enrollment) {
                return [];
            }

            $higher = $this->subjectRows($user)->pluck('class_id')->filter();

            $basic = $enrollment->section_id
                ? DB::table('classes')->where('section_id', $enrollment->section_id)->pluck('id')
                : collect();

            return $higher->merge($basic)->map(fn ($i) => (int) $i)->unique()->values()->all();
        });
    }

    /* ------------------------------------------------------------------
     |  Subjects at Risk (multi-factor modal)
     * -----------------------------------------------------------------*/

    /**
     * At-risk subjects, multi-source. A subject is flagged when its best
     * available measure falls below passing:
     *   1. a POSTED grade — student_enrollment_subjects (higher ed) or
     *      report_card_grades (basic ed quarters, averaged); or, with
     *      nothing posted for that subject yet,
     *   2. a RUNNING measure from graded activity — the grading-scheme
     *      component average when work is component-tagged, else the plain
     *      mean of activity percentages (tests, OMR sheets, homework).
     * Flagged subjects are enriched with reasons (grade, attendance rate,
     * failed tests) and a recommendation. Advisory display only — nothing
     * here writes or alters a grade.
     *
     * @return list<array<string, mixed>>
     */
    public function subjectsAtRisk(User $user): array
    {
        return $this->remember($user, 'subjects_at_risk', function () use ($user) {
            $student = Student::where('user_id', $user->id)->where('school_id', $user->school_id)->first();
            $enrollment = $this->enrollment($user);
            if (! $student || ! $enrollment) {
                return [];
            }

            // subject_id => ['value' => float, 'source' => 'posted'|'running']
            $measures = [];
            foreach ($this->postedBySubject($student, $enrollment) as $subjectId => $grade) {
                $measures[$subjectId] = ['value' => $grade, 'source' => 'posted'];
            }
            foreach ($this->runningBySubject($student) as $subjectId => $avg) {
                $measures[$subjectId] ??= ['value' => $avg, 'source' => 'running'];
            }

            $atRisk = array_filter($measures, fn ($m) => $m['value'] < self::PASSING_GRADE);
            if ($atRisk === []) {
                return [];
            }

            $subjects = DB::table('subjects')->whereIn('id', array_keys($atRisk))->get(['id', 'name', 'code'])->keyBy('id');
            $classBySubject = $this->classBySubject($student, $enrollment);
            $attendance = $this->attendanceByClass($student);   // [class_id => pct]
            $failed = $this->failedTestsByClass($student);       // [class_id => count]

            $rows = [];
            foreach ($atRisk as $subjectId => $m) {
                $meta = $subjects[$subjectId] ?? null;
                $classId = $classBySubject[$subjectId] ?? null;
                $avg = round($m['value'], 2);
                $att = $classId ? ($attendance[$classId] ?? null) : null;
                $fail = $classId ? ($failed[$classId] ?? 0) : 0;

                $reasons = [$m['source'] === 'posted'
                    ? 'Average '.number_format($avg, 2).' — below passing ('.(int) self::PASSING_GRADE.')'
                    : 'Running average '.number_format($avg, 2).' — below passing ('.(int) self::PASSING_GRADE.'), no grade posted yet'];
                if ($att !== null && $att < self::ATTENDANCE_AT_RISK) {
                    $reasons[] = 'Attendance '.$att.'% — frequent absences';
                }
                if ($fail > 0) {
                    $reasons[] = $fail.' failed quiz/long test'.($fail > 1 ? 's' : '');
                }

                $rows[] = [
                    'subject' => $meta->name ?? 'Subject #'.$subjectId,
                    'code' => $meta->code ?? null,
                    'average' => number_format($avg, 2),
                    'attendance' => $att,
                    'failed_tests' => $fail,
                    'reasons' => $reasons,
                    'recommendation' => $this->recommendation($avg, $att, $fail),
                ];
            }

            usort($rows, fn ($a, $b) => (float) $a['average'] <=> (float) $b['average']);

            return $rows;
        });
    }

    /**
     * Posted grades per subject — one per enrolled higher-ed subject
     * (COALESCE(final_grade, grade)), plus basic-ed report-card quarters
     * averaged per subject. Higher-ed wins when both somehow exist.
     *
     * @return array<int, float> subject_id => grade
     */
    private function postedBySubject(Student $student, StudentEnrollment $enrollment): array
    {
        $out = [];

        DB::table('student_enrollment_subjects as ses')
            ->where('ses.student_enrollment_id', $enrollment->id)
            ->where('ses.status', StudentEnrollment::STATUS_ENROLLED)
            ->whereNotNull('ses.subject_id')
            ->selectRaw('ses.subject_id, COALESCE(ses.final_grade, ses.grade) as posted')
            ->get()
            ->each(function ($r) use (&$out) {
                if ($r->posted !== null) {
                    $out[(int) $r->subject_id] = (float) $r->posted;
                }
            });

        DB::table('report_card_grades')
            ->where('student_id', $student->id)
            ->where('school_id', $student->school_id)
            ->when($enrollment->academic_year_id, fn ($q) => $q->where('academic_year_id', $enrollment->academic_year_id))
            ->whereNotNull('final_grade')
            ->groupBy('subject_id')
            ->selectRaw('subject_id, AVG(final_grade) as avg_grade')
            ->get()
            ->each(function ($r) use (&$out) {
                $out[(int) $r->subject_id] ??= (float) $r->avg_grade;
            });

        return $out;
    }

    /**
     * Running measure per subject from graded activity: the grading-scheme
     * component average (weighted) where component_scores exist, else the
     * plain mean of activity percentages.
     *
     * @return array<int, float> subject_id => running average
     */
    private function runningBySubject(Student $student): array
    {
        $out = [];

        $componentRows = DB::table('component_scores as cs')
            ->join('grade_components as gc', 'gc.id', '=', 'cs.grade_component_id')
            ->leftJoin('classes as c', 'c.id', '=', 'cs.class_id')
            ->where('cs.student_id', $student->id)
            ->where('cs.school_id', $student->school_id)
            ->whereNotNull('cs.score')
            ->selectRaw('COALESCE(cs.subject_id, c.subject_id) as sid, cs.grade_component_id, gc.weight, cs.score')
            ->get()
            ->filter(fn ($r) => $r->sid !== null);

        foreach ($componentRows->groupBy('sid') as $sid => $bucket) {
            $weightedSum = 0.0;
            $totalWeight = 0.0;
            foreach ($bucket->groupBy('grade_component_id') as $rows) {
                $weight = (float) $rows->first()->weight;
                if ($weight <= 0) {
                    continue;
                }
                $weightedSum += (float) $rows->avg('score') * $weight;
                $totalWeight += $weight;
            }
            if ($totalWeight > 0) {
                $out[(int) $sid] = $weightedSum / $totalWeight;
            }
        }

        foreach ($this->activityPctsBySubject($student) as $sid => $pcts) {
            $out[$sid] ??= array_sum($pcts) / count($pcts);
        }

        return $out;
    }

    /**
     * Graded-activity percentages per subject: the latest submitted/graded
     * attempt per online test, every OMR result, and graded homework.
     *
     * @return array<int, list<float>> subject_id => percentages
     */
    private function activityPctsBySubject(Student $student): array
    {
        $out = [];
        $push = function ($sid, $pct) use (&$out) {
            if ($sid !== null && $pct !== null) {
                $out[(int) $sid][] = (float) $pct;
            }
        };

        DB::table('test_attempts as ta')
            ->join('tests as t', 't.id', '=', 'ta.test_id')
            ->leftJoin('classes as c', 'c.id', '=', 't.class_id')
            ->where('ta.student_id', $student->id)
            ->where('t.school_id', $student->school_id)
            ->whereIn('ta.status', ['submitted', 'graded'])
            ->whereNotNull('ta.percentage')
            ->orderBy('ta.id')
            ->selectRaw('ta.test_id, ta.percentage, COALESCE(t.subject_id, c.subject_id) as sid')
            ->get()
            ->groupBy('test_id')
            ->each(function ($g) use ($push) {
                $latest = $g->last();
                $push($latest->sid, $latest->percentage);
            });

        DB::table('omr_sheets as sh')
            ->join('omr_results as r', 'r.omr_sheet_id', '=', 'sh.id')
            ->join('tests as t', 't.id', '=', 'sh.test_id')
            ->leftJoin('classes as c', 'c.id', '=', 't.class_id')
            ->where('sh.student_id', $student->id)
            ->where('sh.school_id', $student->school_id)
            ->whereNotNull('r.percentage')
            ->selectRaw('r.percentage, COALESCE(t.subject_id, c.subject_id) as sid')
            ->get()
            ->each(fn ($r) => $push($r->sid, $r->percentage));

        DB::table('homework_submissions as hs')
            ->join('homework as h', 'h.id', '=', 'hs.homework_id')
            ->leftJoin('classes as c', 'c.id', '=', 'h.class_id')
            ->where('hs.student_id', $student->id)
            ->where('h.school_id', $student->school_id)
            ->whereNotNull('hs.score')
            ->selectRaw('hs.score, h.points, c.subject_id as sid')
            ->get()
            ->each(function ($r) use ($push) {
                if ((float) $r->points > 0) {
                    $push($r->sid, (float) $r->score / (float) $r->points * 100);
                }
            });

        return $out;
    }

    /**
     * subject_id => class_id, for the attendance / failed-test enrichment —
     * higher ed from the enrolled-subject rows, basic ed from the section's
     * classes.
     *
     * @return array<int, int>
     */
    private function classBySubject(Student $student, StudentEnrollment $enrollment): array
    {
        $out = [];

        DB::table('student_enrollment_subjects as ses')
            ->where('ses.student_enrollment_id', $enrollment->id)
            ->whereNotNull('ses.class_id')
            ->whereNotNull('ses.subject_id')
            ->get(['ses.subject_id', 'ses.class_id'])
            ->each(function ($r) use (&$out) {
                $out[(int) $r->subject_id] ??= (int) $r->class_id;
            });

        if ($enrollment->section_id) {
            DB::table('classes')
                ->where('section_id', $enrollment->section_id)
                ->where('school_id', $student->school_id)
                ->whereNotNull('subject_id')
                ->get(['id', 'subject_id'])
                ->each(function ($r) use (&$out) {
                    $out[(int) $r->subject_id] ??= (int) $r->id;
                });
        }

        return $out;
    }

    /**
     * Attendance rate (%) per class from attendance_records (session scope);
     * present/late/excused count as attended.
     *
     * @return array<int, int> class_id => percent
     */
    private function attendanceByClass(?Student $student): array
    {
        if (! $student) {
            return [];
        }

        $out = [];
        DB::table('attendance_records')
            ->where('student_id', $student->id)
            ->whereNotNull('class_id')
            ->get(['class_id', 'status'])
            ->groupBy('class_id')
            ->each(function ($group, $classId) use (&$out) {
                $total = $group->count();
                $attended = $group->whereIn('status', ['present', 'late', 'excused'])->count();
                $out[(int) $classId] = (int) round($attended / $total * 100);
            });

        return $out;
    }

    /**
     * Number of distinct failed online tests per class — the student's LATEST
     * graded/submitted attempt per test scoring below passing.
     *
     * @return array<int, int> class_id => failed count
     */
    private function failedTestsByClass(?Student $student): array
    {
        if (! $student) {
            return [];
        }

        $latestPerTest = DB::table('test_attempts as ta')
            ->join('tests as t', 't.id', '=', 'ta.test_id')
            ->where('ta.student_id', $student->id)
            ->whereIn('ta.status', ['submitted', 'graded'])
            ->whereNotNull('ta.percentage')
            ->whereNotNull('t.class_id')
            ->orderBy('ta.id')
            ->get(['ta.test_id', 't.class_id', 'ta.percentage'])
            ->groupBy('test_id')
            ->map(fn ($g) => $g->last());   // last = highest id = latest attempt

        return $latestPerTest
            ->filter(fn ($r) => (float) $r->percentage < self::PASSING_GRADE)
            ->groupBy('class_id')
            ->map->count()
            ->mapWithKeys(fn ($c, $k) => [(int) $k => (int) $c])
            ->all();
    }

    /** A short, encouraging "how to at least pass" line from the risk factors. */
    private function recommendation(float $avg, ?int $attendance, int $failed): string
    {
        $gap = (int) ceil(max(0, self::PASSING_GRADE - $avg));
        $tips = [$gap > 0
            ? 'Raise your average by ~'.$gap.' point'.($gap > 1 ? 's' : '').' to reach passing.'
            : 'Hold your average above passing.'];

        if ($failed > 0) {
            $tips[] = 'Review and, where allowed, retake the failed test'.($failed > 1 ? 's' : '').'.';
        }
        if ($attendance !== null && $attendance < self::ATTENDANCE_AT_RISK) {
            $tips[] = 'Attend every remaining class.';
        }
        $tips[] = 'Ask your teacher about remedial work or a consultation.';

        return implode(' ', $tips);
    }

    /* ------------------------------------------------------------------
     |  Building blocks
     * -----------------------------------------------------------------*/

    /** The student's active enrollment, resolved the same way as My Subjects. */
    private function enrollment(User $user): ?StudentEnrollment
    {
        return $this->remember($user, 'enrollment', function () use ($user) {
            $student = Student::query()
                ->where('user_id', $user->id)
                ->where('school_id', $user->school_id)
                ->first();
            if (! $student) {
                return null;
            }

            // Basic ed and higher ed each manage their own academic-year rows,
            // so a school running both levels legitimately holds one active
            // year PER level — resolve against every active year, never
            // whichever single row ->first() happens to grab.
            $activeYearIds = AcademicYear::where('school_id', $user->school_id)
                ->where('is_active', true)
                ->pluck('id');

            if ($activeYearIds->isEmpty()) {
                // No year flagged active (the activate toggle was never
                // clicked, or a date-sync cleared it) — fall back to the
                // year(s) whose date range covers today.
                $activeYearIds = AcademicYear::where('school_id', $user->school_id)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->pluck('id');
            }
            if ($activeYearIds->isEmpty()) {
                return null;
            }

            $enrolled = fn () => StudentEnrollment::query()
                ->where('student_id', $student->id)
                ->whereIn('academic_year_id', $activeYearIds)
                ->where('status', StudentEnrollment::STATUS_ENROLLED);

            // Prefer an enrollment sitting on a currently running/upcoming
            // term; otherwise any enrolled row in an active year.
            return $enrolled()
                ->whereHas('term', fn ($q) => $q->whereIn('status', ['active', 'upcoming']))
                ->latest('id')
                ->first()
                ?? $enrolled()->latest('id')->first();
        });
    }

    /** Enrolled subject rows with grade, progress, units, class linkage. */
    private function subjectRows(User $user)
    {
        return $this->remember($user, 'subjects', function () use ($user) {
            $enrollment = $this->enrollment($user);
            if (! $enrollment) {
                return collect();
            }

            return DB::table('student_enrollment_subjects as ses')
                ->join('subjects as s', 's.id', '=', 'ses.subject_id')
                ->leftJoin('classes as c', 'c.id', '=', 'ses.class_id')
                ->where('ses.student_enrollment_id', $enrollment->id)
                ->where('ses.status', StudentEnrollmentSubject::STATUS_ENROLLED)
                ->orderBy('s.name')
                ->get([
                    'ses.id',
                    'ses.class_id',
                    'ses.progress_percentage',
                    'ses.updated_at as graded_at',
                    's.name as subject_name',
                    's.code as subject_code',
                    's.units',
                    'c.room as class_room',
                    DB::raw('COALESCE(ses.final_grade, ses.grade) as posted_grade'),
                ]);
        });
    }

    /** @return array{pending:int, overdue:int, completed:int, due_this_week:int, total:int} */
    private function deadlineCounts(User $user): array
    {
        return $this->remember($user, 'deadline_counts', function () use ($user) {
            $rows = DB::table('deadline_user_completions as duc')
                ->join('deadlines as d', 'd.id', '=', 'duc.deadline_id')
                ->where('duc.user_id', $user->id)
                ->where('d.school_id', $user->school_id)
                ->where('d.active', 1)
                ->whereNull('d.deleted_at')
                ->get(['duc.status', 'duc.is_completed', 'd.due_date']);

            $weekEnd = now()->addDays(7);

            return [
                'pending' => $rows->where('status', 'pending')->count(),
                'overdue' => $rows->where('status', 'overdue')->count(),
                'completed' => $rows->filter(fn ($r) => (int) $r->is_completed === 1)->count(),
                'due_this_week' => $rows->filter(fn ($r) => $r->status === 'pending'
                    && Carbon::parse($r->due_date)->lte($weekEnd))->count(),
                'total' => $rows->count(),
            ];
        });
    }

    /** Next unfinished deadlines with an urgency badge. */
    private function upcomingDeadlines(User $user): array
    {
        return DB::table('deadline_user_completions as duc')
            ->join('deadlines as d', 'd.id', '=', 'duc.deadline_id')
            ->where('duc.user_id', $user->id)
            ->where('d.school_id', $user->school_id)
            ->where('d.active', 1)
            ->whereNull('d.deleted_at')
            ->whereIn('duc.status', ['pending', 'overdue'])
            ->orderBy('d.due_date')
            ->limit(5)
            ->get(['d.title', 'd.type', 'd.due_date', 'duc.status'])
            ->map(function ($d) {
                $due = Carbon::parse($d->due_date);
                $days = (int) now()->startOfDay()->diffInDays($due->copy()->startOfDay(), false);

                [$badge, $tone] = match (true) {
                    $d->status === 'overdue' || $days < 0 => ['Overdue', 'rose'],
                    $days <= 2 => ['High', 'rose'],
                    $days <= 7 => ['Medium', 'amber'],
                    default => ['Low', 'emerald'],
                };

                return [
                    'title' => $d->title,
                    'type' => ucfirst((string) $d->type),
                    'when' => $days < 0 ? $due->format('M d') : ($days === 0 ? 'Today'
                        : ($days === 1 ? 'Tomorrow' : 'In '.$days.' days')),
                    'badge' => $badge,
                    'tone' => $tone,
                ];
            })
            ->all();
    }

    /** Active, published announcements for the school. */
    private function announcements(User $user): array
    {
        return DB::table('announcements')
            ->where('school_id', $user->school_id)
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderByRaw("FIELD(priority_level,'super','normal')")
            ->orderByDesc('published_at')
            ->limit(4)
            ->get(['title', 'published_at', 'priority_level'])
            ->map(fn ($a) => [
                'title' => $a->title,
                'when' => Carbon::parse($a->published_at)->format('M d, Y'),
                'super' => $a->priority_level === 'super',
            ])
            ->all();
    }

    /**
     * Today's class meetings: dated class_sessions first, otherwise the
     * weekly class_schedules pattern for today's weekday.
     */
    private function todaySchedule(User $user): array
    {
        // Higher-ed subject enrolments + basic-ed section classes — the same
        // class set the weekly timetable page uses.
        $classIds = collect($this->currentClassIds($user));
        if ($classIds->isEmpty()) {
            return [];
        }

        $rows = DB::table('class_sessions as cs')
            ->join('subjects as s', 's.id', '=', 'cs.subject_id')
            ->leftJoin('users as t', 't.id', '=', 'cs.teacher_id')
            ->whereIn('cs.class_id', $classIds)
            ->whereDate('cs.meeting_date', now()->toDateString())
            ->where('cs.status', '!=', 'cancelled')
            ->orderBy('cs.start_time')
            ->get([
                'cs.start_time', 'cs.end_time', 'cs.room',
                's.name as subject_name', 't.first_name', 't.last_name',
            ]);

        if ($rows->isEmpty()) {
            $rows = DB::table('class_schedules as sch')
                ->join('classes as c', 'c.id', '=', 'sch.class_id')
                ->join('subjects as s', 's.id', '=', 'c.subject_id')
                ->leftJoin('users as t', 't.id', '=', 'c.teacher_id')
                ->whereIn('sch.class_id', $classIds)
                ->where('sch.day_of_week', now()->format('l'))
                ->orderBy('sch.start_time')
                ->get([
                    'sch.start_time', 'sch.end_time', 'c.room',
                    's.name as subject_name', 't.first_name', 't.last_name',
                ]);
        }

        $nowTime = now()->format('H:i:s');

        return $rows->map(fn ($r) => [
            'time' => Carbon::parse($r->start_time)->format('g:i A'),
            'subject' => $r->subject_name,
            'room' => $r->room ?: '—',
            'teacher' => trim(($r->first_name ?? '').' '.($r->last_name ?? '')) ?: '—',
            'now' => $r->start_time <= $nowTime && $nowTime < $r->end_time,
        ])->all();
    }

    /** Average posted grade per month (last 6 months with grades). */
    private function gradeTrend(User $user): array
    {
        $enrollment = $this->enrollment($user);
        if (! $enrollment) {
            return ['labels' => [], 'values' => []];
        }

        $rows = DB::table('student_enrollment_subjects')
            ->where('student_enrollment_id', $enrollment->id)
            ->where(fn ($q) => $q->whereNotNull('final_grade')->orWhereNotNull('grade'))
            ->groupByRaw("DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m')")
            ->limit(6)
            ->get([
                DB::raw("DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m') as ym"),
                DB::raw('AVG(COALESCE(final_grade, grade)) as avg_grade'),
            ])
            ->filter(fn ($r) => ! empty($r->ym))
            ->values();

        return [
            'labels' => $rows->map(fn ($r) => Carbon::createFromFormat('Y-m', $r->ym)->format('M'))->all(),
            'values' => $rows->map(fn ($r) => round((float) $r->avg_grade, 2))->all(),
        ];
    }

    /** @return array{paid: float, outstanding: float, current_due: float, overdue: float, next_due: ?string, next_due_amount: ?float, due_subtitle: string} */
    private function billing(User $user): array
    {
        return $this->remember($user, 'billing', function () use ($user) {
            $rows = DB::table('invoices')
                ->where('school_id', $user->school_id)
                ->where('student_id', $user->id)
                ->get(['paid_amount', 'balance', 'due_date', 'billing_date']);

            $today = now()->startOfDay();

            $open = $rows->filter(fn ($r) => (float) $r->balance > 0.005);
            $overdue = $open->filter(fn ($r) => $r->due_date && Carbon::parse($r->due_date)->isPast());
            $nextDue = $open->filter(fn ($r) => $r->due_date && ! Carbon::parse($r->due_date)->isPast())
                ->sortBy('due_date')->first();

            // "Currently payable" = invoices already billed (billing_date reached,
            // or legacy null billing_date) that still carry a balance. Future
            // scheduled installments are excluded from the student-facing figure.
            $currentDue = (float) $open->filter(fn ($r) => ! $r->billing_date || Carbon::parse($r->billing_date)->startOfDay()->lte($today)
            )->sum('balance');

            $outstanding = (float) $open->sum('balance');

            return [
                'paid' => (float) $rows->sum('paid_amount'),
                'outstanding' => $outstanding,           // full open balance (all installments)
                'current_due' => $currentDue,            // billed + unpaid past dues only
                'overdue' => (float) $overdue->sum('balance'),
                'next_due' => $nextDue ? Carbon::parse($nextDue->due_date)->format('M d') : null,
                'next_due_amount' => $nextDue ? (float) $nextDue->balance : null,
                'due_subtitle' => match (true) {
                    $overdue->isNotEmpty() => 'Overdue — please settle',
                    $nextDue !== null => 'Due '.Carbon::parse($nextDue->due_date)->format('M d'),
                    $currentDue > 0.005 => 'No due date set',
                    default => 'All settled',
                },
            ];
        });
    }

    /**
     * Events of the current month keyed by day number.
     * Types: class (sessions), assignment / exam (deadlines by type).
     *
     * @return array{year:int, month:int, days:array<int, array<int, string>>}
     */
    private function calendar(User $user): array
    {
        return $this->calendarFor($user, (int) now()->format('Y'), (int) now()->format('n'));
    }

    /**
     * Events for a specific month (used by the dashboard + the month-navigation
     * endpoint so students can browse back/forward through the school year).
     *
     * @return array{year:int, month:int, days:array<int, array<int, string>>}
     */
    public function calendarFor(User $user, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $days = [];

        $push = function (string $date, string $type) use (&$days) {
            $day = (int) Carbon::parse($date)->format('j');
            if (! in_array($type, $days[$day] ?? [], true)) {
                $days[$day][] = $type;
            }
        };

        $classIds = $this->subjectRows($user)->pluck('class_id')->filter()->unique()->values();
        if ($classIds->isNotEmpty()) {
            DB::table('class_sessions')
                ->whereIn('class_id', $classIds)
                ->whereBetween('meeting_date', [$start->toDateString(), $end->toDateString()])
                ->where('status', '!=', 'cancelled')
                ->pluck('meeting_date')
                ->each(fn ($d) => $push((string) $d, 'class'));
        }

        DB::table('deadline_user_completions as duc')
            ->join('deadlines as d', 'd.id', '=', 'duc.deadline_id')
            ->where('duc.user_id', $user->id)
            ->where('d.school_id', $user->school_id)
            ->where('d.active', 1)
            ->whereNull('d.deleted_at')
            ->whereBetween('d.due_date', [$start, $end])
            ->get(['d.due_date', 'd.type'])
            ->each(fn ($d) => $push(
                (string) $d->due_date,
                in_array(strtolower((string) $d->type), ['exam', 'quiz', 'test'], true) ? 'exam' : 'assignment'
            ));

        return [
            'year' => (int) $start->format('Y'),
            'month' => (int) $start->format('n'),
            'days' => $days,
        ];
    }

    /**
     * The student's active enrollment, publicly exposed for feature gating
     * (student services pages share the dashboard's resolution + memo).
     */
    public function activeEnrollment(User $user): ?StudentEnrollment
    {
        return $this->enrollment($user);
    }

    /**
     * Can this student currently file a modality request? Non-basic-ed only,
     * and only within 2 weeks of the official enrollment date. Drives both
     * the sidebar item's visibility and the server-side guard on submit.
     */
    public function modalityWindowOpen(User $user): bool
    {
        $enrollment = $this->enrollment($user);
        if (! $enrollment || $this->isBasicEd($user)) {
            return false;
        }

        $deadline = $enrollment->modalityRequestDeadline();

        return $deadline !== null && now()->lte($deadline);
    }

    /**
     * Is the student's active enrollment in basic education? Drives level-aware
     * copy (Term vs Semester) and KPI visibility (units are higher-ed only).
     */
    public function isBasicEd(User $user): bool
    {
        return $this->remember($user, 'is_basic_ed', function () use ($user) {
            $enrollment = $this->enrollment($user);
            if (! $enrollment) {
                return false;
            }

            $termLevel = DB::table('terms')->where('id', $enrollment->term_id)->value('education_level');
            if ($termLevel) {
                return strtolower((string) $termLevel) === 'basic_ed';
            }

            return in_array(
                strtolower((string) $enrollment->education_level),
                ['kinder', 'elementary', 'junior_high', 'senior_high', 'basic_ed', 'basic'],
                true
            );
        });
    }

    /** Rule-based Study Coach bullets, all derived from live data. */
    private function snapshot(User $user): array
    {
        $deadlines = $this->deadlineCounts($user);
        $billing = $this->billing($user);
        $subjects = $this->subjectRows($user);
        $posted = $subjects->filter(fn ($s) => $s->posted_grade !== null);
        $risk = collect($this->subjectsAtRisk($user)); // multi-source, same as the KPI/modal

        $dueTomorrow = DB::table('deadline_user_completions as duc')
            ->join('deadlines as d', 'd.id', '=', 'duc.deadline_id')
            ->where('duc.user_id', $user->id)
            ->where('d.school_id', $user->school_id)
            ->where('duc.status', 'pending')
            ->whereDate('d.due_date', now()->addDay()->toDateString())
            ->count();

        $bullets = [];

        if ($dueTomorrow > 0) {
            $bullets[] = ['tone' => 'amber', 'text' => 'You have '.$dueTomorrow.' task'.($dueTomorrow > 1 ? 's' : '').' due tomorrow.'];
        }
        if ($deadlines['overdue'] > 0) {
            $bullets[] = ['tone' => 'rose', 'text' => $deadlines['overdue'].' task'.($deadlines['overdue'] > 1 ? 's are' : ' is').' overdue — catch up soon.'];
        }
        if ($risk->isNotEmpty()) {
            $bullets[] = ['tone' => 'rose', 'text' => $risk->pluck('subject')->take(2)->implode(' and ').($risk->count() > 2 ? ' (+'.($risk->count() - 2).' more)' : '').' need'.($risk->count() === 1 ? 's' : '').' attention grade-wise.'];
        } elseif ($posted->isNotEmpty()) {
            $bullets[] = ['tone' => 'emerald', 'text' => 'All graded subjects are in good standing. Keep it up!'];
        }
        if ($billing['overdue'] > 0.005) {
            $bullets[] = ['tone' => 'rose', 'text' => 'A balance of ₱'.number_format($billing['overdue'], 2).' is past due.'];
        } elseif ($billing['next_due']) {
            $amount = $billing['next_due_amount'] ?? $billing['current_due'];
            $bullets[] = ['tone' => 'sky', 'text' => 'Next payment of ₱'.number_format($amount, 2).' is due '.$billing['next_due'].'.'];
        }

        $classesToday = count($this->todaySchedule($user));
        if ($classesToday > 0) {
            $bullets[] = ['tone' => 'sky', 'text' => 'You have '.$classesToday.' class'.($classesToday > 1 ? 'es' : '').' today.'];
        }

        if (empty($bullets)) {
            $bullets[] = ['tone' => 'emerald', 'text' => 'Nothing urgent today — a good time to get ahead on your subjects.'];
        }

        return array_slice($bullets, 0, 5);
    }

    /* ------------------------------------------------------------------
     |  Helpers
     * -----------------------------------------------------------------*/

    private function termLabel(User $user): string
    {
        $enrollment = $this->enrollment($user);
        if ($enrollment) {
            $name = DB::table('terms')->where('id', $enrollment->term_id)->value('name');
            if ($name) {
                return (string) $name;
            }
        }

        return 'semester';
    }

    /** Units-weighted general average of posted grades (unit-less subjects weigh 1). */
    private function weightedAverage($posted): ?float
    {
        if ($posted->isEmpty()) {
            return null;
        }

        $weights = $posted->sum(fn ($s) => (float) ($s->units ?: 1));
        $sum = $posted->sum(fn ($s) => (float) $s->posted_grade * (float) ($s->units ?: 1));

        return $weights > 0 ? $sum / $weights : null;
    }

    private function gwaLabel(?float $gwa): string
    {
        return match (true) {
            $gwa === null => 'No grades yet',
            $gwa >= 90.0 => 'Excellent',
            $gwa >= 85.0 => 'Very good',
            $gwa >= 80.0 => 'Good',
            $gwa >= self::PASSING_GRADE => 'Passing',
            default => 'At risk',
        };
    }

    private function remember(User $user, string $key, \Closure $compute)
    {
        return $this->memo[$user->id][$key] ??= $compute();
    }
}
