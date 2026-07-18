<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One immutable answer sheet per (test, student): the frozen answer-key snapshot
 * + bubble map that was printed for that student. Grading always runs against
 * this snapshot, never the live test (which may change after printing).
 */
class OmrSheet extends Model
{
    protected $fillable = [
        'school_id', 'test_id', 'student_id', 'section_id',
        'layout_version', 'token', 'answer_key', 'written_key',
        'item_count', 'written_count', 'max_score', 'generated_at',
    ];

    protected $casts = [
        'answer_key' => 'array',
        'written_key' => 'array',
        'generated_at' => 'datetime',
    ];

    /** The test this sheet was printed for — the source of the grade component. */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(OmrResult::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(OmrScanAttempt::class);
    }
}
