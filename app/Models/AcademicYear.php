<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademicTerm extends Model
{
    

    protected $fillable = [
        'school_id',
        'name',
        'academic_year',
        'start_date',
        'end_date',
        'enrollment_open_date',
        'enrollment_close_date',
        'status',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'enrollment_open_date',
        'enrollment_close_date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}

