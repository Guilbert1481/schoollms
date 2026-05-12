<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trainee extends Model
{
    protected $fillable = [
        'profile_id',
        'trainee_number',
        'company',
        'position',
        'status'
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function enrollments()
    {
        return $this->hasMany(TrainingEnrollment::class);
    }
}