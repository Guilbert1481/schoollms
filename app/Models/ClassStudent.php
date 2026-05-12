<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassStudent extends Model
{
    protected $table = 'class_student';

    protected $fillable = [
        'class_id',
        'student_id',
        'enrollment_id',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id');
    }
}
