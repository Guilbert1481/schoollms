<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One student's sitting of an online test. See the create migration for the
 * lock-at-submit and server-authoritative-deadline design.
 */
class TestAttempt extends Model
{
    protected $fillable = [
        'school_id', 'test_id', 'student_id', 'attempt_no',
        'status', 'auto_submitted', 'print_seed',
        'started_at', 'deadline_at', 'submitted_at',
        'raw_score', 'max_score', 'percentage', 'needs_manual',
        'graded_at', 'graded_by', 'meta',
    ];

    protected $casts = [
        'attempt_no' => 'integer',
        'auto_submitted' => 'boolean',
        'print_seed' => 'integer',
        'started_at' => 'datetime',
        'deadline_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'raw_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'needs_manual' => 'boolean',
        'meta' => 'array',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TestAttemptAnswer::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'graded'], true);
    }

    /** Past the server deadline while still in progress → must be auto-submitted. */
    public function isExpired(): bool
    {
        return $this->isOpen() && $this->deadline_at !== null && $this->deadline_at->isPast();
    }
}
