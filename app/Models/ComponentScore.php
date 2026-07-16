<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One student's raw score for one grade component in one context (a higher-ed
 * class, or a basic-ed learning area × grading period). The draft the grading
 * engine computes a final from. Written only through GradebookService. See the
 * migration for the context/key model.
 */
class ComponentScore extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'grade_component_id',
        'class_id',
        'subject_id',
        'education_node_id',
        'grading_period',
        'score',
        'recorded_by',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'grading_period' => 'integer',
    ];

    public function component()
    {
        return $this->belongsTo(GradeComponent::class, 'grade_component_id');
    }
}
