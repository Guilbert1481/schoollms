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
    public const RULE_AVERAGE = 'average';

    public const RULE_ALL_AREAS_PASS = 'all_areas_pass';

    /** Default collective noun for a basic-ed grading period. */
    public const DEFAULT_PERIOD_LABEL = 'Quarter';

    /** Default per-period names. The grade pipeline supports at most 4. */
    public const DEFAULT_PERIOD_NAMES = ['1st Quarter', '2nd Quarter', '3rd Quarter', '4th Quarter'];

    /** Hard cap on grading periods (report_card_grades / Form 137 assume ≤ 4). */
    public const MAX_PERIODS = 4;

    protected $fillable = [
        'school_id',
        'passing_threshold',
        'promotion_rule',
        'show_student_grades',
        'show_student_form137',
        'period_label',
        'period_names',
    ];

    protected $casts = [
        'passing_threshold' => 'decimal:2',
        'show_student_grades' => 'boolean',
        'show_student_form137' => 'boolean',
        'period_names' => 'array',
    ];

    /** Get (or create with sensible defaults) the settings row for a school. */
    public static function forSchool(int $schoolId): self
    {
        return static::firstOrCreate(
            ['school_id' => $schoolId],
            [
                'passing_threshold' => 75.00,
                'promotion_rule' => self::RULE_AVERAGE,
                // Student grade views are hidden until the Principal enables them.
                'show_student_grades' => false,
                'show_student_form137' => false,
                'period_label' => self::DEFAULT_PERIOD_LABEL,
                'period_names' => self::DEFAULT_PERIOD_NAMES,
            ]
        );
    }

    /** The ordinal → default name map: [1 => '1st Quarter', …]. */
    public static function defaultPeriods(): array
    {
        return self::indexNames(self::DEFAULT_PERIOD_NAMES);
    }

    /**
     * The configured ordinal → name map for this school, honouring saved names
     * and falling back to the defaults when unset. Capped at MAX_PERIODS.
     *
     * @return array<int, string> e.g. [1 => '1st Quarter', 2 => '2nd Quarter', …]
     */
    public function periods(): array
    {
        $names = $this->period_names;

        if (! is_array($names) || $names === []) {
            return self::defaultPeriods();
        }

        // An all-blank array survives the guard above but reduces to nothing —
        // never hand a consumer an empty period list.
        $mapped = self::indexNames($names);

        return $mapped !== [] ? $mapped : self::defaultPeriods();
    }

    /**
     * How many grading periods a school uses — its configured count, or the
     * default when it has not customised them. Used to bound the period a
     * teacher may post to, so grades can't land on an ordinal the school does
     * not recognise.
     */
    public static function periodCountForSchool(int $schoolId): int
    {
        $setting = static::where('school_id', $schoolId)->first();

        return $setting ? max(1, count($setting->periods())) : count(self::DEFAULT_PERIOD_NAMES);
    }

    /** The collective noun for a grading period (never blank). */
    public function periodLabel(): string
    {
        return trim((string) $this->period_label) ?: self::DEFAULT_PERIOD_LABEL;
    }

    /**
     * Reindex a list of names to a 1-based ordinal map, dropping blanks and
     * capping at MAX_PERIODS so callers never see more periods than the grade
     * pipeline supports.
     *
     * @param  array<int|string, mixed>  $names
     * @return array<int, string>
     */
    private static function indexNames(array $names): array
    {
        return collect(array_values($names))
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->take(self::MAX_PERIODS)
            ->values()
            ->mapWithKeys(fn ($name, $i) => [$i + 1 => $name])
            ->all();
    }
}
