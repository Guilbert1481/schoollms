<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

class Scholarship extends Model
{
    use BelongsToSchool;

    /** value kinds */
    public const KINDS = [
        'percentage' => 'Percentage',
        'fixed'      => 'Fixed Amount',
    ];

    /** what the scholarship covers */
    public const COVERAGE = [
        'tuition' => 'Tuition Only',
        'total'   => 'Total Assessment',
    ];

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'kind',
        'value',
        'coverage',
        'requires_approval',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'value'             => 'decimal:2',
        'requires_approval' => 'boolean',
        'is_active'         => 'boolean',
    ];
}
