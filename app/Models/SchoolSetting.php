<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $table = 'school_settings';

    protected $casts = [
        'academic_levels' => 'array',
        'question_types'    => 'array',
        'difficulty_levels' => 'array',
        'assessment_types'  => 'array',
    ];

    protected $fillable = [
        'school_id',
        'academic_levels',
        'question_types',
        'difficulty_levels',
        'assessment_types',
    ];
}
