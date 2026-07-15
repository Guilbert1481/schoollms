<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * Attendance configuration for one education level (see the migration). The
 * Principal edits basic-ed levels, the Dean edits higher-ed levels. Consumed by
 * the capture flow (status rules, allowed methods) and, later, by the grading
 * engine (attendance-rate denominator).
 */
class AttendanceSetting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'academic_level_id',
        'capture_mode',
        'allow_manual',
        'allow_qr',
        'allow_biometric',
        'allow_facial',
        'late_after',
        'grace_minutes',
        'half_day_enabled',
        'expected_days_per_period',
        'qr_rotation_seconds',
    ];

    protected $casts = [
        'allow_manual' => 'boolean',
        'allow_qr' => 'boolean',
        'allow_biometric' => 'boolean',
        'allow_facial' => 'boolean',
        'grace_minutes' => 'integer',
        'half_day_enabled' => 'boolean',
        'expected_days_per_period' => 'integer',
        'qr_rotation_seconds' => 'integer',
    ];

    public const CAPTURE_DAILY = 'daily';

    public const CAPTURE_SESSION = 'session';

    /**
     * Sensible defaults for a level that has no saved row yet. Basic ed is daily
     * homeroom; everything else is per-subject session.
     *
     * @return array<string, mixed>
     */
    public static function defaultsForType(string $levelType): array
    {
        return [
            'capture_mode' => $levelType === 'basic' ? self::CAPTURE_DAILY : self::CAPTURE_SESSION,
            'allow_manual' => true,
            'allow_qr' => false,
            'allow_biometric' => false,
            'allow_facial' => false,
            'late_after' => null,
            'grace_minutes' => 0,
            'half_day_enabled' => false,
            'expected_days_per_period' => null,
            'qr_rotation_seconds' => 10,
        ];
    }

    public function academicLevel()
    {
        return $this->belongsTo(AcademicLevel::class);
    }
}
