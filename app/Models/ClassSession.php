<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Concrete scheduled meeting of a class.
 *
 * Generated from class_schedules expanded across the term's date range
 * via `php artisan acad:generate-sessions {term_id}`.
 */
class ClassSession extends Model
{
    use HasFactory;

    protected $table = 'class_sessions';

    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
        'room',
        'meeting_date',
        'start_time',
        'end_time',
        'capacity',
        'status',
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    public const STATUS_SCHEDULED   = 'scheduled';
    public const STATUS_CANCELLED   = 'cancelled';
    public const STATUS_RESCHEDULED = 'rescheduled';

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
