<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingSession extends Model
{
    protected $fillable = [
        'training_course_id',
        'session_name',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'meeting_link',
        'max_slots',
        'status'
    ];

    public function course()
    {
        return $this->belongsTo(TrainingCourse::class, 'training_course_id');
    }

    public function enrollments()
    {
        return $this->hasMany(TrainingEnrollment::class);
    }
}