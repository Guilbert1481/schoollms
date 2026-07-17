<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One student's submission to a homework — their text and/or uploaded file, plus
 * the teacher's score and feedback. The file lives on the private disk and is
 * served only through an authorized download route.
 */
class HomeworkSubmission extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'homework_id',
        'student_id',
        'body',
        'file_path',
        'file_name',
        'submitted_at',
        'score',
        'feedback',
        'graded_at',
        'graded_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'score' => 'decimal:2',
        'graded_at' => 'datetime',
    ];

    public function homework(): BelongsTo
    {
        return $this->belongsTo(Homework::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
