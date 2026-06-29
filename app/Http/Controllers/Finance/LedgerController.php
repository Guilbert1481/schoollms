<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceSetting;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\StatementOfAccount;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Finance\LedgerService;
use App\Support\EducationLevels;
use App\Support\EnrollmentStatuses;
use App\Support\PaymentStatuses;
use App\Support\Spreadsheet;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Finance → Student Ledgers.
 *
 * Mirrors the registrar's Student Registry layout (education-level tabs +
 * dropdown filters), but the columns and KPIs are finance-centric: every
 * enrolled student's outstanding balance, account status (paid / current /
 * due soon / overdue) and last payment. Drilling into a row opens that
 * student's full account (ledger entries, invoices, Statements of Account).
 */
class LedgerController extends Controller
{
    /** Currency symbol resolved from FinanceSetting (₱ for PHP). */
    private string $currency = '₱';

    public function __construct(private readonly LedgerService $ledger)
    {
    }

    public function index(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;
        $this->currency = $this->currencySymbol($schoolId);

        // Top-level education levels offered by the school (tabs).
        $levels = DB::table('education_nodes')
            ->whereNull('parent_id')
            ->where('is_offered', 1)
            ->where('is_active', 1)
            ->orderBy('order_index')
            ->get(['id', 'name']);

        $nodeToRoot   = $this->buildNodeRootMap();
        $showTabs     = $levels->count() > 1;
        $rootNameById = $levels->pluck('name', 'id')->all();

        $levelParam    = $request->query('level');
        $showAll       = $levelParam === null || $levelParam === '' || strtolower((string) $levelParam) === 'all';
        $activeLevelId = $showAll ? 0 : (int) $levelParam;

        // Status filter — payment status (not enrollment status). Defaults to All.
        $statusFilter  = $request->query('status') ?: 'all';
        $statusOptions = PaymentStatuses::options();
        if ($statusFilter !== 'all' && ! array_key_exists($statusFilter, $statusOptions)) {
            $statusFilter = 'all';
        }

        $academicYearId = $request->integer('academic_year_id') ?: null;
        $yearLevelFilter = $request->query('year_level');
        $yearLevelFilter = ($yearLevelFilter === null || $yearLevelFilter === '') ? null : (string) $yearLevelFilter;
        $programId = $request->integer('program_id') ?: null;
        $sectionId = $request->integer('section_id') ?: null;

        // Same population as the registry: latest enrollment per student, ledger
        // statuses only. Finance is user-keyed, so we require a linked account.
        $rows = $this->ledgerSelectRows($schoolId)
            ->filter(fn ($r) => $r->user_id)
            ->values();

        $userIds = $rows->pluck('user_id')->map(fn ($v) => (int) $v)->unique()->all();
        $finance = $this->financeByUser($schoolId, $userIds);

        // KPI cards — school-wide totals (independent of the active tab/filters).
        $kpi = $this->financeKpis($schoolId, $rows->count());

        $items = $rows->map(fn ($r) => $this->ledgerItemFrom($r, $nodeToRoot, $rootNameById, $finance));

        if ($statusFilter !== 'all') {
            $items = $items->filter(fn ($i) => $i->payment_status_key === $statusFilter)->values();
        }

        // Restrict to the active level (tab), then layer on the dropdown filters.
        $scoped = $showAll
            ? $items
            : $items->filter(fn ($i) => (int) ($i->root ?? 0) === $activeLevelId)->values();

        $activeLevel    = $levels->firstWhere('id', $activeLevelId);
        $singleLevel    = $levels->count() === 1 ? $levels->first() : null;
        $effectiveLevel = $showAll ? $singleLevel : $activeLevel;
        $activeLevelIsBasic = $effectiveLevel
            && str_contains(strtolower((string) $effectiveLevel->name), 'basic');

        // Academic-year filter options (from the active level scope).
        $academicYears = $scoped
            ->filter(fn ($i) => $i->academic_year_id)
            ->unique('academic_year_id')
            ->sortByDesc('academic_year_name')
            ->mapWithKeys(fn ($i) => [(int) $i->academic_year_id => $i->academic_year_name])
            ->all();

        $afterAy = $academicYearId
            ? $scoped->filter(fn ($i) => (int) $i->academic_year_id === $academicYearId)->values()
            : $scoped;

        // Grade / Year-level filter options, sourced from the education tree.
        $dataYearLevels = fn () => $afterAy
            ->filter(fn ($i) => $i->year_level !== null && $i->year_level !== '')
            ->mapWithKeys(fn ($i) => [(string) (int) $i->year_level => 'Year '.(int) $i->year_level])
            ->all();

        if ($activeLevelIsBasic) {
            $yearLevelOptions = EducationLevels::basicGradeOptions();
        } elseif (! $showAll && $activeLevelId > 0) {
            $yearLevelOptions = EducationLevels::yearLevelOptions($activeLevelId) + $dataYearLevels();
            ksort($yearLevelOptions, SORT_NUMERIC);
        } else {
            $yearLevelOptions = $dataYearLevels();
            ksort($yearLevelOptions, SORT_NUMERIC);
        }

        $finalRows = $afterAy
            ->when($yearLevelFilter !== null, fn ($c) => $c->filter(fn ($i) => (string) $i->year_level === $yearLevelFilter))
            ->when($programId, fn ($c) => $c->filter(fn ($i) => (int) $i->program_id === (int) $programId))
            ->when($sectionId, fn ($c) => $c->filter(fn ($i) => (int) $i->section_id === (int) $sectionId))
            ->map(fn ($i) => $i->display)
            ->values();

        $columns = $this->columnsFor($activeLevelIsBasic, $showAll);

        // Program filter — higher-ed levels only (Basic Ed has no programs).
        $showProgramFilter = ! $showAll && ! $activeLevelIsBasic && $activeLevelId > 0;
        $programOptions = [];
        if ($showProgramFilter) {
            $nodeRoot = EducationLevels::nodeRootMap();
            $programOptions = DB::table('programs')
                ->where('school_id', $schoolId)
                ->orderBy('code')->orderBy('name')
                ->get(['id', 'code', 'name', 'education_node_id'])
                ->filter(fn ($p) => (int) ($nodeRoot[$p->education_node_id ?? null] ?? 0) === $activeLevelId)
                ->mapWithKeys(fn ($p) => [(int) $p->id => $p->code ?: $p->name])
                ->all();
        }

        // Section filter options — active sections (shown on the Basic Ed tab).
        $sectionOptions = DB::table('sections')
            ->where('school_id', $schoolId)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($s) => [(int) $s->id => $s->name])
            ->all();

