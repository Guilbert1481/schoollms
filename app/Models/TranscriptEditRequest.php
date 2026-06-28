<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pending registrar edit on a student's transcript subject row.
 *
 * Requires program-head + dean approval before a registrar may apply the
 * change to the underlying StudentEnrollmentSubject record.
 */
class TranscriptEditRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING                = 'pending';
    public const STATUS_PROGRAM_HEAD_APPROVED  = 'program_head_approved';
    public const STATUS_DEAN_APPROVED          = 'dean_approved';
    public const STATUS_APPLIED                = 'applied';
    public const STATUS_REJECTED               = 'rejected';

    protected $fillable = [
        'student_id',
        'subject_id',
        'enrollment_subject_id',
        'old_grade',
        'new_grade',
        'reason',
        'status',
        'requested_by',
        'program_head_id',
        'program_head_approved_at',
        'program_head_note',
        'dean_id',
        'dean_approved_at',
        'dean_note',
        'applied_at',
        'applied_by',
    ];

    protected $casts = [
        'old_grade'                => 'decimal:2',
        'new_grade'                => 'decimal:2',
        'program_head_approved_at' => 'datetime',
        'dean_approved_at'         => 'datetime',
        'applied_at'               => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function enrollmentSubject(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollmentSubject::class, 'enrollment_subject_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
