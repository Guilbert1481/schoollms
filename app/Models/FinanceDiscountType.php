<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceDiscountType extends Model
{
    public const DISCOUNT_KINDS = [
        'percentage' => 'Percentage',
        'fixed' => 'Fixed Amount',
    ];

    public const APPLIES_TO = [
        'tuition' => 'Tuition',
        'miscellaneous' => 'Miscellaneous',
        'total' => 'Total Assessment',
    ];

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'discount_kind',
        'value',
        'applies_to',
        'requires_approval',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
