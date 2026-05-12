<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonResource extends Model
{
    protected $fillable = [
        'school_id',
        'program_id',
        'subject_id',
        'topic_id',
        'lesson_id',
        'competency_id',
        'file_path',
        'file_type',
        'original_filename',
        'file_size',
        'created_by',
    ];

    public function program()    { return $this->belongsTo(Program::class); }
    public function subject()    { return $this->belongsTo(Subject::class); }
    public function topic()      { return $this->belongsTo(Topic::class); }
    public function lesson()     { return $this->belongsTo(Lesson::class); }
    public function competency() { return $this->belongsTo(Competency::class); }
}
