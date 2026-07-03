<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

class PenaltyRule extends Model
{
    use BelongsToSchool;

    /** how the penalty is computed */
    public const BASES = [
        'fixed'      => 'Fixed Amount',
        'percentage' => 'Percentage of Balance',
    ];

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'basis',
        'amount',
        'grace_days',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'grace_days' => 'integer',
        'is_active'  => 'boolean',
    ];
}
