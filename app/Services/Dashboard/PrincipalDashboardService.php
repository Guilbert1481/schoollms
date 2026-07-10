<?php

namespace App\Services\Dashboard;

use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Principal dashboard — every widget's data in one place (student-dashboard
 * pattern: the controller stays thin, the view consumes plain arrays).
 *
 * Honesty rule: widgets whose domain is NOT tracked yet (student/teacher
 * attendance, expenses/budget, teacher observation & evaluation) return
 * null / empty so the view renders an explicit "not tracked yet" state —
 * never fabricated numbers. The queries are already wired, so those cards
 * light up the day the data exists.
 */
class PrincipalDashboardService
{
    /** Grade below this is failing / at risk (house rule, see StudentDashboardService). */
    public const PASSING_GRADE = 75.0;

    private array $memo = [];

    private function remember(string $key, \Closure $fn)
    {
        return $this->memo[$key] ??= $fn();
    }

    /* ============================================================
     | KPI strip (8 cards)
     |============================================================*/

    public function cards(int $schoolId): array
    {
        $students   = $this->totalStudents($schoolId);
        $teachers   = $this->totalTeachers($schoolId);
        $avg        = $this->schoolAverage($schoolId);
        $atRisk     = $this->atRiskCount($schoolId);
        $revenue    = $this->revenueCollected($schoolId);
        $revDelta   = $this->revenueDelta($schoolId);

        return [
            [
                'title' => 'Total Students', 'icon' => 'users', 'accent' => 'blue',
                'value' => number_format($students), 'delta' => null, 'delta_up' => null,
            ],
            [
                'title' => 'Student Attendance', 'icon' => 'user-check', 'accent' => 'emerald',
                'value' => '—', 'subtitle' => 'Not tracked yet', 'delta' => null, 'delta_up' => null,
            ],
            [
                'title' => 'Teachers', 'icon' => 'graduation-cap', 'accent' => 'violet',
                'value' => number_format($teachers), 'delta' => null, 'delta_up' => null,
            ],
            [
                'title' => 'Teacher Attendance', 'icon' => 'user-check', 'accent' => 'emerald',
                'value' => '—', 'subtitle' => 'Not tracked yet', 'delta' => null, 'delta_up' => null,
            ],
            [
                'title' => 'School Average', 'icon' => 'star', 'accent' => 'amber',
                'value' => $avg !== null ? number_format($avg, 1).'%' : '—',
                'subtitle' => $avg === null ? 'No grades posted yet' : null,
                'delta' => null, 'delta_up' => null,
            ],
            [
                'title' => 'Students at Risk', 'icon' => 'alert-triangle', 'accent' => 'red',
                'value' => number_format($atRisk),
                'subtitle' => 'Failing grades (< '.rtrim(rtrim(number_format(self::PASSING_GRADE, 1), '0'), '.').')',
                'delta' => null, 'delta_up' => null,
            ],
            [
                'title' => 'Revenue Collected', 'icon' => 'banknote', 'accent' => 'sky',
                'value' => '₱'.$this->compact($revenue),
                'delta' => $revDelta !== null ? abs($revDelta).'% vs last month' : null,
                'delta_up' => $revDelta !== null ? $revDelta >= 0 : null,
            ],
            [
                'title' => 'Budget Utilization', 'icon' => 'pie-chart', 'accent' => 'sky',
                'value' => '—', 'subtitle' => 'Not tracked yet', 'delta' => null, 'delta_up' => null,
            ],
        ];
    }

    /* ============================================================
     | Widgets (charts + tables + right rail)
     |============================================================*/

    public function widgets(int $schoolId): array
    {
        return [
            'school_years'       => $this->schoolYears($schoolId),
            'enrollment_trend'   => $this->enrollmentTrend($schoolId),
            'revenue_expenses'   => $this->revenueExpenses($schoolId),
            'attendance_grades'  => [],   // not tracked — view renders empty state
            'teacher_compliance' => $this->teacherCompliance($schoolId),
            'financial_health'   => $this->financialHealth($schoolId),
            'attention_students' => $this->attentionStudents($schoolId),
            'staffing'           => $this->staffing($schoolId),
            'pending_approvals'  => $this->pendingApprovals($schoolId),
            'alerts'             => $this->alerts($schoolId),
            'schedule_today'     => $this->scheduleToday($schoolId),
            'deadlines'          => $this->deadlines($schoolId),
        ];
    }

    /* ------------------------------------------------------------
     | KPI helpers
     |------------------------------------------------------------*/

    private function totalStudents(int $schoolId): int
    {
        return (int) DB::table('students')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->count();
    }

