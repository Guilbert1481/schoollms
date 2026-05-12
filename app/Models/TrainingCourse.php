<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingCourse extends Model
{
    protected $fillable = [
        'course_code',
        'course_name',
        'course_type',
        'description',
        'fee',
        'duration_hours',
        'delivery_mode',
        'status'
    ];

    public function sessions()
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function materials()
    {
        return $this->hasMany(TrainingMaterial::class);
    }

    public function trainingType()
{
    return $this->belongsTo(TrainingType::class);
}
}