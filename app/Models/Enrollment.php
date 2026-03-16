<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'program_id',
        'academic_term_id',
        'campus_id',
        'enrollment_type',
        'enrollment_status',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected $dates = [
        'approved_at',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function logs()
    {
        return $this->hasMany(EnrollmentLog::class);
    }

    public function documents()
    {
        return $this->hasMany(EnrollmentDocument::class);
    }
}

