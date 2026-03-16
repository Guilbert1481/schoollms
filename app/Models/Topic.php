<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $table = 'topics';

    protected $fillable = [
        'school_id',
        'subject_id',
        'name',
        'competency',
    ];

    /* =====================
       Relationships
    ===================== */

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function tests()
    {
        return $this->hasMany(Test::class, 'topic_id');
    }

    protected static function booted()
    {
        static::creating(function ($topic) {
            if (auth()->check() && empty($topic->school_id)) {
                $topic->school_id = auth()->user()->school_id;
            }
        });
    }


    public function questions()
    {
        return $this->hasMany(\App\Models\Question::class);
    }
}
