<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Per-item breakdown for the current result of a sheet. */
class OmrItemResult extends Model
{
    protected $fillable = [
        'omr_result_id', 'item_number', 'question_id',
        'marked', 'correct_label', 'outcome',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(OmrResult::class, 'omr_result_id');
    }
}
