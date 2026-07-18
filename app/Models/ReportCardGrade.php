<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * A basic-ed Report Card grade for one learning area in one grading period of
 * the current school year. See the migration for how it relates to Form 137.
 */
class ReportCardGrade extends Model
{
    use Auditable;

    protected $fillable = [
        'school_id',
        'student_id',
        'education_node_id',
        'subject_id',
        'academic_year_id',
        'grading_period',
        'final_grade',
        'recorded_by',
    ];

    protected $casts = [
        'final_grade' => 'decimal:2',
        'grading_period' => 'integer',
    ];
}
