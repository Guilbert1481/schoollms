<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;
use App\Models\TeacherProfile;

class ClassModel extends Model
{

    use BelongsToSchool;
    protected $table = 'classes';

    protected $fillable = [
        'school_id',
        'subject_id',
        'teacher_id',
        'semester_id',
        'section_id',
        'code',
        'room',
        'schedule',
        'capacity',
        'is_open',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'class_student')
            ->withPivot(['enrollment_id', 'status'])
            ->withTimestamps();
    }


    public function grades()
    {
        return $this->hasMany(Grade::class, 'class_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(
            TeacherProfile::class,
            'class_teacher',
            'class_id',
            'teacher_id'
        )->withPivot('role')->withTimestamps();
    }

    public function tests()
    {
        return $this->hasMany(Test::class);
    }

}
