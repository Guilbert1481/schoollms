<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    /* =====================
       TABLE CONFIG
    ===================== */

    protected $table = 'questions';

    // If your table has `id`, you can omit this.
    // Kept explicit for clarity.
    protected $primaryKey = 'id';

    public $incrementing = true;
    protected $keyType = 'int';

    /* =====================
       DEFAULTS
    ===================== */

    protected static function booted()
    {
        static::creating(function ($question) {
            if (empty($question->school_id)) {
                $question->school_id = 1; // TEMP DEFAULT
            }
        });
    }

    /* =====================
       MASS ASSIGNMENT
    ===================== */

    protected $fillable = [
        'school_id',
        'teacher_id',
        'subject_id',
        'topic_id',
        'lesson_id',
        'academic_level_id',
        'competency_id',
        'question_type',
        'question_text',
        'difficulty',
        'keyword',
        'question',
        'explanation',
        
        
    ];



    /* ❌ REMOVE THIS — choices are NOT JSON */
    // protected $casts = [
    //     'choices' => 'array',
    // ];

    /* =====================
       RELATIONSHIPS
    ===================== */


    public function choices()
    {
        return $this->hasMany(Choice::class);
    }

    public function competency()
    {
        return $this->belongsTo(Competency::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
