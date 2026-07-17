<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit of every scan / manual submission against a sheet. Results
 * are derived from attempts; an attempt row is never mutated, so the original
 * scan is always recoverable.
 */
class OmrScanAttempt extends Model
{
    protected $fillable = [
        'school_id', 'omr_sheet_id', 'scanned_by', 'source',
        'marked_answers', 'confidence', 'meta', 'outcome',
    ];

    protected $casts = [
        'marked_answers' => 'array',
        'confidence' => 'array',
        'meta' => 'array',
        'outcome' => 'array',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(OmrSheet::class, 'omr_sheet_id');
    }
}
