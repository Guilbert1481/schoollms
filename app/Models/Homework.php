<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A homework task tied to a class (subject × section × teacher). See the
 * migration. Students submit against it; the teacher scores each submission.
 */
class Homework extends Model
{
    use BelongsToSchool;

    protected $table = 'homework';

    protected $fillable = [
        'school_id',
        'class_id',
        'title',
        'instructions',
        'points',
        'due_at',
        'grading_period',
        'grade_component_id',
        'is_published',
        'created_by',
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'due_at' => 'datetime',
        'grading_period' => 'integer',
        'is_published' => 'boolean',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}
