<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'school_id',
        'subject_id',
        'topic_id',
        'name',
        'description',
        'created_by',
    ];

    /**
     * Get the competencies for the lesson.
     */
    public function competencies()
    {
        return $this->hasMany(Competency::class);
    }

    /**
     * Get the topic that owns the lesson.
     */
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get the subject that owns the lesson.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the school that owns the lesson.
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    // app/Models/Lesson.php

    public function questions()
    {
        return $this->hasMany(\App\Models\Question::class);
    }
}
