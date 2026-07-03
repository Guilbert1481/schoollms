<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceDiscountType;
use App\Models\FinanceFeeSetup;
use App\Models\PaymentPlan;
use App\Models\PenaltyRule;
use App\Models\Scholarship;
use App\Support\EducationLevels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TuitionSetupController extends Controller
{
    /** Tab key => [label, lucide icon, add-button label]. */
    public const TABS = [
        'tuition'       => ['label' => 'Tuition Matrix',      'icon' => 'layout-grid',        'add' => 'Add Tuition Rate'],
        'miscellaneous' => ['label' => 'Miscellaneous Fees',  'icon' => 'circle-dollar-sign', 'add' => 'Add Fee'],
        'other'         => ['label' => 'Other Fees',          'icon' => 'file-text',          'add' => 'Add Fee'],
        'discounts'     => ['label' => 'Discounts',           'icon' => 'badge-percent',      'add' => 'Add Discount'],
        'scholarships'  => ['label' => 'Scholarships',        'icon' => 'graduation-cap',     'add' => 'Add Scholarship'],
        'payment-plans' => ['label' => 'Payment Plans',       'icon' => 'credit-card',        'add' => 'Add Payment Plan'],
        'penalty-rules' => ['label' => 'Penalty Rules',       'icon' => 'shield-alert',       'add' => 'Add Penalty Rule'],
    ];

    public function index(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;

        $tab = $request->query('tab', 'tuition');
        if (! array_key_exists($tab, self::TABS)) {
            $tab = 'tuition';
        }

        // Tuition Matrix filters.
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $levelId        = $request->integer('education_level') ?: null;
        $gradeYear      = $request->query('grade_year');
        $gradeYear      = ($gradeYear === null || $gradeYear === '') ? null : (string) $gradeYear;
        $paymentPlanId  = $request->integer('payment_plan') ?: null;
        $programId      = $request->integer('program') ?: null;

        $nodeToRoot   = $this->buildNodeRootMap();
        $rootNameById = DB::table('education_nodes')->whereNull('parent_id')->pluck('name', 'id')->all();

        // The selected Education Level drives the Grade/Year + Program dropdowns.
        $levelName    = $levelId ? ($rootNameById[$levelId] ?? null) : null;
        $isBasicLevel = $levelName && EducationLevels::isBasic($levelName);

        // Program dropdown only appears for a specific non-basic level.
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

        [$rows, $columns] = $this->tabData($tab, $schoolId, $nodeToRoot, $rootNameById, [
            'academic_year_id' => $academicYearId,
            'education_level'  => $levelId,
            'grade_year'       => $gradeYear,
            'payment_plan'     => $paymentPlanId,
            'program'          => $programId,
        ]);

        // Shared lookups for filters + modal selects.
        $academicYears = DB::table('academic_years')->where('school_id', $schoolId)
            ->orderByDesc('start_date')->get(['id', 'name']);
        $terms = DB::table('terms')->where('school_id', $schoolId)
            ->orderByDesc('is_current')->orderByDesc('start_date')->get(['id', 'name', 'academic_year_id']);
        $levels = DB::table('education_nodes')->whereNull('parent_id')
            ->where('is_offered', 1)->where('is_active', 1)->orderBy('order_index')->get(['id', 'name']);
        $paymentPlans = PaymentPlan::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name', 'is_active']);
        $programs = DB::table('programs')->where('school_id', $schoolId)->orderBy('code')->orderBy('name')->get(['id', 'code', 'name']);

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

        return view('finance.tuition_setup', [
            'tabs'             => self::TABS,
            'tab'              => $tab,
            'rows'             => $rows,
            'columns'          => $columns,
            'actions'          => $this->actionsFor($tab),
            'addLabel'         => self::TABS[$tab]['add'],

            // filters
            'academicYears'    => $academicYears,
            'levels'           => $levels,
            'gradeYearOptions' => $gradeYearOptions,
            'paymentPlans'     => $paymentPlans,
            'filters'          => [
                'academic_year_id' => $academicYearId,
                'education_level'  => $levelId,
                'grade_year'       => $gradeYear,
                'payment_plan'     => $paymentPlanId,
                'program'          => $programId,
            ],
            'showProgramFilter' => $showProgramFilter,
            'programOptions'    => $programOptions,

            // modal lookups
            'terms'            => $terms,
            'programs'         => $programs,
            'educationNodes'   => $this->educationNodeOptions(),
            'feeTypes'         => FinanceFeeSetup::FEE_TYPES,
            'billingBases'     => FinanceFeeSetup::BILLING_BASES,
            'discountKinds'    => FinanceDiscountType::DISCOUNT_KINDS,
            'discountAppliesTo' => FinanceDiscountType::APPLIES_TO,
            'scholarshipKinds' => Scholarship::KINDS,
            'scholarshipCoverage' => Scholarship::COVERAGE,
            'penaltyBases'     => PenaltyRule::BASES,
            'planFrequencies'  => PaymentPlan::FREQUENCIES,
            'planValueTypes'   => PaymentPlan::VALUE_TYPES,

            // edit payload for the active tab
            'editPayload'      => $this->editPayload($tab, $schoolId),
        ]);
    }

    /* ===================================================================
     | Per-tab row + column builders
     * =================================================================== */

    protected function tabData(string $tab, int $schoolId, array $nodeToRoot, array $rootNameById, array $filters): array
    {
        return match ($tab) {
            'tuition'       => [$this->feeRows($schoolId, ['tuition'], $nodeToRoot, $rootNameById, $filters, true), $this->tuitionColumns()],
            'miscellaneous' => [$this->feeRows($schoolId, ['miscellaneous'], $nodeToRoot, $rootNameById, $filters, false), $this->feeColumns()],
            'other'         => [$this->feeRows($schoolId, ['other', 'registration', 'laboratory'], $nodeToRoot, $rootNameById, $filters, false), $this->feeColumns()],
            'discounts'     => [$this->discountRows($schoolId), $this->discountColumns()],
            'scholarships'  => [$this->scholarshipRows($schoolId), $this->scholarshipColumns()],
            'payment-plans' => [$this->paymentPlanRows($schoolId), $this->paymentPlanColumns()],
            'penalty-rules' => [$this->penaltyRows($schoolId), $this->penaltyColumns()],
        };
    }

    /** Fee rows (tuition / misc / other). $withPlan adds the Payment Plan column data. */
    protected function feeRows(int $schoolId, array $types, array $nodeToRoot, array $rootNameById, array $filters, bool $withPlan)
    {
        return FinanceFeeSetup::query()
            ->with(['program:id,code,name', 'paymentPlan:id,name', 'educationNode:id,name,parent_id'])
            ->where('school_id', $schoolId)
            ->whereIn('fee_type', $types)
            ->when($filters['academic_year_id'] ?? null, fn ($q, $v) => $q->where('academic_year_id', $v))
            ->when($filters['grade_year'] ?? null, fn ($q, $v) => $q->where('year_level', (int) $v))
            ->when($filters['payment_plan'] ?? null, fn ($q, $v) => $q->where('payment_plan_id', $v))
            ->when($filters['program'] ?? null, fn ($q, $v) => $q->where('program_id', $v))
            ->orderByDesc('is_active')->orderBy('code')
            ->get()
            ->filter(function (FinanceFeeSetup $fee) use ($nodeToRoot, $filters) {
                if (empty($filters['education_level'])) {
                    return true;
                }
                $root = $nodeToRoot[$fee->education_node_id ?? null] ?? null;

                return (int) $root === (int) $filters['education_level'];
            })
            ->map(function (FinanceFeeSetup $fee) use ($nodeToRoot, $rootNameById, $withPlan) {
                $rootId   = $nodeToRoot[$fee->education_node_id ?? null] ?? null;
                $rootName = $rootId ? ($rootNameById[$rootId] ?? '—') : '—';
                $isBasic  = str_contains(strtolower((string) $rootName), 'basic');

                $gradeYear = $fee->program
                    ? trim(($fee->program->code ? $fee->program->code.' ' : '').$fee->program->name)
                    : ($fee->year_level ? ($isBasic ? 'Grade ' : 'Year ').(int) $fee->year_level : '—');

                return (object) [
                    'id'             => $fee->id,
                    'education_level'=> $this->levelCell($rootName),
                    'grade_year'     => $gradeYear,
                    'name'           => $fee->name,
                    'payment_plan'   => $fee->paymentPlan?->name ?: '—',
                    'amount'         => $this->peso($fee->amount),
                    'status'         => $this->statusPill((bool) $fee->is_active),
                ];
            })->values();
    }

    protected function discountRows(int $schoolId)
    {
        return FinanceDiscountType::where('school_id', $schoolId)
            ->orderByDesc('is_active')->orderBy('code')->get()
            ->map(fn (FinanceDiscountType $d) => (object) [
                'id'         => $d->id,
                'code'       => $d->code,
                'name'       => $d->name,
                'kind'       => FinanceDiscountType::DISCOUNT_KINDS[$d->discount_kind] ?? Str::headline($d->discount_kind),
                'value'      => $d->discount_kind === 'percentage' ? number_format((float) $d->value, 2).'%' : $this->peso($d->value),
                'applies_to' => FinanceDiscountType::APPLIES_TO[$d->applies_to] ?? Str::headline($d->applies_to),
                'status'     => $this->statusPill((bool) $d->is_active),
            ])->values();
    }

    protected function scholarshipRows(int $schoolId)
    {
        return Scholarship::where('school_id', $schoolId)
            ->orderByDesc('is_active')->orderBy('code')->get()
            ->map(fn (Scholarship $s) => (object) [
                'id'       => $s->id,
                'code'     => $s->code,
                'name'     => $s->name,
                'kind'     => Scholarship::KINDS[$s->kind] ?? Str::headline($s->kind),
                'value'    => $s->kind === 'percentage' ? number_format((float) $s->value, 2).'%' : $this->peso($s->value),
                'coverage' => Scholarship::COVERAGE[$s->coverage] ?? Str::headline($s->coverage),
                'status'   => $this->statusPill((bool) $s->is_active),
            ])->values();
    }

    protected function paymentPlanRows(int $schoolId)
    {
        return PaymentPlan::where('school_id', $schoolId)
            ->orderByDesc('is_active')->orderBy('name')->get()
            ->map(fn (PaymentPlan $p) => (object) [
                'id'          => $p->id,
                'code'        => $p->code,
                'name'        => $p->name,
                'options'     => $this->planOptionsCell($p),
                'frequencies' => $this->planFrequenciesCell($p),
                'status'      => $this->statusPill((bool) $p->is_active),
            ])->values();
    }

    protected function penaltyRows(int $schoolId)
    {
        return PenaltyRule::where('school_id', $schoolId)
            ->orderByDesc('is_active')->orderBy('code')->get()
            ->map(fn (PenaltyRule $r) => (object) [
                'id'         => $r->id,
                'code'       => $r->code,
                'name'       => $r->name,
                'basis'      => PenaltyRule::BASES[$r->basis] ?? Str::headline($r->basis),
                'amount'     => $r->basis === 'percentage' ? number_format((float) $r->amount, 2).'%' : $this->peso($r->amount),
                'grace_days' => $r->grace_days.' day'.($r->grace_days == 1 ? '' : 's'),
                'status'     => $this->statusPill((bool) $r->is_active),
            ])->values();
    }

    /* ===================================================================
     | Columns + actions
     * =================================================================== */

    protected function tuitionColumns(): array
    {
        return [
            ['key' => 'education_level', 'label' => 'Education Level',  'width' => '200px', 'raw' => true],
            ['key' => 'grade_year',     'label' => 'Grade / Year Level', 'width' => '180px'],
            ['key' => 'payment_plan',   'label' => 'Payment Plan',     'width' => '180px'],
            ['key' => 'amount',         'label' => 'Tuition Amount',   'width' => '150px'],
            ['key' => 'status',         'label' => 'Status',           'width' => '110px', 'raw' => true],
        ];
    }

    protected function feeColumns(): array
    {
        return [
            ['key' => 'education_level', 'label' => 'Education Level', 'width' => '200px', 'raw' => true],
            ['key' => 'grade_year',     'label' => 'Grade / Year Level', 'width' => '160px'],
            ['key' => 'name',           'label' => 'Fee Name',        'width' => '220px'],
            ['key' => 'amount',         'label' => 'Amount',          'width' => '150px'],
            ['key' => 'status',         'label' => 'Status',          'width' => '110px', 'raw' => true],
        ];
    }

    protected function discountColumns(): array
    {
        return [
            ['key' => 'code',       'label' => 'Code',       'width' => '120px'],
            ['key' => 'name',       'label' => 'Discount Name', 'width' => '230px'],
            ['key' => 'kind',       'label' => 'Kind',       'width' => '130px'],
            ['key' => 'value',      'label' => 'Value',      'width' => '120px'],
            ['key' => 'applies_to', 'label' => 'Applies To', 'width' => '150px'],
            ['key' => 'status',     'label' => 'Status',     'width' => '110px', 'raw' => true],
        ];
    }

    protected function scholarshipColumns(): array
    {
        return [
            ['key' => 'code',     'label' => 'Code',     'width' => '120px'],
            ['key' => 'name',     'label' => 'Scholarship Name', 'width' => '230px'],
            ['key' => 'kind',     'label' => 'Kind',     'width' => '130px'],
            ['key' => 'value',    'label' => 'Value',    'width' => '120px'],
            ['key' => 'coverage', 'label' => 'Coverage', 'width' => '160px'],
            ['key' => 'status',   'label' => 'Status',   'width' => '110px', 'raw' => true],
        ];
    }

    protected function paymentPlanColumns(): array
    {
        return [
            ['key' => 'code',        'label' => 'Code',        'width' => '110px'],
            ['key' => 'name',        'label' => 'Plan Name',   'width' => '190px'],
            ['key' => 'options',     'label' => 'Options',     'width' => '320px', 'raw' => true],
            ['key' => 'frequencies', 'label' => 'Frequencies', 'width' => '200px', 'raw' => true],
            ['key' => 'status',      'label' => 'Status',      'width' => '110px', 'raw' => true],
        ];
    }

    protected function penaltyColumns(): array
    {
        return [
            ['key' => 'code',       'label' => 'Code',       'width' => '120px'],
            ['key' => 'name',       'label' => 'Rule Name',  'width' => '220px'],
            ['key' => 'basis',      'label' => 'Basis',      'width' => '170px'],
            ['key' => 'amount',     'label' => 'Amount',     'width' => '130px'],
            ['key' => 'grace_days', 'label' => 'Grace',      'width' => '120px'],
            ['key' => 'status',     'label' => 'Status',     'width' => '110px', 'raw' => true],
        ];
    }

    protected function actionsFor(string $tab): array
    {
        $modal = match ($tab) {
            'tuition', 'miscellaneous', 'other' => 'feeModal',
            'discounts'     => 'discountModal',
            'scholarships'  => 'scholarshipModal',
            'payment-plans' => 'paymentPlanModal',
            'penalty-rules' => 'penaltyModal',
        };

        return [
            ['type' => 'modal', 'name' => 'edit', 'label' => 'Edit', 'modal' => $modal, 'handler' => "openRowModal('{$modal}', {id})", 'icon' => 'pencil', 'class' => 'text-indigo-600 hover:bg-indigo-50'],
            ['type' => 'delete', 'name' => 'delete', 'label' => 'Delete', 'icon' => 'trash-2', 'class' => 'text-rose-600 hover:bg-rose-50'],
        ];
    }

    /** Edit payload (id-keyed) for the active tab so the modal can prefill. */
    protected function editPayload(string $tab, int $schoolId)
    {
        return match ($tab) {
            'tuition', 'miscellaneous', 'other' => FinanceFeeSetup::where('school_id', $schoolId)
                ->get(['id', 'academic_year_id', 'term_id', 'education_node_id', 'program_id', 'payment_plan_id', 'year_level', 'fee_type', 'code', 'name', 'billing_basis', 'amount', 'is_active', 'notes'])
                ->keyBy('id'),
            'discounts' => FinanceDiscountType::where('school_id', $schoolId)
                ->get(['id', 'code', 'name', 'discount_kind', 'value', 'applies_to', 'requires_approval', 'is_active', 'notes'])->keyBy('id'),
            'scholarships' => Scholarship::where('school_id', $schoolId)
                ->get(['id', 'code', 'name', 'kind', 'value', 'coverage', 'requires_approval', 'is_active', 'notes'])->keyBy('id'),
            'payment-plans' => PaymentPlan::where('school_id', $schoolId)
                ->get(['id', 'code', 'name', 'frequencies', 'class_start_date', 'class_end_date', 'billing_end_date', 'billing_day', 'cash_enabled', 'cash_discount_type', 'cash_discount_value', 'dp_enabled', 'down_payment_type', 'down_payment_value', 'interest_enabled', 'interest_rate', 'is_active', 'notes'])->keyBy('id'),
            'penalty-rules' => PenaltyRule::where('school_id', $schoolId)
                ->get(['id', 'code', 'name', 'basis', 'amount', 'grace_days', 'is_active', 'notes'])->keyBy('id'),
        };
    }

    /* ===================================================================
     | Fee CRUD
     * =================================================================== */

    public function storeFee(Request $request)
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $this->validatedFeeData($request, $schoolId);
        $data['school_id'] = $schoolId;
        FinanceFeeSetup::create($data);

        return back()->with('success', 'Fee saved.');
    }

    public function updateFee(Request $request, FinanceFeeSetup $fee)
    {
        $this->authorizeSchool((int) $fee->school_id);
        $fee->update($this->validatedFeeData($request, (int) $fee->school_id, $fee->id));

        return back()->with('success', 'Fee updated.');
    }

    public function destroyFee(FinanceFeeSetup $fee)
    {
        $this->authorizeSchool((int) $fee->school_id);
        $fee->delete();

        return back()->with('success', 'Fee deleted.');
    }

    protected function validatedFeeData(Request $request, int $schoolId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'academic_year_id'  => ['nullable', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
            'term_id'           => ['nullable', 'integer', Rule::exists('terms', 'id')->where('school_id', $schoolId)],
            'education_node_id' => ['nullable', 'integer', Rule::exists('education_nodes', 'id')],
            'program_id'        => ['nullable', 'integer', Rule::exists('programs', 'id')->where('school_id', $schoolId)],
            'payment_plan_id'   => ['nullable', 'integer', Rule::exists('payment_plans', 'id')->where('school_id', $schoolId)],
            'year_level'        => ['nullable', 'integer', 'min:1', 'max:20'],
            'fee_type'          => ['required', Rule::in(array_keys(FinanceFeeSetup::FEE_TYPES))],
            'code'              => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('finance_fee_setups', 'code')->where('school_id', $schoolId)->ignore($ignoreId)],
            'name'              => ['required', 'string', 'max:191'],
            'billing_basis'     => ['required', Rule::in(array_keys(FinanceFeeSetup::BILLING_BASES))],
            'amount'            => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'is_active'         => ['nullable', 'boolean'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ]);

        $data['code']      = Str::upper(trim($data['code']));
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['amount']    = round((float) $data['amount'], 2);

        return $data;
    }

    /* ===================================================================
     | Discount CRUD
     * =================================================================== */

    public function storeDiscount(Request $request)
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $this->validatedDiscountData($request, $schoolId);
        $data['school_id'] = $schoolId;
        FinanceDiscountType::create($data);

        return back()->with('success', 'Discount saved.');
    }

    public function updateDiscount(Request $request, FinanceDiscountType $discount)
    {
        $this->authorizeSchool((int) $discount->school_id);
        $discount->update($this->validatedDiscountData($request, (int) $discount->school_id, $discount->id));

        return back()->with('success', 'Discount updated.');
    }

    public function destroyDiscount(FinanceDiscountType $discount)
    {
        $this->authorizeSchool((int) $discount->school_id);
        $discount->delete();

        return back()->with('success', 'Discount deleted.');
    }

    protected function validatedDiscountData(Request $request, int $schoolId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code'              => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('finance_discount_types', 'code')->where('school_id', $schoolId)->ignore($ignoreId)],
            'name'              => ['required', 'string', 'max:191'],
            'discount_kind'     => ['required', Rule::in(array_keys(FinanceDiscountType::DISCOUNT_KINDS))],
            'value'             => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'applies_to'        => ['required', Rule::in(array_keys(FinanceDiscountType::APPLIES_TO))],
            'requires_approval' => ['nullable', 'boolean'],
            'is_active'         => ['nullable', 'boolean'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ]);

        $data['code']              = Str::upper(trim($data['code']));
        $data['requires_approval'] = (bool) ($data['requires_approval'] ?? false);
        $data['is_active']         = (bool) ($data['is_active'] ?? false);
        $data['value']             = round((float) $data['value'], 2);

        return $data;
    }

    /* ===================================================================
     | Scholarship CRUD
     * =================================================================== */

    public function storeScholarship(Request $request)
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $this->validatedScholarshipData($request, $schoolId);
        $data['school_id'] = $schoolId;
        Scholarship::create($data);

        return back()->with('success', 'Scholarship saved.');
    }

    public function updateScholarship(Request $request, Scholarship $scholarship)
    {
        $this->authorizeSchool((int) $scholarship->school_id);
        $scholarship->update($this->validatedScholarshipData($request, (int) $scholarship->school_id, $scholarship->id));

        return back()->with('success', 'Scholarship updated.');
    }

    public function destroyScholarship(Scholarship $scholarship)
    {
        $this->authorizeSchool((int) $scholarship->school_id);
        $scholarship->delete();

        return back()->with('success', 'Scholarship deleted.');
    }

    protected function validatedScholarshipData(Request $request, int $schoolId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code'              => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('scholarships', 'code')->where('school_id', $schoolId)->ignore($ignoreId)],
            'name'              => ['required', 'string', 'max:191'],
            'kind'              => ['required', Rule::in(array_keys(Scholarship::KINDS))],
            'value'             => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'coverage'          => ['required', Rule::in(array_keys(Scholarship::COVERAGE))],
            'requires_approval' => ['nullable', 'boolean'],
            'is_active'         => ['nullable', 'boolean'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ]);

        $data['code']              = Str::upper(trim($data['code']));
        $data['requires_approval'] = (bool) ($data['requires_approval'] ?? false);
        $data['is_active']         = (bool) ($data['is_active'] ?? false);
        $data['value']             = round((float) $data['value'], 2);

        return $data;
    }

    /* ===================================================================
     | Payment Plan CRUD
     * =================================================================== */

    public function storePaymentPlan(Request $request)
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $this->validatedPaymentPlanData($request, $schoolId);
        $data['school_id'] = $schoolId;
        PaymentPlan::create($data);

        return back()->with('success', 'Payment plan saved.');
    }

    public function updatePaymentPlan(Request $request, PaymentPlan $paymentPlan)
    {
        $this->authorizeSchool((int) $paymentPlan->school_id);
        $paymentPlan->update($this->validatedPaymentPlanData($request, (int) $paymentPlan->school_id, $paymentPlan->id));

        return back()->with('success', 'Payment plan updated.');
    }

    public function destroyPaymentPlan(PaymentPlan $paymentPlan)
    {
        $this->authorizeSchool((int) $paymentPlan->school_id);
        $paymentPlan->delete();

        return back()->with('success', 'Payment plan deleted.');
    }

    protected function validatedPaymentPlanData(Request $request, int $schoolId, ?int $ignoreId = null): array
    {
        $freqKeys   = array_keys(PaymentPlan::FREQUENCIES);
        $cashOn     = $request->boolean('cash_enabled');
        $dpOn       = $request->boolean('dp_enabled');
        $interestOn = $request->boolean('interest_enabled');
        $cashType   = $request->input('cash_discount_type') === 'fixed' ? 'fixed' : 'percentage';
        $dpType     = $request->input('down_payment_type') === 'fixed' ? 'fixed' : 'percentage';

        $data = $request->validate([
            'code'                => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('payment_plans', 'code')->where('school_id', $schoolId)->ignore($ignoreId)],
            'name'                => ['required', 'string', 'max:191'],
            'frequencies'         => ['array'],
            'frequencies.*'       => [Rule::in($freqKeys)],

            // Class & billing schedule — drives the installment count + due dates.
            'class_start_date'    => ['nullable', 'date'],
            'class_end_date'      => ['nullable', 'date', 'after_or_equal:class_start_date'],
            'billing_end_date'    => ['nullable', 'date', 'after_or_equal:class_start_date'],
            'billing_day'         => ['nullable', 'integer', 'min:1', 'max:31'],

            // Option 1 — Cash + optional discount (% capped at 100, fixed is not).
            'cash_enabled'        => ['nullable', 'boolean'],
            'cash_discount_type'  => ['nullable', Rule::in(['percentage', 'fixed'])],
            'cash_discount_value' => ['nullable', 'numeric', 'min:0', Rule::when($cashType === 'percentage', ['max:100'], ['max:999999999.99'])],

            // Option 2 — Down payment + installment.
            'dp_enabled'          => ['nullable', 'boolean'],
            'down_payment_type'   => ['nullable', Rule::in(['percentage', 'fixed'])],
            'down_payment_value'  => ['nullable', 'numeric', 'min:0', Rule::when($dpType === 'percentage', ['max:100'], ['max:999999999.99'])],

            // Option 3 — Installment + interest.
            'interest_enabled'    => ['nullable', 'boolean'],
            'interest_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],

            'is_active'           => ['nullable', 'boolean'],
            'notes'               => ['nullable', 'string', 'max:2000'],
        ]);

        // At least one option must be offered.
        if (! $cashOn && ! $dpOn && ! $interestOn) {
            throw ValidationException::withMessages(['cash_enabled' => 'Enable at least one payment option.']);
        }
        // Installment-based options need a frequency to divide the balance over.
        if (($dpOn || $interestOn) && empty($data['frequencies'])) {
            throw ValidationException::withMessages(['frequencies' => 'Select at least one billing frequency for installment options.']);
        }

        return [
            'code'                => Str::upper(trim($data['code'])),
            'name'                => $data['name'],
            'frequencies'         => array_values(array_intersect($freqKeys, $data['frequencies'] ?? [])),

            'class_start_date'    => $data['class_start_date'] ?? null,
            'class_end_date'      => $data['class_end_date'] ?? null,
            'billing_end_date'    => $data['billing_end_date'] ?? null,
            'billing_day'         => $data['billing_day'] ?? null,

            'cash_enabled'        => $cashOn,
            'cash_discount_type'  => $cashType,
            'cash_discount_value' => $cashOn ? round((float) ($data['cash_discount_value'] ?? 0), 2) : 0,

            'dp_enabled'          => $dpOn,
            'down_payment_type'   => $dpType,
            'down_payment_value'  => $dpOn ? round((float) ($data['down_payment_value'] ?? 0), 2) : 0,

            'interest_enabled'    => $interestOn,
            'interest_rate'       => $interestOn ? round((float) ($data['interest_rate'] ?? 0), 2) : 0,

            'is_active'           => $request->boolean('is_active'),
            'notes'               => $data['notes'] ?? null,
        ];
    }

    /* ===================================================================
     | Penalty Rule CRUD
     * =================================================================== */

    public function storePenalty(Request $request)
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $this->validatedPenaltyData($request, $schoolId);
        $data['school_id'] = $schoolId;
        PenaltyRule::create($data);

        return back()->with('success', 'Penalty rule saved.');
    }

    public function updatePenalty(Request $request, PenaltyRule $penaltyRule)
    {
        $this->authorizeSchool((int) $penaltyRule->school_id);
        $penaltyRule->update($this->validatedPenaltyData($request, (int) $penaltyRule->school_id, $penaltyRule->id));

        return back()->with('success', 'Penalty rule updated.');
    }

    public function destroyPenalty(PenaltyRule $penaltyRule)
    {
        $this->authorizeSchool((int) $penaltyRule->school_id);
        $penaltyRule->delete();

        return back()->with('success', 'Penalty rule deleted.');
    }

    protected function validatedPenaltyData(Request $request, int $schoolId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code'       => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('penalty_rules', 'code')->where('school_id', $schoolId)->ignore($ignoreId)],
            'name'       => ['required', 'string', 'max:191'],
            'basis'      => ['required', Rule::in(array_keys(PenaltyRule::BASES))],
            'amount'     => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_active'  => ['nullable', 'boolean'],
            'notes'      => ['nullable', 'string', 'max:2000'],
        ]);

        $data['code']      = Str::upper(trim($data['code']));
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['amount']    = round((float) $data['amount'], 2);

        return $data;
    }

    /* ===================================================================
     | Helpers
     * =================================================================== */

    protected function peso($v): string
    {
        return '₱'.number_format((float) $v, 2);
    }

    /** A "% or ₱" amount label, e.g. "20%" or "₱5,000.00". */
    protected function valueLabel(string $type, float $value): string
    {
        return $type === 'fixed'
            ? $this->peso($value)
            : rtrim(rtrim(number_format($value, 2), '0'), '.').'%';
    }

    /** Small pill used in the plan Options / Frequencies cells. */
    protected function planBadge(string $text, string $bg, string $fg): string
    {
        return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold" '
            .'style="background:'.$bg.'; color:'.$fg.';">'.e($text).'</span>';
    }

    /** Enabled payment options as coloured badges. */
    protected function planOptionsCell(PaymentPlan $p): string
    {
        $badges = [];

        if ($p->cash_enabled) {
            $text = 'Cash';
            if ((float) $p->cash_discount_value > 0) {
                $text .= ' −'.$this->valueLabel((string) $p->cash_discount_type, (float) $p->cash_discount_value);
            }
            $badges[] = $this->planBadge($text, '#dcfce7', '#15803d');
        }
        if ($p->dp_enabled) {
            $dp = (float) $p->down_payment_value > 0
                ? $this->valueLabel((string) $p->down_payment_type, (float) $p->down_payment_value).' down'
                : 'Down payment';
            $badges[] = $this->planBadge($dp.' + installment', '#e0e7ff', '#4338ca');
        }
        if ($p->interest_enabled) {
            $text = 'Installment';
            if ((float) $p->interest_rate > 0) {
                $text .= ' + '.rtrim(rtrim(number_format((float) $p->interest_rate, 2), '0'), '.').'% interest';
            }
            $badges[] = $this->planBadge($text, '#fef3c7', '#b45309');
        }

        if (empty($badges)) {
            return '<span class="text-xs text-slate-400">No options set</span>';
        }

        return '<div class="flex flex-wrap gap-1">'.implode('', $badges).'</div>';
    }

    /** Allowed billing frequencies as badges. */
    protected function planFrequenciesCell(PaymentPlan $p): string
    {
        $freqs = is_array($p->frequencies) ? $p->frequencies : [];
        $badges = [];
        foreach ($freqs as $key) {
            if (isset(PaymentPlan::FREQUENCIES[$key])) {
                $badges[] = $this->planBadge(PaymentPlan::FREQUENCIES[$key], '#f1f5f9', '#475569');
            }
        }

        if (empty($badges)) {
            return '<span class="text-xs text-slate-400">—</span>';
        }

        return '<div class="flex flex-wrap gap-1">'.implode('', $badges).'</div>';
    }

    protected function statusPill(bool $active): string
    {
        return $active
            ? '<span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">Active</span>'
            : '<span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600">Inactive</span>';
    }

    /** Education-level cell: a coloured icon chip + the level name (raw HTML). */
    protected function levelCell(string $rootName): string
    {
        $n = strtolower($rootName);
        [$icon, $bg, $fg] = match (true) {
            str_contains($n, 'basic')                                => ['book-open', '#dcfce7', '#16a34a'],
            str_contains($n, 'senior') || str_contains($n, 'shs')    => ['graduation-cap', '#ede9fe', '#7c3aed'],
            str_contains($n, 'college') || str_contains($n, 'under') => ['building-2', '#ffedd5', '#ea580c'],
            str_contains($n, 'graduate') || str_contains($n, 'post') => ['award', '#dbeafe', '#2563eb'],
            default                                                  => ['layers', '#f1f5f9', '#475569'],
        };

        return '<span class="inline-flex items-center gap-2">'
            .'<span class="inline-flex h-7 w-7 items-center justify-center rounded-lg" style="background-color:'.$bg.';color:'.$fg.';">'
            .'<i data-lucide="'.$icon.'" class="h-4 w-4"></i></span>'
            .'<span class="font-semibold text-slate-700">'.e($rootName).'</span></span>';
    }

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

    protected function educationNodeOptions()
    {
        $nodes = DB::table('education_nodes')->where('is_active', 1)
            ->orderBy('order_index')->orderBy('name')->get(['id', 'name', 'parent_id'])->keyBy('id');

        return $nodes->map(function ($node) use ($nodes) {
            $segments = [$node->name];
            $parentId = $node->parent_id;
            for ($i = 0; $i < 20 && $parentId && isset($nodes[$parentId]); $i++) {
                array_unshift($segments, $nodes[$parentId]->name);
                $parentId = $nodes[$parentId]->parent_id;
            }

            return (object) ['id' => $node->id, 'label' => implode(' / ', $segments)];
        })->sortBy('label')->values();
    }

    protected function authorizeSchool(int $schoolId): void
    {
        abort_unless((int) auth()->user()->school_id === $schoolId, 403);
    }
}
