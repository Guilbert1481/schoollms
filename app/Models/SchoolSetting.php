<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    use Auditable; // admin-action log (Phase 4)

    protected $table = 'school_settings';

    protected $casts = [
        'academic_levels' => 'array',
        'question_types' => 'array',
        'difficulty_levels' => 'array',
        'assessment_types' => 'array',
    ];

    protected $fillable = [
        'school_id',
        'academic_levels',
        'question_types',
        'difficulty_levels',
        'assessment_types',
    ];
}
