<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAcademicBackground extends Model
{
    protected $fillable = [
        'student_id',
        'education_level',
        'education_node_id',
        'school_name',
        'school_address',
        'school_type',
        'last_grade_level',
        'year_started',
        'year_ended',
        'gpa',
        'honors',
        'is_current',
    ];

    protected $casts = [
        'gpa'        => 'decimal:2',
        'is_current' => 'bool',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
