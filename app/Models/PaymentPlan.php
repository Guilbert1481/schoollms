<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    protected $fillable = [
        'school_id',
        'code',
        'name',
        'installments',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'installments' => 'integer',
        'is_active'    => 'boolean',
    ];

    public function feeSetups()
    {
        return $this->hasMany(FinanceFeeSetup::class);
    }
}
