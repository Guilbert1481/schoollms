<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One weighted component of a level's grade (e.g. Written Work 30%). Belongs to
 * a GradingSetting; the attendance share is held separately on the setting, so
 * these are the manually-scored components only.
 */
class GradeComponent extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'grading_setting_id',
        'name',
        'weight',
        'sort_order',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function gradingSetting(): BelongsTo
    {
        return $this->belongsTo(GradingSetting::class);
    }
}
