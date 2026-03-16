<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competency extends Model
{
    protected $fillable = [
        'school_id',
        'subject_id',
        'topic_id',
        'lesson_id',
        'name',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
