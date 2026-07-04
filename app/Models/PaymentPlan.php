<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

class PaymentPlan extends Model
{
    use BelongsToSchool;

    /** Billing frequencies a guardian may pick for installment-based options. */
    public const FREQUENCIES = [
        'monthly'     => 'Monthly',
        'quarterly'   => 'Quarterly',
        'semi_annual' => 'Semi-Annually',
    ];

    /** How a down payment or cash discount is expressed. */
    public const VALUE_TYPES = [
        'percentage' => 'Percentage (%)',
        'fixed'      => 'Fixed amount (₱)',
    ];

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'frequencies',
        'class_start_date',
        'class_end_date',
        'billing_end_date',
        'billing_day',
        'due_days',
        'installments',
        // Option 1 — Cash (full payment) + optional discount.
        'cash_enabled',
        'cash_discount_type',
        'cash_discount_value',
        // Option 2 — Down payment + installment.
        'dp_enabled',
        'down_payment_type',
        'down_payment_value',
        // Option 3 — Installment + interest.
        'interest_enabled',
        'interest_rate',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'frequencies'         => 'array',
        'class_start_date'    => 'date',
        'class_end_date'      => 'date',
        'billing_end_date'    => 'date',
        'billing_day'         => 'integer',
        'due_days'            => 'integer',
        'installments'        => 'integer',
        'cash_enabled'        => 'boolean',
        'cash_discount_value' => 'decimal:2',
        'dp_enabled'          => 'boolean',
        'down_payment_value'  => 'decimal:2',
        'interest_enabled'    => 'boolean',
        'interest_rate'       => 'decimal:2',
        'is_active'           => 'boolean',
    ];

    public function feeSetups()
    {
        return $this->hasMany(FinanceFeeSetup::class);
    }
}
