<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionExamSetting extends Model
{
    use HasFactory;

    public const PURPOSE_DIAGNOSTIC   = 'diagnostic_only';
    public const PURPOSE_REQUIREMENT  = 'admission_requirement';

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
    ];

    public function isAdmissionRequirement(): bool
    {
        return $this->exam_purpose === self::PURPOSE_REQUIREMENT;
    }
}
