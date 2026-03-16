<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'campus_id',
        'name',
        'code',
        'capacity',
        'status',
        'type',
        'active',
        'requires_admission_test',
        'admission_passing_score',
        'admission_max_attempts',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function college()
    {
        return $this->belongsTo(\App\Models\College::class);
    }

    public function students()
    {
        return $this->hasMany(\App\Models\Student::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(\App\Models\Subject::class);
    }

    public function programHead()
    {
        return $this->belongsTo(User::class, 'program_head_id');
    }


}