        $tableEmptyMessage = ($showProgramFilter && empty($programOptions))
            ? 'No programs have been set up for this level yet.'
            : 'No student accounts for this selection yet.';

        // Per-row action — open the student's full ledger account.
        $actions = [[
            'type'  => 'link',
            'name'  => 'open-ledger',
            'label' => 'Open Ledger',
            'route' => 'finance.ledger.show',
            'icon'  => 'wallet',
            'class' => 'text-indigo-600 hover:bg-indigo-50',
        ]];

        return view('finance.ledger.index', [
            'columns'            => $columns,
            'levels'             => $levels,
            'activeLevelId'      => $activeLevelId,
            'showAll'            => $showAll,
            'showTabs'           => $showTabs,
            'rows'               => $finalRows,
            'kpi'                => $kpi,
            'currency'           => $this->currency,
            'academicYears'      => $academicYears,
            'yearLevelOptions'   => $yearLevelOptions,
            'academicYearId'     => $academicYearId,
            'yearLevel'          => $yearLevelFilter,
            'activeLevelIsBasic' => $activeLevelIsBasic,
            'programOptions'     => $programOptions,
            'programId'          => $programId,
            'showProgramFilter'  => $showProgramFilter,
            'sectionOptions'     => $sectionOptions,
            'sectionId'          => $sectionId,
            'statusOptions'      => $statusOptions,
            'statusFilter'       => $statusFilter,
            'actions'            => $actions,
            'tableEmptyMessage'  => $tableEmptyMessage,
        ]);
    }

    public function show(User $student)
    {
        $schoolId = (int) auth()->user()->school_id;
        abort_unless((int) $student->school_id === $schoolId && $student->role === 'student', 404);

        $profile = Student::query()->where('school_id', $schoolId)->where('user_id', $student->id)->first();

        $entries = LedgerEntry::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->orderBy('id')
            ->get();

        $invoices = Invoice::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->orderByDesc('id')
            ->get();

        $statements = StatementOfAccount::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->orderByDesc('id')
            ->get();

        // Enrollments that could still be invoiced (drives "Generate Invoice").
        $enrollments = collect();
        if ($profile) {
            $invoicedEnrollmentIds = $invoices->pluck('student_enrollment_id')->filter()->all();
            $enrollments = StudentEnrollment::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $profile->id)
                ->with(['term:id,name', 'program:id,code,name', 'academicYear:id,name'])
                ->orderByDesc('id')
                ->get()
                ->map(function (StudentEnrollment $e) use ($invoicedEnrollmentIds) {
                    $e->has_invoice = in_array($e->id, $invoicedEnrollmentIds, true);

                    return $e;
                });
        }

        $setting = FinanceSetting::forSchool($schoolId);

        return view('finance.ledger.show', [
            'student'     => $student,
            'profile'     => $profile,
            'entries'     => $entries,
            'invoices'    => $invoices,
            'statements'  => $statements,
            'enrollments' => $enrollments,
            'balance'     => $this->ledger->currentBalance($schoolId, (int) $student->id),
            'setting'     => $setting,
        ]);
    }

    public function adjust(Request $request, User $student)
    {
        $schoolId = (int) auth()->user()->school_id;
        abort_unless((int) $student->school_id === $schoolId && $student->role === 'student', 404);

        $data = $request->validate([
            'direction'   => ['required', 'in:debit,credit'],
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'description' => ['required', 'string', 'max:191'],
        ]);

        $amount = round((float) $data['amount'], 2);
        $this->ledger->postAdjustment(
            schoolId: $schoolId,
            studentId: (int) $student->id,
            debit: $data['direction'] === 'debit' ? $amount : 0,
            credit: $data['direction'] === 'credit' ? $amount : 0,
            description: $data['description'],
            actorId: (int) auth()->id(),
        );

        return back()->with('success', 'Ledger adjustment posted.');
    }

    /**
     * Export the currently-filtered ledger list as CSV or Excel (.xlsx),
     * honouring the same level / status / AY / year / program / section filters.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->query('format')) === 'xlsx' ? 'xlsx' : 'csv';
        [$headers, $rows, $title] = $this->buildExportRows($request);

        $base = Str::slug($title ?: 'student-ledgers').'-'.now()->format('Ymd-His');

        if ($format === 'xlsx') {
            $path = Spreadsheet::xlsx($headers, $rows, 'Ledgers');

            return response()->download($path, $base.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads ₱/accents correctly
            fputcsv($out, $headers);
            foreach ($rows as $r) {
                fputcsv($out, array_values($r));
            }
            fclose($out);
        }, $base.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Build [headers, rows, title] for the export, mirroring the table filters. */
    protected function buildExportRows(Request $request): array
    {
        $schoolId = (int) auth()->user()->school_id;
        $this->currency = $this->currencySymbol($schoolId);

        $levels = DB::table('education_nodes')
            ->whereNull('parent_id')->where('is_offered', 1)->where('is_active', 1)
            ->orderBy('order_index')->get(['id', 'name']);
        $nodeToRoot   = $this->buildNodeRootMap();
        $rootNameById = $levels->pluck('name', 'id')->all();

        $levelParam    = $request->query('level');
        $showAll       = $levelParam === null || $levelParam === '' || strtolower((string) $levelParam) === 'all';
        $activeLevelId = $showAll ? 0 : (int) $levelParam;
        $activeLevel   = $levels->firstWhere('id', $activeLevelId);
        $singleLevel   = $levels->count() === 1 ? $levels->first() : null;
        $effective     = $showAll ? $singleLevel : $activeLevel;
        $isBasic       = $effective && str_contains(strtolower((string) $effective->name), 'basic');

        $statusFilter = $request->query('status') ?: 'all';
        if ($statusFilter !== 'all' && ! PaymentStatuses::isValid($statusFilter)) {
            $statusFilter = 'all';
        }

        $academicYearId  = $request->integer('academic_year_id') ?: null;
        $yearLevelFilter = $request->query('year_level');
        $yearLevelFilter = ($yearLevelFilter === null || $yearLevelFilter === '') ? null : (string) $yearLevelFilter;
        $programId       = $request->integer('program_id') ?: null;
        $sectionId       = $request->integer('section_id') ?: null;

        $rows = $this->ledgerSelectRows($schoolId)->filter(fn ($r) => $r->user_id)->values();
        $userIds = $rows->pluck('user_id')->map(fn ($v) => (int) $v)->unique()->all();
        $finance = $this->financeByUser($schoolId, $userIds);

        $items = $rows->map(fn ($r) => $this->ledgerItemFrom($r, $nodeToRoot, $rootNameById, $finance));

        if ($statusFilter !== 'all') {
            $items = $items->filter(fn ($i) => $i->payment_status_key === $statusFilter);
        }

        $items = $items
            ->when(! $showAll, fn ($c) => $c->filter(fn ($i) => (int) ($i->root ?? 0) === $activeLevelId))
            ->when($academicYearId, fn ($c) => $c->filter(fn ($i) => (int) $i->academic_year_id === $academicYearId))
            ->when($yearLevelFilter !== null, fn ($c) => $c->filter(fn ($i) => (string) $i->year_level === $yearLevelFilter))
            ->when($programId, fn ($c) => $c->filter(fn ($i) => (int) $i->program_id === (int) $programId))
            ->when($sectionId, fn ($c) => $c->filter(fn ($i) => (int) $i->section_id === (int) $sectionId))
            ->values();

        if ($isBasic) {
            $headers = ['Student Name', 'Student ID', 'Grade & Section', 'Total Balance', 'Status', 'Last Payment'];
            $rowsOut = $items->map(fn ($i) => [
                $i->display->full_name,
                $i->display->student_id,
                $i->display->grade_section,
                $this->currency.number_format($i->balance, 2),
                PaymentStatuses::label($i->payment_status_key),
                $i->display->last_payment,
            ])->all();
            $title = $effective->name ?? 'Student Ledgers';
        } elseif ($showAll) {
            $headers = ['Student Name', 'Student ID', 'Level', 'Year/Grade', 'Total Balance', 'Status', 'Last Payment'];
            $rowsOut = $items->map(fn ($i) => [
                $i->display->full_name,
                $i->display->student_id,
                $i->display->level,
                $i->display->year_term,
                $this->currency.number_format($i->balance, 2),
                PaymentStatuses::label($i->payment_status_key),
                $i->display->last_payment,
            ])->all();
            $title = 'All Levels';
        } else {
            $headers = ['Student Name', 'Student ID', 'Program', 'Year Level & Term', 'Total Balance', 'Status', 'Last Payment'];
            $rowsOut = $items->map(fn ($i) => [
                $i->display->full_name,
                $i->display->student_id,
                $i->display->program,
                $i->display->year_term,
                $this->currency.number_format($i->balance, 2),
                PaymentStatuses::label($i->payment_status_key),
                $i->display->last_payment,
            ])->all();
            $title = $activeLevel->name ?? 'Student Ledgers';
        }

        return [$headers, $rowsOut, $title];
    }

    /**
     * Base query: latest enrollment per student, scoped to ledger (official +
     * post-enrollment) statuses. Includes the linked user id so finance data can
     * be joined (ledger_entries / payments / invoices are keyed by users.id).
     */
    protected function ledgerSelectRows(int $schoolId): \Illuminate\Support\Collection
    {
        $ledgerEnrollDb  = EnrollmentStatuses::dbValuesForGroup('ledger');
        $ledgerStudentDb = EnrollmentStatuses::studentDbValuesForGroup('ledger');

        return DB::table('students as st')
            ->join('student_enrollments as se', 'se.id', '=', DB::raw(
                '(select max(id) from student_enrollments where student_id = st.id)'
            ))
            ->leftJoin('programs as p', 'p.id', '=', 'se.program_id')
            ->leftJoin('terms as t', 't.id', '=', 'se.term_id')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 'se.academic_year_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'se.section_id')
            ->where('st.school_id', $schoolId)
            ->where(function ($w) use ($ledgerEnrollDb, $ledgerStudentDb) {
                $w->whereIn('se.status', $ledgerEnrollDb)
                  ->orWhereIn('st.status', $ledgerStudentDb);
            })
            ->orderBy('st.last_name')
            ->orderBy('st.first_name')
            ->get([
                'st.id as student_id',
                'st.user_id',
                'st.student_number',
                'st.first_name',
                'st.middle_name',
                'st.last_name',
                'se.year_level',
                'se.education_node_id',
                'se.academic_year_id',
                'se.program_id',
                'se.section_id',
                'sec.name as section_name',
                'p.code as program_code',
                'p.name as program_name',
                'p.education_node_id as program_node_id',
                't.name as term_name',
                'ay.name as academic_year_name',
            ]);
    }

    /** Shape one raw row into the item used for filtering + finance display. */
    protected function ledgerItemFrom($r, array $nodeToRoot, array $rootNameById, array $finance): object
    {
        $rootLevelId = $nodeToRoot[$r->education_node_id ?? null]
            ?? $nodeToRoot[$r->program_node_id ?? null]
            ?? null;

        $isBasic = $rootLevelId
            && str_contains(strtolower((string) ($rootNameById[$rootLevelId] ?? '')), 'basic');

        $fullName = trim(implode(' ', array_filter([
            $r->first_name, $r->middle_name, $r->last_name,
        ]))) ?: '—';

        $uid = (int) ($r->user_id ?? 0);
        $fin = $finance[$uid] ?? ['balance' => 0.0, 'status_key' => 'paid', 'last_payment' => '—'];

        // Grade / Year label.
        $yearNum = is_numeric($r->year_level) ? (int) $r->year_level : null;
        $gradeLabel = $yearNum !== null
            ? ($isBasic ? 'Grade '.$yearNum : 'Year '.$yearNum)
            : (($r->year_level !== null && $r->year_level !== '') ? (string) $r->year_level : '—');

        $section   = $r->section_name ?: null;
        $termClean = $this->stripAcademicYear($r->term_name);

        // Basic Ed: "Grade & Section". Higher-ed: "Year Level & Term" (no AY).
        $gradeSection = $isBasic
            ? ($section ? $gradeLabel.' · '.$section : $gradeLabel)
            : $gradeLabel;
        $yearTerm = $isBasic
            ? $gradeLabel
            : ($termClean !== '' ? $gradeLabel.' · '.$termClean : $gradeLabel);

        return (object) [
            'root'               => $rootLevelId,
            'year_level'         => $r->year_level,
            'academic_year_id'   => $r->academic_year_id,
            'academic_year_name' => $r->academic_year_name,
            'program_id'         => $r->program_id,
            'section_id'         => $r->section_id,
            'payment_status_key' => $fin['status_key'],
            'balance'            => (float) $fin['balance'],
            'display'            => (object) [
                'id'             => $uid,
                'full_name'      => $fullName,
                'student_id'     => $r->student_number ?: '—',
                'program'        => $r->program_code ?: ($r->program_name ?? '—'),
                'level'          => ($rootLevelId && isset($rootNameById[$rootLevelId])) ? $rootNameById[$rootLevelId] : '—',
                'grade_section'  => $gradeSection,
                'year_term'      => $yearTerm,
                'total_balance'  => $this->balanceCell((float) $fin['balance']),
                'payment_status' => PaymentStatuses::pill($fin['status_key']),
                'last_payment'   => $fin['last_payment'],
            ],
        ];
    }

    /**
     * Per-student finance snapshot keyed by user id:
     *   [user_id => ['balance' => float, 'status_key' => string, 'last_payment' => string]]
     */
    protected function financeByUser(int $schoolId, array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        // Outstanding balance = SUM(charges) − SUM(payments) from the ledger.
        $balances = DB::table('ledger_entries')
            ->select('student_id', DB::raw('SUM(debit) - SUM(credit) as bal'))
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $userIds)
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        // Overdue / due-soon flags from open invoices.
        $today = now()->toDateString();
        $soon  = now()->addDays(7)->toDateString();
        $inv = DB::table('invoices')
            ->selectRaw(
                'student_id,'
                .' SUM(CASE WHEN balance > 0.005 AND due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END) as overdue_cnt,'
                .' SUM(CASE WHEN balance > 0.005 AND due_date IS NOT NULL AND due_date >= ? AND due_date <= ? THEN 1 ELSE 0 END) as soon_cnt',
                [$today, $today, $soon]
            )
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $userIds)
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        // Most recent payment per student.
        $lastPay = DB::table('payments')
            ->select('student_id', 'amount', 'paid_at')
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $userIds)
            ->orderBy('student_id')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('student_id')
            ->map(fn ($g) => $g->first());

        $out = [];
        foreach ($userIds as $uid) {
            $balance    = round((float) ($balances[$uid]->bal ?? 0), 2);
            $hasOverdue = (int) ($inv[$uid]->overdue_cnt ?? 0) > 0;
            $hasDueSoon = (int) ($inv[$uid]->soon_cnt ?? 0) > 0;
            $lp         = $lastPay[$uid] ?? null;

            $out[$uid] = [
                'balance'      => $balance,
                'status_key'   => PaymentStatuses::resolve($balance, $hasOverdue, $hasDueSoon),
                'last_payment' => $lp
                    ? $this->currency.number_format((float) $lp->amount, 2).' · '.Carbon::parse($lp->paid_at)->format('M d, Y')
                    : '—',
            ];
        }

        return $out;
    }

    /** School-wide KPI figures for the summary cards. */
    protected function financeKpis(int $schoolId, int $activeStudents): array
    {
        // Total outstanding = sum of positive per-student balances.
        $outstanding = 0.0;
        DB::table('ledger_entries')
            ->select('student_id', DB::raw('SUM(debit) - SUM(credit) as bal'))
            ->where('school_id', $schoolId)
            ->groupBy('student_id')
            ->get()
            ->each(function ($r) use (&$outstanding) {
                $v = (float) $r->bal;
                if ($v > 0.005) {
                    $outstanding += $v;
                }
            });

        // Collection this calendar month.
        $collection = (float) DB::table('payments')
            ->where('school_id', $schoolId)
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        // Distinct students with at least one overdue invoice.
        $overdue = (int) DB::table('invoices')
            ->where('school_id', $schoolId)
            ->where('balance', '>', 0.005)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->distinct()
            ->count('student_id');

        return [
            'students'    => $activeStudents,
            'outstanding' => round($outstanding, 2),
            'collection'  => round($collection, 2),
            'overdue'     => $overdue,
            'month_label' => now()->format('F Y'),
        ];
    }

    /** Column set for the active view (Basic Ed / higher-ed / All Levels). */
    protected function columnsFor(bool $isBasic, bool $showAll): array
    {
        if ($showAll) {
            return [
                ['key' => 'full_name',      'label' => 'Student Name',  'width' => '200px'],
                ['key' => 'student_id',     'label' => 'Student ID',    'width' => '130px'],
                ['key' => 'level',          'label' => 'Level',         'width' => '170px'],
                ['key' => 'year_term',      'label' => 'Year/Grade',    'width' => '150px'],
                ['key' => 'total_balance',  'label' => 'Total Balance', 'width' => '150px', 'raw' => true],
                ['key' => 'payment_status', 'label' => 'Status',        'width' => '120px', 'raw' => true],
                ['key' => 'last_payment',   'label' => 'Last Payment',  'width' => '190px'],
            ];
        }

        if ($isBasic) {
            return [
                ['key' => 'full_name',      'label' => 'Student Name',    'width' => '200px'],
                ['key' => 'student_id',     'label' => 'Student ID',      'width' => '130px'],
                ['key' => 'grade_section',  'label' => 'Grade & Section', 'width' => '170px'],
                ['key' => 'total_balance',  'label' => 'Total Balance',   'width' => '150px', 'raw' => true],
                ['key' => 'payment_status', 'label' => 'Status',          'width' => '120px', 'raw' => true],
                ['key' => 'last_payment',   'label' => 'Last Payment',    'width' => '190px'],
            ];
        }

        return [
            ['key' => 'full_name',      'label' => 'Student Name',       'width' => '200px'],
            ['key' => 'student_id',     'label' => 'Student ID',         'width' => '130px'],
            ['key' => 'program',        'label' => 'Program',            'width' => '150px'],
            ['key' => 'year_term',      'label' => 'Year Level & Term',  'width' => '180px'],
            ['key' => 'total_balance',  'label' => 'Total Balance',      'width' => '150px', 'raw' => true],
            ['key' => 'payment_status', 'label' => 'Status',             'width' => '120px', 'raw' => true],
            ['key' => 'last_payment',   'label' => 'Last Payment',       'width' => '190px'],
        ];
    }

    /** Total Balance cell — coloured amount (rose = owes, emerald = settled). */
    protected function balanceCell(float $balance): string
    {
        if ($balance > 0.005) {
            return '<span class="font-bold text-rose-600">'.$this->currency.number_format($balance, 2).'</span>';
        }
        if ($balance < -0.005) {
            return '<span class="font-bold text-sky-600">'.$this->currency.number_format(abs($balance), 2).' cr</span>';
        }

        return '<span class="font-bold text-emerald-600">'.$this->currency.'0.00</span>';
    }

    /** Strip a trailing/embedded academic year (e.g. "2nd Sem 2026-2027" → "2nd Sem"). */
    protected function stripAcademicYear(?string $term): string
    {
        $t = trim((string) $term);
        if ($t === '') {
            return '';
        }
        $stripped = preg_replace('/\s*\(?\s*(?:A\.?Y\.?\s*)?\d{4}\s*[-\x{2013}]\s*\d{4}\s*\)?/iu', '', $t);

        return trim((string) $stripped) ?: $t;
    }

    /** Root level for every node id (walks parents to the top). */
    protected function buildNodeRootMap(): array
    {
        $all = DB::table('education_nodes')->get(['id', 'parent_id'])->keyBy('id');

        $rootOf = [];
        foreach ($all as $id => $node) {
            $cur = $node;
            for ($i = 0; $i < 32 && $cur && $cur->parent_id; $i++) {
                $cur = $all[$cur->parent_id] ?? null;
            }
            $rootOf[$id] = $cur?->id;
        }

        return $rootOf;
    }

    /** Currency symbol for the school (₱ for PHP, otherwise the code + space). */
    protected function currencySymbol(int $schoolId): string
    {
        $code = strtoupper((string) (FinanceSetting::forSchool($schoolId)->currency ?? 'PHP'));

        return $code === 'PHP' ? '₱' : $code.' ';
    }
}
