<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionExamSetting extends Model
{
    use HasFactory;

    public const PURPOSE_DIAGNOSTIC   = 'diagnostic_only';
    public const PURPOSE_REQUIREMENT  = 'admission_requirement';
    public const PURPOSE_SCHOLARSHIP  = 'scholarship_grants';

    protected $fillable = [
        'school_id',
        'require_for_new_student',
        'require_for_transferee',
        'require_for_returnee',
        'require_for_shiftee',
        'exam_purpose',
        'max_score',
        'passing_score',
        'max_attempts',
        'retake_cooldown_days',
        'result_validity_months',
        'allow_program_head_waiver',
        'notify_applicant_on_schedule',
        'auto_assess_after_pass',
        'instructions',
        'grants_scholarship',
        'scholarship_bands',
    ];

    protected $casts = [
        'require_for_new_student'      => 'boolean',
        'require_for_transferee'       => 'boolean',
        'require_for_returnee'         => 'boolean',
        'require_for_shiftee'          => 'boolean',
        'allow_program_head_waiver'    => 'boolean',
        'notify_applicant_on_schedule' => 'boolean',
        'auto_assess_after_pass'       => 'boolean',
        'max_score'                    => 'integer',
        'passing_score'                => 'integer',
        'max_attempts'                 => 'integer',
        'retake_cooldown_days'         => 'integer',
        'result_validity_months'       => 'integer',
        'grants_scholarship'           => 'boolean',
        'scholarship_bands'            => 'array',
    ];

    public function isAdmissionRequirement(): bool
    {
        return $this->exam_purpose === self::PURPOSE_REQUIREMENT;
    }

    public function grantsScholarship(): bool
    {
        // Scholarship is an independent toggle, combinable with either the
        // diagnostic or the admission-requirement purpose.
        return (bool) $this->grants_scholarship;
    }

    /**
     * Resolve the best scholarship band for a raw exam score: the highest
     * minimum-score threshold the score reaches. Returns null when the exam
     * isn't a scholarship exam, no bands are configured, or the score qualifies
     * for none. Each band = ['label','percent','apply_to','min_score'].
     */
    public function scholarshipForScore(?int $score): ?array
    {
        if (! $this->grantsScholarship() || $score === null) {
            return null;
        }

        return collect($this->scholarship_bands ?? [])
            ->filter(fn ($b) => isset($b['min_score'], $b['percent']) && $score >= (int) $b['min_score'])
            ->sortByDesc('min_score')
            ->first();
    }
}
