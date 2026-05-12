<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    // TEMPORARY: default school while building the system
    protected static function booted()
    {
        static::creating(function ($subject) {
            if (empty($subject->school_id)) {
                $subject->school_id = 1; // TEMP DEFAULT SCHOOL ID
            }
        });
    }

    // Prepare for multi-school ownership (no enforcement yet)
    protected $fillable = [
        'name',
        'school_id',
        'academic_level_id',
        'code',
        'is_active',
        'description',
        'scope',
        'category',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* =====================
       Relationships
    ===================== */

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function curriculums()
    {
        return $this->belongsToMany(Curriculum::class, 'curriculum_subjects')
                    ->withPivot([
                        'year_level',
                        'semester',
                        'is_core',
                        'is_elective',
                        'units'
                    ])
                    ->withTimestamps();
    }

    public function prerequisites()
    {
        return $this->belongsToMany(
            Subject::class,
            'subject_prerequisites',
            'subject_id',
            'prerequisite_subject_id'
        )
        ->withPivot(['minimum_grade', 'is_strict'])
        ->withTimestamps();
    }

    public function requiredFor()
    {
        return $this->belongsToMany(
            Subject::class,
            'subject_prerequisites',
            'prerequisite_subject_id',
            'subject_id'
        )
        ->withPivot(['minimum_grade', 'is_strict'])
        ->withTimestamps();
    }


    public function qualifiedTeachers()
    {
        return $this->belongsToMany(
            TeacherProfile::class,
            'teacher_subjects',
            'subject_id',
            'teacher_id'
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


    // Subject -> Topics -> Lessons -> Competencies
    public function competencies()
    {
        // We reach competencies by going through the lessons relationship
        return $this->hasManyThrough(Competency::class, Topic::class)
                    ->join('lessons', 'lessons.topic_id', '=', 'topics.id')
                    ->join('competencies', 'competencies.lesson_id', '=', 'lessons.id');
    }

    // Subject -> Topics -> Lessons
    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, Topic::class);
    }


    

}
