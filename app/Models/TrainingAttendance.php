<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingAttendance extends Model
{
    protected $fillable = [
        'training_enrollment_id',
        'attendance_date',
        'status'
    ];

    public function enrollment()
    {
        return $this->belongsTo(TrainingEnrollment::class, 'training_enrollment_id');
    }
}