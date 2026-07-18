<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

class Test extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'class_id',
        'subject_id',
        'topic_id',
        'lesson_id',
        'competency_id',
        'title',
        'description',
        'status',
        'academic_levels',
        'assessment_type',
        'difficulty',
        'builder_rules',
        'test_date',
        'type',
        'academic_owner_type',
        'academic_owner_id',
        'section_id',
        'print_seed',
        'grade_component_id',
        'grading_period',
    ];

    protected $casts = [
        'difficulty' => 'array',
        'academic_levels' => 'array',
        'print_seed' => 'integer',
        'grade_component_id' => 'integer',
        'grading_period' => 'integer',
    ];

    public function testSources()
    {
        return $this->hasMany(TestSource::class);
    }

    public function testQuestions()
    {
        return $this->hasMany(TestQuestion::class);
    }

    public function questions()
    {
        return $this->hasManyThrough(
            Question::class,
            TestQuestion::class,
            'test_id',
            'id',
            'id',
            'question_id'
        );
    }


    public function testSettings()
    {
        return $this->hasOne(TestSetting::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function competency()
    {
        return $this->belongsTo(Competency::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function settings()
    {
        return $this->hasOne(TestSetting::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(\App\Models\Section::class, 'section_id');
    }




}