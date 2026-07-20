<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One answer within an attempt. `response` is JSON so a single column holds every
 * question type's shape (choice id, prompt→choice map, string, array, or essay text).
 */
class TestAttemptAnswer extends Model
{
    protected $fillable = [
        'test_attempt_id', 'question_id', 'question_type',
        'response', 'points_possible', 'points_earned',
        'is_correct', 'needs_manual', 'marked_for_review', 'answered_at',
    ];

    protected $casts = [
        'response' => 'array',
        'points_possible' => 'decimal:2',
        'points_earned' => 'decimal:2',
        'is_correct' => 'boolean',
        'needs_manual' => 'boolean',
        'marked_for_review' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /** A non-empty response — what "answered" means for the section-tab colour. */
    public function isAnswered(): bool
    {
        $r = $this->response;
        if ($r === null) {
            return false;
        }
        if (is_array($r)) {
            return count(array_filter($r, fn ($v) => $v !== null && $v !== '')) > 0;
        }

        return trim((string) $r) !== '';
    }
}
