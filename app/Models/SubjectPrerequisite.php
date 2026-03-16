<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectPrerequisite extends Model
{
    protected $fillable = [
        'subject_id',
        'prerequisite_subject_id',
        'minimum_grade',
        'is_strict'
    ];

    /**
     * The main subject that has a prerequisite.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * The subject that must be taken first.
     */
    public function prerequisite(): BelongsTo
    {
        // We specify 'prerequisite_subject_id' because it doesn't follow 
        // the default naming convention (prerequisite_id).
        return $this->belongsTo(Subject::class, 'prerequisite_subject_id');
    }
}