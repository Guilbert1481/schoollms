<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The current authoritative graded result for a sheet, always pointing at the
 * scan attempt that produced it. Re-scans/overrides move this pointer forward
 * without destroying prior attempts.
 */
class OmrResult extends Model
{
    protected $fillable = [
        'school_id', 'omr_sheet_id', 'scan_attempt_id',
        'raw_score', 'max_score', 'percentage',
        'correct_count', 'incorrect_count', 'blank_count', 'multiple_count',
        'is_override', 'graded_at',
    ];

    protected $casts = [
        'is_override' => 'boolean',
        'graded_at' => 'datetime',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(OmrSheet::class, 'omr_sheet_id');
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OmrScanAttempt::class, 'scan_attempt_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OmrItemResult::class);
    }
}
