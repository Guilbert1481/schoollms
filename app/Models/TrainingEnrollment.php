<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingEnrollment extends Model
{
    protected $fillable = [
        'trainee_id',
        'training_course_id',
        'training_session_id',
        'enrollment_date',
        'status',
        'name',
        'email',
        'type',
        'expires_at',
        'payment_status',
        'payment_reference',
        'payment_paid_at',
    ];

    protected $casts = [
        'payment_paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function trainee()
    {
        return $this->belongsTo(Trainee::class);
    }

    public function session()
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function course()
    {
        return $this->belongsTo(TrainingCourse::class, 'training_course_id');
    }

    public function certificate()
    {
        return $this->hasOne(TrainingCertificate::class);
    }

    public function attendance()
    {
        return $this->hasMany(TrainingAttendance::class);
    }
}