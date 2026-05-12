<?php

namespace App\Models\Scheduler;

use Illuminate\Database\Eloquent\Model;

class SchedulerSetting extends Model
{
    protected $table = 'scheduler_settings';

    protected $fillable = [
        'school_id',
        'min_session_hours', 'max_session_hours',
        'max_subjects_per_day',
        'max_hours_per_day', 'max_hours_per_week',
        'max_allowed_gap', 'allow_gaps',
        'min_days_per_week', 'max_days_per_week',
        'teacher_max_hours_per_week', 'teacher_max_hours_per_day',
        'teacher_work_days_per_week', 'teacher_min_hours_per_day', 'prioritize_full_time',
        'part_time_min_hours_per_day',
    ];

    protected $casts = [
        'allow_gaps'           => 'boolean',
        'prioritize_full_time' => 'boolean',
        'min_session_hours'    => 'decimal:2',
        'max_session_hours'    => 'decimal:2',
        'max_hours_per_day'    => 'decimal:2',
        'max_hours_per_week'   => 'decimal:2',
        'teacher_min_hours_per_day' => 'decimal:2',
        'part_time_min_hours_per_day' => 'decimal:2',
    ];

    /** Resolve effective settings for a school, falling back to defaults. */
    public static function forSchool(?int $schoolId): array
    {
        $row = $schoolId ? static::where('school_id', $schoolId)->first() : null;

        return [
            'min_session_hours'    => (float) ($row->min_session_hours    ?? 1),
            'max_session_hours'    => (float) ($row->max_session_hours    ?? 2),
            'max_subjects_per_day' => (int)   ($row->max_subjects_per_day ?? 5),
            'max_hours_per_week'   => (float) ($row->max_hours_per_week   ?? 40),
            'max_allowed_gap'      => (int)   ($row->max_allowed_gap      ?? 30),
            'allow_gaps'           => (bool)  ($row->allow_gaps           ?? true),
            'min_days_per_week'    => (int)   ($row->min_days_per_week    ?? 1),
            'max_days_per_week'    => (int)   ($row->max_days_per_week    ?? 3),
        ];
    }

    /** Resolve teacher constraints for a school, falling back to defaults. */
    public static function teacherConstraintsForSchool(?int $schoolId): array
    {
        $row = $schoolId ? static::where('school_id', $schoolId)->first() : null;

        return [
            'max_hours_per_week'   => (int)  ($row->teacher_max_hours_per_week ?? 24),
            'max_hours_per_day'    => (int)  ($row->teacher_max_hours_per_day  ?? 5),
            'work_days_per_week'   => (int)  ($row->teacher_work_days_per_week ?? 5),
            'min_hours_per_day'    => (float) ($row->teacher_min_hours_per_day ?? 1),
            'prioritize_full_time' => (bool) ($row->prioritize_full_time       ?? true),
            'part_time_min_hours_per_day' => (float) ($row->part_time_min_hours_per_day ?? 1),
        ];
    }
}
