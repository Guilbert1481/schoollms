<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceDiscountType;
use App\Models\FinanceFeeSetup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TuitionSetupController extends Controller
{
    public function index()
    {
        $schoolId = (int) auth()->user()->school_id;

        $fees = FinanceFeeSetup::query()
            ->with(['academicYear:id,name', 'term:id,name', 'educationNode:id,name,parent_id', 'program:id,code,name'])
            ->where('school_id', $schoolId)
            ->orderByDesc('is_active')
            ->orderBy('fee_type')
            ->orderBy('code')
            ->get()
            ->map(fn (FinanceFeeSetup $fee) => $this->formatFeeRow($fee));

        $discounts = FinanceDiscountType::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('is_active')
            ->orderBy('code')
            ->get()
            ->map(fn (FinanceDiscountType $discount) => $this->formatDiscountRow($discount));

        return view('finance.tuition_setup', [
            'fees' => $fees,
            'discounts' => $discounts,
            'feeColumns' => $this->feeColumns(),
            'discountColumns' => $this->discountColumns(),
            'feeActions' => $this->feeActions(),
            'discountActions' => $this->discountActions(),
            'academicYears' => DB::table('academic_years')
                ->where('school_id', $schoolId)
                ->orderByDesc('start_date')
                ->get(['id', 'name']),
            'terms' => DB::table('terms')
                ->where('school_id', $schoolId)
                ->orderByDesc('is_current')
                ->orderByDesc('start_date')
                ->get(['id', 'name', 'academic_year_id']),
            'educationNodes' => $this->educationNodeOptions(),
            'programs' => DB::table('programs')
                ->where('school_id', $schoolId)
                ->orderBy('code')
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'feeTypes' => FinanceFeeSetup::FEE_TYPES,
            'billingBases' => FinanceFeeSetup::BILLING_BASES,
            'discountKinds' => FinanceDiscountType::DISCOUNT_KINDS,
            'discountAppliesTo' => FinanceDiscountType::APPLIES_TO,
        ]);
    }

    public function storeFee(Request $request)
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $this->validatedFeeData($request, $schoolId);
        $data['school_id'] = $schoolId;

        FinanceFeeSetup::create($data);

        return back()->with('success', 'Fee setup created.');
    }

    public function updateFee(Request $request, FinanceFeeSetup $fee)
    {
        $this->authorizeSchool($fee->school_id);

        $fee->update($this->validatedFeeData($request, (int) $fee->school_id, $fee->id));

        return back()->with('success', 'Fee setup updated.');
    }

    public function destroyFee(FinanceFeeSetup $fee)
    {
        $this->authorizeSchool($fee->school_id);
        $fee->delete();

        return back()->with('success', 'Fee setup deleted.');
    }

    public function storeDiscount(Request $request)
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $this->validatedDiscountData($request, $schoolId);
        $data['school_id'] = $schoolId;

        FinanceDiscountType::create($data);

        return back()->with('success', 'Discount type created.');
    }

    public function updateDiscount(Request $request, FinanceDiscountType $discount)
    {
        $this->authorizeSchool($discount->school_id);

        $discount->update($this->validatedDiscountData($request, (int) $discount->school_id, $discount->id));

        return back()->with('success', 'Discount type updated.');
    }

    public function destroyDiscount(FinanceDiscountType $discount)
    {
        $this->authorizeSchool($discount->school_id);
        $discount->delete();

        return back()->with('success', 'Discount type deleted.');
    }

    protected function validatedFeeData(Request $request, int $schoolId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
            'term_id' => ['nullable', 'integer', Rule::exists('terms', 'id')->where('school_id', $schoolId)],
            'education_node_id' => ['nullable', 'integer', Rule::exists('education_nodes', 'id')],
            'program_id' => ['nullable', 'integer', Rule::exists('programs', 'id')->where('school_id', $schoolId)],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:20'],
            'fee_type' => ['required', Rule::in(array_keys(FinanceFeeSetup::FEE_TYPES))],
            'code' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('finance_fee_setups', 'code')
                    ->where('school_id', $schoolId)
                    ->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:191'],
            'billing_basis' => ['required', Rule::in(array_keys(FinanceFeeSetup::BILLING_BASES))],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['code'] = Str::upper(trim($data['code']));
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['amount'] = round((float) $data['amount'], 2);

        return $data;
    }

    protected function validatedDiscountData(Request $request, int $schoolId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('finance_discount_types', 'code')
                    ->where('school_id', $schoolId)
                    ->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:191'],
            'discount_kind' => ['required', Rule::in(array_keys(FinanceDiscountType::DISCOUNT_KINDS))],
            'value' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'applies_to' => ['required', Rule::in(array_keys(FinanceDiscountType::APPLIES_TO))],
            'requires_approval' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['code'] = Str::upper(trim($data['code']));
        $data['requires_approval'] = (bool) ($data['requires_approval'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['value'] = round((float) $data['value'], 2);

        return $data;
    }

    protected function formatFeeRow(FinanceFeeSetup $fee): FinanceFeeSetup
    {
        $scope = collect([
            $fee->academicYear?->name,
            $fee->term?->name,
            $fee->educationNode?->name,
            $fee->program ? trim(($fee->program->code ? $fee->program->code.' - ' : '').$fee->program->name) : null,
            $fee->year_level ? 'Year/Grade '.$fee->year_level : null,
        ])->filter()->implode(' / ');

        $fee->scope_label = e($scope !== '' ? $scope : 'All students');
        $fee->fee_type_label = e(FinanceFeeSetup::FEE_TYPES[$fee->fee_type] ?? Str::headline($fee->fee_type));
        $fee->billing_basis_label = e(FinanceFeeSetup::BILLING_BASES[$fee->billing_basis] ?? Str::headline($fee->billing_basis));
        $fee->amount_label = 'PHP '.number_format((float) $fee->amount, 2);
        $fee->status_label = $this->statusPill($fee->is_active);

        return $fee;
    }

    protected function formatDiscountRow(FinanceDiscountType $discount): FinanceDiscountType
    {
        $discount->discount_kind_label = e(FinanceDiscountType::DISCOUNT_KINDS[$discount->discount_kind] ?? Str::headline($discount->discount_kind));
        $discount->value_label = $discount->discount_kind === 'percentage'
            ? number_format((float) $discount->value, 2).'%'
            : 'PHP '.number_format((float) $discount->value, 2);
        $discount->applies_to_label = e(FinanceDiscountType::APPLIES_TO[$discount->applies_to] ?? Str::headline($discount->applies_to));
        $discount->approval_label = $discount->requires_approval
            ? '<span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">Approval</span>'
            : '<span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">Automatic</span>';
        $discount->status_label = $this->statusPill($discount->is_active);

        return $discount;
    }

    protected function statusPill(bool $active): string
    {
        return $active
            ? '<span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Active</span>'
            : '<span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">Inactive</span>';
    }

    protected function feeColumns(): array
    {
        return [
            ['key' => 'code', 'label' => 'Code', 'width' => '120px'],
            ['key' => 'name', 'label' => 'Fee Name', 'width' => '220px'],
            ['key' => 'fee_type_label', 'label' => 'Type', 'width' => '140px'],
            ['key' => 'scope_label', 'label' => 'Scope', 'width' => '300px'],
            ['key' => 'billing_basis_label', 'label' => 'Basis', 'width' => '140px'],
            ['key' => 'amount_label', 'label' => 'Amount', 'width' => '140px'],
            ['key' => 'status_label', 'label' => 'Status', 'width' => '110px', 'raw' => true],
        ];
    }

    protected function discountColumns(): array
    {
        return [
            ['key' => 'code', 'label' => 'Code', 'width' => '120px'],
            ['key' => 'name', 'label' => 'Discount Name', 'width' => '230px'],
            ['key' => 'discount_kind_label', 'label' => 'Kind', 'width' => '130px'],
            ['key' => 'value_label', 'label' => 'Value', 'width' => '120px'],
            ['key' => 'applies_to_label', 'label' => 'Applies To', 'width' => '150px'],
            ['key' => 'approval_label', 'label' => 'Approval', 'width' => '120px', 'raw' => true],
            ['key' => 'status_label', 'label' => 'Status', 'width' => '110px', 'raw' => true],
        ];
    }

    protected function feeActions(): array
    {
        return [
            ['type' => 'modal', 'label' => 'Edit', 'modal' => 'feeEditModal', 'handler' => 'openFeeEditModal({id})', 'class' => 'bg-indigo-600 text-white hover:bg-indigo-700'],
            ['type' => 'delete', 'label' => 'Delete', 'class' => 'bg-rose-600 text-white hover:bg-rose-700'],
        ];
    }

    protected function discountActions(): array
    {
        return [
            ['type' => 'modal', 'label' => 'Edit', 'modal' => 'discountEditModal', 'handler' => 'openDiscountEditModal({id})', 'class' => 'bg-indigo-600 text-white hover:bg-indigo-700'],
            ['type' => 'delete', 'label' => 'Delete', 'class' => 'bg-rose-600 text-white hover:bg-rose-700'],
        ];
    }

    protected function educationNodeOptions()
    {
        $nodes = DB::table('education_nodes')
            ->where('is_active', 1)
            ->orderBy('order_index')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->keyBy('id');

        return $nodes->map(function ($node) use ($nodes) {
            $segments = [$node->name];
            $parentId = $node->parent_id;
            for ($i = 0; $i < 20 && $parentId && isset($nodes[$parentId]); $i++) {
                array_unshift($segments, $nodes[$parentId]->name);
                $parentId = $nodes[$parentId]->parent_id;
            }

            return (object) [
                'id' => $node->id,
                'label' => implode(' / ', $segments),
            ];
        })->sortBy('label')->values();
    }

    protected function authorizeSchool(int $schoolId): void
    {
        abort_unless((int) auth()->user()->school_id === $schoolId, 403);
    }
}
