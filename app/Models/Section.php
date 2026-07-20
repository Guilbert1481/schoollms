<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use BelongsToSchool;

    protected $table = 'sections';

    protected $fillable = [
        'school_id',
        'program_id',
        'education_node_id',
        'name',
        'code',
        'term_id',
        'academic_year_id',
        'year_level',
        'adviser_id',
        'capacity',
        'is_active',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // School this section belongs to
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    // Program this section belongs to (BSIT, BEED, etc.)
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    // Grade level this section belongs to (basic ed — Grade 1, Grade 7, …)
    public function educationNode(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EducationNode::class, 'education_node_id');
    }

    // Semester this section belongs to (1st Semester, 2nd Semester)
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    // Academic year (2025–2026)
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // Classes under this section
    public function classes()
    {
        return $this->hasMany(ClassModel::class, 'section_id');
    }

    // Students enrolled in this section
    public function students()
    {
        return $this->hasMany(Student::class, 'section_id');
    }

    // Teachers assigned to this section through classes
    public function teachers()
    {
        return $this->hasManyThrough(
            Teacher::class,
            ClassModel::class,
            'section_id',   // Foreign key on classes table
            'id',           // Foreign key on teachers table
            'id',           // Local key on sections table
            'teacher_id'    // Local key on classes table
        );
    }
}
