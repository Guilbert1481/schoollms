<?php

namespace App\Http\Controllers\Finance\Concerns;

use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Support\EducationLevels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Builds the invoices list (rows + columns + actions + status filter) so it can
 * be rendered both on the standalone Invoices page and inside the Billing
 * page's "Invoices" tab.
 */
trait BuildsInvoiceList
{
    /**
     * @return array{rows: \Illuminate\Support\Collection, columns: array, actions: array,
     *     status: string, statuses: array, academicYears: \Illuminate\Support\Collection,
     *     levels: \Illuminate\Support\Collection, programOptions: array, showProgramFilter: bool,
     *     gradeYearOptions: array, paymentPlans: \Illuminate\Support\Collection, invoiceFilters: array}
     */
    protected function invoiceListData(Request $request, bool $onlyBilled = false): array
    {
        $schoolId = (int) auth()->user()->school_id;

        $status  = $request->string('status', 'all')->toString();
        $allowed = ['all', Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_PAID];
        if (! in_array($status, $allowed, true)) {
            $status = 'all';
        }

        // ---- Filters (mirrors the Tuition & Fees toolbar). Academic year is on
        // the invoice; education level / program / grade-year / payment plan all
        // come from the linked enrolment, so they share one whereHas('enrollment').
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $levelId        = $request->integer('education_level') ?: null;
        $gradeYear      = $request->query('grade_year');
        $gradeYear      = ($gradeYear === null || $gradeYear === '') ? null : (string) $gradeYear;
        $programId      = $request->integer('program') ?: null;
        $paymentPlanId  = $request->integer('payment_plan') ?: null;

        $nodeToRoot   = $this->invoiceNodeRootMap();
        $rootNameById = DB::table('education_nodes')->whereNull('parent_id')->pluck('name', 'id')->all();

        // The selected Education Level drives the Grade/Year + Program dropdowns.
        $levelName    = $levelId ? ($rootNameById[$levelId] ?? null) : null;
        $isBasicLevel = $levelName && EducationLevels::isBasic($levelName);

        $showProgramFilter = (bool) ($levelId && ! $isBasicLevel);
        $programId = $showProgramFilter ? $programId : null;
        $programOptions = [];
        if ($showProgramFilter) {
            $programOptions = DB::table('programs')
                ->where('school_id', $schoolId)->orderBy('code')->orderBy('name')
                ->get(['id', 'code', 'name', 'education_node_id'])
                ->filter(fn ($p) => (int) ($nodeToRoot[$p->education_node_id ?? null] ?? 0) === (int) $levelId)
                ->mapWithKeys(fn ($p) => [(int) $p->id => ($p->code ? $p->code.' - ' : '').$p->name])
                ->all();
        }

        // Education_node ids that roll up to the selected level (for the join).
        $levelNodeIds = $levelId
            ? array_keys(array_filter($nodeToRoot, fn ($root) => (int) $root === (int) $levelId))
            : [];

        $invoices = Invoice::query()
            ->where('school_id', $schoolId)
            // Billing page only: show a bill once its billing date has arrived —
            // current + past (incl. all still-unpaid); future installments stay
            // hidden until their billing date.
            ->when($onlyBilled, fn ($q) => $q->billed())
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($academicYearId, fn ($q, $v) => $q->where('academic_year_id', $v))
            ->when($levelId || $programId || $gradeYear || $paymentPlanId, function ($q) use ($levelId, $levelNodeIds, $programId, $gradeYear, $paymentPlanId) {
                $q->whereHas('enrollment', function ($e) use ($levelId, $levelNodeIds, $programId, $gradeYear, $paymentPlanId) {
                    $e->when($levelId, fn ($q) => $q->whereIn('education_node_id', $levelNodeIds ?: [-1]))
                        ->when($programId, fn ($q, $v) => $q->where('program_id', $v))
                        ->when($gradeYear, fn ($q, $v) => $q->where('year_level', (int) $v))
                        ->when($paymentPlanId, fn ($q, $v) => $q->where('payment_plan_id', $v));
                });
            })
            ->with(['student:id,first_name,last_name', 'enrollment:id,term_id'])
            ->orderByDesc('id')
            ->get();

        $rows = $invoices->map(fn (Invoice $inv) => (object) [
            'id'             => $inv->id,
            'invoice_number' => $inv->invoice_number,
            'student'        => $inv->student ? trim($inv->student->first_name.' '.$inv->student->last_name) : '—',
            'total_label'    => 'PHP '.number_format((float) $inv->total_amount, 2),
            'paid_label'     => 'PHP '.number_format((float) $inv->paid_amount, 2),
            'balance_label'  => 'PHP '.number_format((float) $inv->balance, 2),
            'due'            => optional($inv->due_date)->format('M d, Y') ?? '—',
            'status'         => $this->invoiceStatusPill($inv),
        ]);

        // Grade / Year Level options — dynamic to the selected Education Level:
        // Basic -> Grade N (from the tree); higher-ed -> Year N; none -> Grade 1..12.
        if ($levelId && $isBasicLevel) {
            $gradeYearOptions = EducationLevels::basicGradeOptions();
        } elseif ($levelId) {
            $gradeYearOptions = EducationLevels::yearLevelOptions($levelId);
        } else {
            $gradeYearOptions = [];
            for ($g = 1; $g <= 12; $g++) {
                $gradeYearOptions[(string) $g] = 'Grade '.$g;
            }
        }

        return [
            'rows'     => $rows,
            'columns'  => $this->invoiceColumns(),
            'actions'  => $this->invoiceActions(),
            'status'   => $status,
            'statuses' => [
                'all'                   => 'All',
                Invoice::STATUS_UNPAID  => 'Unpaid',
                Invoice::STATUS_PARTIAL => 'Partial',
                Invoice::STATUS_PAID    => 'Paid',
            ],

            // Filter toolbar lookups + current selections.
            'academicYears'     => DB::table('academic_years')->where('school_id', $schoolId)
                ->orderByDesc('start_date')->get(['id', 'name']),
            'levels'            => DB::table('education_nodes')->whereNull('parent_id')
                ->where('is_offered', 1)->where('is_active', 1)->orderBy('order_index')->get(['id', 'name']),
            'programOptions'    => $programOptions,
            'showProgramFilter' => $showProgramFilter,
            'gradeYearOptions'  => $gradeYearOptions,
            'paymentPlans'      => PaymentPlan::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
            'invoiceFilters'    => [
                'academic_year_id' => $academicYearId,
                'education_level'  => $levelId,
                'grade_year'       => $gradeYear,
                'program'          => $programId,
                'payment_plan'     => $paymentPlanId,
            ],
        ];
    }

