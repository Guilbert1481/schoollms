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
        'code',
        'name',
        'description',
        'bloom_level',
        'sequence',
        'is_active',
        'created_by',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function resources()
    {
        return $this->hasMany(LessonResource::class);
    }
}
