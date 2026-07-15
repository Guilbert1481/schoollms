<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Grading configuration for one education level (see the migration). The
 * Principal edits basic-ed levels, the Dean edits higher-ed levels. The weighted
 * components live in grade_components; attendance_weight is the share of the
 * grade attendance contributes (0 = not counted).
 */
class GradingSetting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'academic_level_id',
        'scale_type',
        'passing_mark',
        'attendance_weight',
    ];

    protected $casts = [
        'passing_mark' => 'decimal:2',
        'attendance_weight' => 'decimal:2',
    ];

    public const SCALE_PERCENTAGE = 'percentage';

    public const SCALE_GPA = 'gpa';

    public const SCALE_LETTER = 'letter';

    public const SCALE_PASS_FAIL = 'pass_fail';

    public const SCALES = [
        self::SCALE_PERCENTAGE,
        self::SCALE_GPA,
        self::SCALE_LETTER,
        self::SCALE_PASS_FAIL,
    ];

    /** Defaults for a level with no saved scheme yet. */
    public static function defaults(): array
    {
        return [
            'scale_type' => self::SCALE_PERCENTAGE,
            'passing_mark' => 75.00,
            'attendance_weight' => 0.00,
        ];
    }

    public function components(): HasMany
    {
        return $this->hasMany(GradeComponent::class)->orderBy('sort_order');
    }

    public function academicLevel()
    {
        return $this->belongsTo(AcademicLevel::class);
    }
}
