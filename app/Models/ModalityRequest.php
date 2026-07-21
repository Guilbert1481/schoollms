<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ModalityRequest extends Model
{
    use BelongsToSchool;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    protected $fillable = [
        'school_id',
        'student_id',
        'student_enrollment_id',
        'from_modality_id',
        'to_modality_id',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'decision_remarks',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function fromModality()
    {
        return $this->belongsTo(Modality::class, 'from_modality_id');
    }

    public function toModality()
    {
        return $this->belongsTo(Modality::class, 'to_modality_id');
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
