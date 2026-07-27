<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One student's raw score for one {@see GradeActivity}. A null score means the
 * student did not take the item (left blank in the entry modal) — it is excluded
 * from the component aggregate rather than counted as a zero.
 */
class GradeActivityScore extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'grade_activity_id',
        'student_id',
        'raw_score',
    ];

    protected $casts = [
        'raw_score' => 'decimal:2',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(GradeActivity::class, 'grade_activity_id');
    }
}
