<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Clearance extends Model
{
    use BelongsToSchool;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const PURPOSES = ['End of Term', 'Transfer', 'Graduation', 'Other'];

    protected $fillable = [
        'school_id',
        'student_id',
        'student_enrollment_id',
        'purpose',
        'note',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function items()
    {
        return $this->hasMany(ClearanceItem::class);
    }

    public function isOpen(): bool
    {
        return $this->status !== self::STATUS_COMPLETED;
    }

    /** Re-derive the clearance status from its items and persist if changed. */
    public function refreshStatus(): void
    {
        $items = $this->items()->get(['status']);

        $status = match (true) {
            $items->isNotEmpty() && $items->every(fn ($i) => $i->status === ClearanceItem::STATUS_CLEARED) => self::STATUS_COMPLETED,
            $items->contains(fn ($i) => $i->status !== ClearanceItem::STATUS_PENDING) => self::STATUS_IN_PROGRESS,
            default => self::STATUS_PENDING,
        };

        if ($status !== $this->status) {
            $this->status = $status;
            $this->completed_at = $status === self::STATUS_COMPLETED ? now() : null;
            $this->save();
        }
    }
}