    /** Map every education_node id to the id of its root ancestor. */
    protected function invoiceNodeRootMap(): array
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

    protected function invoiceStatusPill(Invoice $invoice): string
    {
        if ($invoice->isOverdue()) {
            return '<span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">Overdue</span>';
        }

        return match ($invoice->status) {
            Invoice::STATUS_PAID    => '<span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Paid</span>',
            Invoice::STATUS_PARTIAL => '<span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">Partial</span>',
            default                 => '<span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">Unpaid</span>',
        };
    }

    protected function invoiceColumns(): array
    {
        return [
            ['key' => 'invoice_number', 'label' => 'Invoice #', 'width' => '180px'],
            ['key' => 'student', 'label' => 'Student', 'width' => '220px'],
            ['key' => 'total_label', 'label' => 'Total', 'width' => '140px'],
            ['key' => 'paid_label', 'label' => 'Paid', 'width' => '140px'],
            ['key' => 'balance_label', 'label' => 'Balance', 'width' => '140px'],
            ['key' => 'due', 'label' => 'Due', 'width' => '130px'],
            ['key' => 'status', 'label' => 'Status', 'width' => '120px', 'raw' => true],
        ];
    }

    protected function invoiceActions(): array
    {
        return [
            // Opens the invoice detail in a modal (openInvoiceModal is defined in
            // finance/invoices/_list.blade.php) instead of navigating to a page.
            ['type' => 'js', 'label' => 'View', 'handler' => 'openInvoiceModal', 'class' => 'bg-indigo-600 text-white hover:bg-indigo-700'],
        ];
    }
}
