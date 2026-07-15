<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One student's attendance for one context on one date. Written only through
 * App\Services\Attendance\AttendanceIngestionService so every capture method
 * (manual, QR, biometric, facial) lands in the same shape. See the migration
 * for the daily-vs-session scope model.
 */
class AttendanceRecord extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'scope',
        'section_id',
        'class_id',
        'class_session_id',
        'academic_year_id',
        'term_id',
        'attendance_date',
        'time_in',
        'time_out',
        'status',
        'method',
        'remarks',
        'recorded_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public const SCOPE_DAILY = 'daily';

    public const SCOPE_SESSION = 'session';

    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_LATE = 'late';

    public const STATUS_EXCUSED = 'excused';

    public const STATUS_HALF_DAY = 'half_day';

    public const METHOD_MANUAL = 'manual';

    public const METHOD_QR = 'qr';

    public const METHOD_BIOMETRIC = 'biometric';

    public const METHOD_FACIAL = 'facial';

    /** Statuses a teacher may set from the roster UI. */
    public const STATUSES = [
        self::STATUS_PRESENT,
        self::STATUS_ABSENT,
        self::STATUS_LATE,
        self::STATUS_EXCUSED,
        self::STATUS_HALF_DAY,
    ];

    public const METHODS = [
        self::METHOD_MANUAL,
        self::METHOD_QR,
        self::METHOD_BIOMETRIC,
        self::METHOD_FACIAL,
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function classSession()
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
