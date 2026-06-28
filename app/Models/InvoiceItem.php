<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'school_id',
        'finance_fee_setup_id',
        'finance_discount_type_id',
        'fee_type',
        'description',
        'billing_basis',
        'quantity',
        'unit_amount',
        'amount',
        'discount_amount',
        'net_amount',
    ];

    protected $casts = [
        'quantity'        => 'decimal:2',
        'unit_amount'     => 'decimal:2',
        'amount'          => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount'      => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function feeSetup()
    {
        return $this->belongsTo(FinanceFeeSetup::class, 'finance_fee_setup_id');
    }

    public function discountType()
    {
        return $this->belongsTo(FinanceDiscountType::class, 'finance_discount_type_id');
    }
}
