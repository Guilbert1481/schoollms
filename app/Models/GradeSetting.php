<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-school grade settings (Principal → Settings → Grades).
 * Extensible home for grading thresholds/policies; currently the passing
 * threshold + promotion rule consumed by Form 137.
 */
class GradeSetting extends Model
{
    public const RULE_AVERAGE        = 'average';
    public const RULE_ALL_AREAS_PASS = 'all_areas_pass';

    protected $fillable = [
        'school_id',
        'passing_threshold',
        'promotion_rule',
    ];

    protected $casts = [
        'passing_threshold' => 'decimal:2',
    ];

    /** Get (or create with sensible defaults) the settings row for a school. */
    public static function forSchool(int $schoolId): self
    {
        return static::firstOrCreate(
            ['school_id' => $schoolId],
            ['passing_threshold' => 75.00, 'promotion_rule' => self::RULE_AVERAGE]
        );
    }
}