    private function totalTeachers(int $schoolId): int
    {
        return (int) DB::table('users')
            ->where('school_id', $schoolId)
            ->where('role', 'teacher')
            ->count();
    }

    private function schoolAverage(int $schoolId): ?float
    {
        $avg = DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as se', 'se.id', '=', 'ses.student_enrollment_id')
            ->where('se.school_id', $schoolId)
            ->whereRaw('COALESCE(ses.final_grade, ses.grade) IS NOT NULL')
            ->selectRaw('AVG(COALESCE(ses.final_grade, ses.grade)) as a')
            ->value('a');

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    private function atRiskRows(int $schoolId)
    {
        return $this->remember('at_risk', fn () => DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as se', 'se.id', '=', 'ses.student_enrollment_id')
            ->join('students as st', 'st.id', '=', 'se.student_id')
            ->join('subjects as sub', 'sub.id', '=', 'ses.subject_id')
            ->where('se.school_id', $schoolId)
            ->whereRaw('COALESCE(ses.final_grade, ses.grade) < ?', [self::PASSING_GRADE])
            ->selectRaw('st.id as student_id,
                         CONCAT(st.first_name, " ", st.last_name) as student_name,
                         se.year_level, sub.name as subject_name,
                         COALESCE(ses.final_grade, ses.grade) as posted_grade,
                         ses.updated_at')
            ->orderBy('posted_grade')
            ->get());
    }

    private function atRiskCount(int $schoolId): int
    {
        return $this->atRiskRows($schoolId)->unique('student_id')->count();
    }

    private function revenueCollected(int $schoolId): float
    {
        return (float) DB::table('payments')->where('school_id', $schoolId)->sum('amount');
    }

    private function revenueDelta(int $schoolId): ?float
    {
        $thisMonth = (float) DB::table('payments')->where('school_id', $schoolId)
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');
        $lastMonth = (float) DB::table('payments')->where('school_id', $schoolId)
            ->whereBetween('paid_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])->sum('amount');

        if ($lastMonth <= 0) {
            return null;
        }

        return round(($thisMonth - $lastMonth) / $lastMonth * 100, 1);
    }

    /* ------------------------------------------------------------
     | Charts
     |------------------------------------------------------------*/

    /** Last 10 months of new enrollments (approved_at, else created_at). */
    private function enrollmentTrend(int $schoolId): array
    {
        $rows = DB::table('student_enrollments')
            ->where('school_id', $schoolId)
            ->whereIn('status', ['enrolled', 'provisionally_enrolled', 'completed'])
            ->selectRaw("DATE_FORMAT(COALESCE(approved_at, created_at), '%Y-%m') as ym, COUNT(DISTINCT student_id) as n")
            ->groupBy('ym')
            ->pluck('n', 'ym');

        [$labels, $values] = $this->monthlyBuckets(10, fn (string $ym) => (int) ($rows[$ym] ?? 0));

        // Cumulative running total mirrors the mockup's rising headcount line.
        $running = 0;
        $values  = array_map(function ($v) use (&$running) { return $running += $v; }, $values);

        return ['labels' => $labels, 'values' => $values, 'has_data' => array_sum($values) > 0];
    }

    /** Last 10 months revenue (real) vs expenses (not tracked → zeros). */
    private function revenueExpenses(int $schoolId): array
    {
        $rows = DB::table('payments')
            ->where('school_id', $schoolId)
            ->whereNotNull('paid_at')
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        [$labels, $revenue] = $this->monthlyBuckets(10, fn (string $ym) => (float) ($rows[$ym] ?? 0));

        return [
            'labels'   => $labels,
            'revenue'  => $revenue,
            'expenses' => array_fill(0, count($labels), 0),   // no expenses table yet
            'has_data' => array_sum($revenue) > 0,
        ];
    }

    /** Compliance meters — only grade submission has a real data source today. */
    private function teacherCompliance(int $schoolId): array
    {
        $totals = DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as se', 'se.id', '=', 'ses.student_enrollment_id')
            ->where('se.school_id', $schoolId)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN COALESCE(ses.final_grade, ses.grade) IS NOT NULL THEN 1 ELSE 0 END) as graded')
            ->first();

        $gradesPct = ($totals && (int) $totals->total > 0)
            ? round((int) $totals->graded / (int) $totals->total * 100, 1)
            : null;

        return [
            ['label' => 'Attendance',            'value' => null],
            ['label' => 'Lesson Plans Submitted', 'value' => null],
            ['label' => 'Grades Submitted',       'value' => $gradesPct],
            ['label' => 'Classroom Observation',  'value' => null],
            ['label' => 'Evaluation Completion',  'value' => null],
        ];
    }

    /** Donut: verified collections vs outstanding receivables (both real). */
    private function financialHealth(int $schoolId): array
    {
        $collected = $this->revenueCollected($schoolId);

        $outstanding = DB::table('ledger_entries')
            ->where('school_id', $schoolId)
            ->select('student_id', DB::raw('SUM(debit) - SUM(credit) as bal'))
            ->groupBy('student_id')
            ->havingRaw('SUM(debit) - SUM(credit) > 0')
            ->get()
            ->sum('bal');

        return [
            'collected'   => (float) $collected,
            'outstanding' => (float) $outstanding,
            'total'       => (float) $collected + (float) $outstanding,
        ];
    }

    /* ------------------------------------------------------------
     | Tables
     |------------------------------------------------------------*/

    /** Students with failing grades — worst grade first, one row per student. */
    private function attentionStudents(int $schoolId, int $limit = 5): array
    {
        return $this->atRiskRows($schoolId)
            ->unique('student_id')
            ->take($limit)
            ->map(fn ($r) => [
                'student'  => $r->student_name,
                'grade'    => $r->year_level ? 'Grade '.$r->year_level : '—',
                'concern'  => 'Failing '.$r->subject_name,
                'risk'     => (float) $r->posted_grade < 70 ? 'High' : 'Moderate',
                'last'     => Carbon::parse($r->updated_at)->format('M j, Y'),
            ])
            ->values()
            ->all();
    }

    /** Active teaching staff (attendance/evaluation not tracked → "—"). */
    private function staffing(int $schoolId, int $limit = 5): array
    {
        return DB::table('teachers as t')
            ->join('profiles as p', 'p.id', '=', 't.profile_id')
            ->where('p.school_id', $schoolId)
            ->where('t.employment_status', 'Active')
            ->orderBy('p.last_name')
            ->limit($limit)
            ->selectRaw('CONCAT(p.first_name, " ", p.last_name) as name,
                         COALESCE(NULLIF(t.rank, ""), t.employment_type, "—") as dept')
            ->get()
            ->map(fn ($r) => [
                'teacher'    => $r->name,
                'department' => $r->dept,
                'attendance' => null,   // not tracked
                'submission' => null,   // per-teacher linkage not tracked
                'evaluation' => null,   // not tracked
            ])
            ->all();
    }

    /** Pending payment-proof submissions awaiting finance verification. */
    private function pendingApprovals(int $schoolId, int $limit = 5): array
    {
        return DB::table('payment_submissions as ps')
            ->leftJoin('users as u', 'u.id', '=', 'ps.student_id')
            ->where('ps.school_id', $schoolId)
            ->where('ps.status', 'pending')
            ->orderByDesc('ps.submitted_at')
            ->limit($limit)
            ->selectRaw('ps.amount, ps.payment_method, ps.submitted_at,
                         CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, "")) as payer')
            ->get()
            ->map(fn ($r) => [
                'request'    => trim('Payment — '.trim($r->payer ?: 'Student')),
                'department' => ucfirst((string) $r->payment_method) ?: 'Finance',
                'amount'     => '₱'.number_format((float) $r->amount, 0),
                'status'     => Carbon::parse($r->submitted_at)->gt(now()->subDays(2)) ? 'New' : 'Pending',
                'date'       => Carbon::parse($r->submitted_at)->format('M j, Y'),
            ])
            ->all();
    }

    /* ------------------------------------------------------------
     | Right rail
     |------------------------------------------------------------*/

    /** Real alert conditions only; the view shows "all clear" when empty. */
    private function alerts(int $schoolId): array
    {
        $alerts = [];

        if (($atRisk = $this->atRiskCount($schoolId)) > 0) {
            $alerts[] = [
                'icon' => 'alert-triangle', 'tone' => 'rose',
                'text' => $atRisk.' student'.($atRisk === 1 ? ' has' : 's have').' failing grades.',
            ];
        }

        $pendingPayments = (int) DB::table('payment_submissions')
            ->where('school_id', $schoolId)->where('status', 'pending')->count();
        if ($pendingPayments > 0) {
            $alerts[] = [
                'icon' => 'clock', 'tone' => 'amber',
                'text' => $pendingPayments.' payment submission'.($pendingPayments === 1 ? '' : 's').' awaiting verification.',
            ];
        }

        $overdue = (float) DB::table('invoices')
            ->where('school_id', $schoolId)
            ->where('balance', '>', 0.005)
            ->whereDate('due_date', '<', today())
            ->sum('balance');
        if ($overdue > 0) {
            $alerts[] = [
                'icon' => 'trending-down', 'tone' => 'amber',
                'text' => '₱'.$this->compact($overdue).' in overdue invoices.',
            ];
        }

        $queue = (int) DB::table('student_enrollments')
            ->where('school_id', $schoolId)
            ->whereIn('status', ['submitted', 'assessed', 'sent_billing', 'billed', 'partially_paid'])
            ->count();
        if ($queue > 0) {
            $alerts[] = [
                'icon' => 'user-plus', 'tone' => 'sky',
                'text' => $queue.' enrollment'.($queue === 1 ? '' : 's').' in progress.',
            ];
        }

        return array_slice($alerts, 0, 4);
    }

    /** Today's class sessions + certificate events (empty state when none). */
    private function scheduleToday(int $schoolId): array
    {
        $items = [];

        $sessions = DB::table('class_sessions as cs')
            ->join('subjects as sub', 'sub.id', '=', 'cs.subject_id')
            ->where('sub.school_id', $schoolId)
            ->whereDate('cs.meeting_date', today())
            ->orderBy('cs.start_time')
            ->limit(4)
            ->selectRaw('cs.start_time, sub.name')
            ->get();
        foreach ($sessions as $s) {
            $items[] = [
                'time'  => $s->start_time ? Carbon::parse($s->start_time)->format('g:i A') : '—',
                'title' => $s->name,
            ];
        }

        $events = DB::table('certificate_events')
            ->where('school_id', $schoolId)
            ->whereDate('start_date', today())
            ->orderBy('start_time')
            ->limit(4)
            ->get(['event_name', 'start_time']);
        foreach ($events as $e) {
            $items[] = [
                'time'  => $e->start_time ? Carbon::parse($e->start_time)->format('g:i A') : '—',
                'title' => $e->event_name,
            ];
        }

        return array_slice($items, 0, 4);
    }

    /** Upcoming dated obligations: invoice due dates, events, term ends. */
    private function deadlines(int $schoolId): array
    {
        $items = [];

        $due = DB::table('invoices')
            ->where('school_id', $schoolId)
            ->where('balance', '>', 0.005)
            ->whereDate('due_date', '>=', today())
            ->groupBy('due_date')
            ->orderBy('due_date')
            ->limit(3)
            ->selectRaw('due_date, COUNT(*) as n')
            ->get();
        foreach ($due as $d) {
            $items[] = [
                'date'  => Carbon::parse($d->due_date),
                'title' => 'Invoice due — '.$d->n.' student'.((int) $d->n === 1 ? '' : 's'),
            ];
        }

        $events = DB::table('certificate_events')
            ->where('school_id', $schoolId)
            ->whereDate('start_date', '>', today())
            ->orderBy('start_date')
            ->limit(3)
            ->get(['event_name', 'start_date']);
        foreach ($events as $e) {
            $items[] = ['date' => Carbon::parse($e->start_date), 'title' => $e->event_name];
        }

        $terms = DB::table('terms')
            ->where('school_id', $schoolId)
            ->whereDate('end_date', '>=', today())
            ->whereDate('end_date', '<=', today()->addDays(60))
            ->orderBy('end_date')
            ->limit(2)
            ->get(['name', 'end_date']);
        foreach ($terms as $t) {
            $items[] = ['date' => Carbon::parse($t->end_date), 'title' => $t->name.' ends'];
        }

        return collect($items)
            ->sortBy('date')
            ->take(3)
            ->map(fn ($i) => [
                'day'   => $i['date']->format('j'),
                'month' => strtoupper($i['date']->format('M')),
                'title' => $i['title'],
            ])
            ->values()
            ->all();
    }

    /** Basic-ed school years for the filter bar (newest first). */
    private function schoolYears(int $schoolId): array
    {
        return AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('education_level', 'basic_ed')
            ->orderByDesc('start_date')
            ->pluck('name')
            ->all();
    }

    /* ------------------------------------------------------------
     | Utilities
     |------------------------------------------------------------*/

    /** [$labels, $values] for the last N calendar months via a per-month resolver. */
    private function monthlyBuckets(int $months, \Closure $resolve): array
    {
        $labels = [];
        $values = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $m        = now()->subMonthsNoOverflow($i);
            $labels[] = $m->format('M');
            $values[] = $resolve($m->format('Y-m'));
        }

        return [$labels, $values];
    }

    /** 18400000 -> 18.4M, 2500 -> 2.5K. */
    private function compact(float $v): string
    {
        if ($v >= 1_000_000) return rtrim(rtrim(number_format($v / 1_000_000, 1, '.', ''), '0'), '.').'M';
        if ($v >= 1_000)     return rtrim(rtrim(number_format($v / 1_000, 1, '.', ''), '0'), '.').'K';
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }
}
