<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ClassModel;


class TeacherProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'department',
        'specialization',
        'employment_status',
        'employment_start',
        'employment_end'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'teacher_subjects',
            'teacher_id',
            'subject_id'
        )->withPivot('qualification_level')->withTimestamps();
    }

    public function classes()
    {
        return $this->belongsToMany(
            ClassModel::class,
            'class_teacher',
            'teacher_id',
            'class_id'
        )->withPivot('role')->withTimestamps();
    }



    
}
