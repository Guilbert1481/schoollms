<?php

namespace App\Services\Dashboard;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentEnrollmentSubject;
use App\Models\Term;
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

    private array $memo = [];

    /* ------------------------------------------------------------------
     |  KPI summary (consumed by KPIRegistry + the dashboard header)
     * -----------------------------------------------------------------*/

    /** @return array<string, array{value: mixed, subtitle: ?string}> keyed by card key. */
    public function summary(User $user): array
    {
        return $this->remember($user, 'summary', function () use ($user) {
            $subjects  = $this->subjectRows($user);
            $deadlines = $this->deadlineCounts($user);
            $billing   = $this->billing($user);

            $posted = $subjects->filter(fn ($s) => $s->posted_grade !== null);
            $gwa    = $this->weightedAverage($posted);
            $risk   = $posted->filter(fn ($s) => (float) $s->posted_grade < self::PASSING_GRADE)->count();
            $units  = $subjects->sum(fn ($s) => (float) ($s->units ?? 0));
            $progress = $subjects->count()
                ? (int) round($subjects->avg(fn ($s) => (int) $s->progress_percentage))
                : 0;

            return [
                'active_subjects_student' => [
                    'value'    => $subjects->count(),
                    'subtitle' => 'This term',
                ],
                'pending_assignments_student' => [
                    'value'    => $deadlines['pending'],
                    'subtitle' => $deadlines['due_this_week'].' due this week',
                ],
                'progress_student' => [
                    'value'    => $progress.'%',
                    'subtitle' => $this->isBasicEd($user) ? 'Term progress' : 'Semester progress',
                ],
                'GWA_student' => [
                    'value'    => $gwa !== null ? number_format($gwa, 2) : '—',
                    'subtitle' => $this->gwaLabel($gwa),
                ],
                'task_student' => [
                    'value'    => $deadlines['completed'],
                    'subtitle' => 'Tasks completed',
                ],
                'subject_at_risk' => [
                    'value'    => $risk,
                    'subtitle' => $risk > 0 ? 'Needs attention' : 'All clear',
                ],
                'outstanding_student' => [
                    // Only what's currently payable (billed + any unpaid past dues),
                    // NOT the whole year's scheduled installments — showing the full
                    // annual payable is an unnecessary emotional burden.
                    'value'    => '₱'.number_format($billing['current_due'], 2),
                    'subtitle' => $billing['due_subtitle'],
                ],
                'units_student' => [
                    'value'    => $units > 0 ? rtrim(rtrim(number_format($units, 2, '.', ''), '0'), '.') : 0,
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
        $subjects  = $this->subjectRows($user);
        $deadlines = $this->deadlineCounts($user);
        $billing   = $this->billing($user);
        $posted    = $subjects->filter(fn ($s) => $s->posted_grade !== null);

        return [
            'term_label'      => $this->termLabel($user),
            'on_track'        => $subjects->count()
                ? (int) round($subjects->avg(fn ($s) => (int) $s->progress_percentage))
                : 0,
            'grade_trend'     => $this->gradeTrend($user),
            'schedule'        => $this->todaySchedule($user),
            'deadline_list'   => $this->upcomingDeadlines($user),
            'announcements'   => $this->announcements($user),
            'performance'     => $posted->map(fn ($s) => [
                'label' => $s->subject_name,
                'value' => (float) $s->posted_grade,
            ])->values()->all(),
            'assignments'     => $deadlines,
            'recent_grades'   => $posted->sortByDesc('graded_at')->take(5)->map(fn ($s) => [
                'subject' => $s->subject_name,
                'code'    => $s->subject_code,
                'grade'   => number_format((float) $s->posted_grade, 2),
                'at_risk' => (float) $s->posted_grade < self::PASSING_GRADE,
                'date'    => $s->graded_at ? Carbon::parse($s->graded_at)->format('M d') : '—',
            ])->values()->all(),
            'billing'         => $billing,
            'subject_cards'   => $subjects->take(4)->map(fn ($s) => [
                'name'     => $s->subject_name,
                'code'     => $s->subject_code,
                'progress' => (int) $s->progress_percentage,
                'grade'    => $s->posted_grade !== null ? number_format((float) $s->posted_grade, 0) : '—',
            ])->values()->all(),
            'calendar'        => $this->calendar($user),
            'snapshot'        => $this->snapshot($user),
        ];
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

            $activeYear = AcademicYear::where('school_id', $user->school_id)
                ->where('is_active', true)
                ->first();
            if (! $activeYear) {
                return null;
            }

            $activeTerm = Term::where('school_id', $user->school_id)
                ->where('academic_year_id', $activeYear->id)
                ->whereIn('status', ['active', 'upcoming'])
                ->orderByRaw("FIELD(status,'active','upcoming')")
                ->orderBy('start_date', 'desc')
                ->first();

            return StudentEnrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $activeYear->id)
                ->where('status', StudentEnrollment::STATUS_ENROLLED)
                ->when($activeTerm, fn ($q) => $q->where('term_id', $activeTerm->id))
                ->latest('id')
                ->first()
                ?? StudentEnrollment::query()
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('status', StudentEnrollment::STATUS_ENROLLED)
                    ->latest('id')
                    ->first();
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
                'pending'       => $rows->where('status', 'pending')->count(),
                'overdue'       => $rows->where('status', 'overdue')->count(),
                'completed'     => $rows->filter(fn ($r) => (int) $r->is_completed === 1)->count(),
                'due_this_week' => $rows->filter(fn ($r) => $r->status === 'pending'
                    && Carbon::parse($r->due_date)->lte($weekEnd))->count(),
                'total'         => $rows->count(),
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
                $due  = Carbon::parse($d->due_date);
                $days = (int) now()->startOfDay()->diffInDays($due->copy()->startOfDay(), false);

                [$badge, $tone] = match (true) {
                    $d->status === 'overdue' || $days < 0 => ['Overdue', 'rose'],
                    $days <= 2                            => ['High', 'rose'],
                    $days <= 7                            => ['Medium', 'amber'],
                    default                               => ['Low', 'emerald'],
                };

                return [
                    'title' => $d->title,
                    'type'  => ucfirst((string) $d->type),
                    'when'  => $days < 0 ? $due->format('M d') : ($days === 0 ? 'Today'
                        : ($days === 1 ? 'Tomorrow' : 'In '.$days.' days')),
                    'badge' => $badge,
                    'tone'  => $tone,
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
                'when'  => Carbon::parse($a->published_at)->format('M d, Y'),
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
        $classIds = $this->subjectRows($user)->pluck('class_id')->filter()->unique()->values();
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
            'time'    => Carbon::parse($r->start_time)->format('g:i A'),
            'subject' => $r->subject_name,
            'room'    => $r->room ?: '—',
            'teacher' => trim(($r->first_name ?? '').' '.($r->last_name ?? '')) ?: '—',
            'now'     => $r->start_time <= $nowTime && $nowTime < $r->end_time,
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

            $open    = $rows->filter(fn ($r) => (float) $r->balance > 0.005);
            $overdue = $open->filter(fn ($r) => $r->due_date && Carbon::parse($r->due_date)->isPast());
            $nextDue = $open->filter(fn ($r) => $r->due_date && ! Carbon::parse($r->due_date)->isPast())
                ->sortBy('due_date')->first();

            // "Currently payable" = invoices already billed (billing_date reached,
            // or legacy null billing_date) that still carry a balance. Future
            // scheduled installments are excluded from the student-facing figure.
            $currentDue = (float) $open->filter(fn ($r) =>
                ! $r->billing_date || Carbon::parse($r->billing_date)->startOfDay()->lte($today)
            )->sum('balance');

            $outstanding = (float) $open->sum('balance');

            return [
                'paid'            => (float) $rows->sum('paid_amount'),
                'outstanding'     => $outstanding,           // full open balance (all installments)
                'current_due'     => $currentDue,            // billed + unpaid past dues only
                'overdue'         => (float) $overdue->sum('balance'),
                'next_due'        => $nextDue ? Carbon::parse($nextDue->due_date)->format('M d') : null,
                'next_due_amount' => $nextDue ? (float) $nextDue->balance : null,
                'due_subtitle'    => match (true) {
                    $overdue->isNotEmpty()  => 'Overdue — please settle',
                    $nextDue !== null       => 'Due '.Carbon::parse($nextDue->due_date)->format('M d'),
                    $currentDue > 0.005     => 'No due date set',
                    default                 => 'All settled',
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
        $end   = $start->copy()->endOfMonth();
        $days  = [];

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
            'year'  => (int) $start->format('Y'),
            'month' => (int) $start->format('n'),
            'days'  => $days,
        ];
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
        $billing   = $this->billing($user);
        $subjects  = $this->subjectRows($user);
        $posted    = $subjects->filter(fn ($s) => $s->posted_grade !== null);
        $risk      = $posted->filter(fn ($s) => (float) $s->posted_grade < self::PASSING_GRADE);

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
            $bullets[] = ['tone' => 'rose', 'text' => $risk->pluck('subject_name')->take(2)->implode(' and ').($risk->count() > 2 ? ' (+'.($risk->count() - 2).' more)' : '').' need'.($risk->count() === 1 ? 's' : '').' attention grade-wise.'];
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
        $sum     = $posted->sum(fn ($s) => (float) $s->posted_grade * (float) ($s->units ?: 1));

        return $weights > 0 ? $sum / $weights : null;
    }

    private function gwaLabel(?float $gwa): string
    {
        return match (true) {
            $gwa === null                     => 'No grades yet',
            $gwa >= 90.0                      => 'Excellent',
            $gwa >= 85.0                      => 'Very good',
            $gwa >= 80.0                      => 'Good',
            $gwa >= self::PASSING_GRADE       => 'Passing',
            default                           => 'At risk',
        };
    }

    private function remember(User $user, string $key, \Closure $compute)
    {
        return $this->memo[$user->id][$key] ??= $compute();
    }
}
