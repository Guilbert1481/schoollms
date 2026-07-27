<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One hand-entered graded item (a quiz, recitation, seatwork) for a grade
 * component, in a class (higher ed) or a learning area × grading period (basic
 * ed). Its per-student raw scores live in {@see GradeActivityScore}; together
 * they feed component_scores through ComponentScoreRecomputer. See the migration
 * for the context/key model.
 */
class GradeActivity extends Model
{
    use Auditable, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'grade_component_id',
        'title',
        'total_items',
        'activity_date',
        'class_id',
        'subject_id',
        'education_node_id',
        'grading_period',
        'recorded_by',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'activity_date' => 'date',
        'grading_period' => 'integer',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(GradeComponent::class, 'grade_component_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(GradeActivityScore::class);
    }
}
