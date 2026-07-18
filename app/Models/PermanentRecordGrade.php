<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * A learner's permanent-record (Form 137) grade for one subject at one grade
 * level. Authoritative store the registrar enters; see the migration for how
 * it composes with student_enrollment_subjects.
 */
class PermanentRecordGrade extends Model
{
    use Auditable;

    protected $fillable = [
        'school_id',
        'student_id',
        'education_node_id',
        'subject_id',
        'final_grade',
        'status',
        'teacher_name',
        'transferred_from',
        'school_year',
        'recorded_by',
    ];

    protected $casts = [
        'final_grade' => 'decimal:2',
    ];
}
