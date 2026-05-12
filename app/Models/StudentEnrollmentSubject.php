<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pivot row representing a single subject (class) inside a student's
 * enrolment for a given term.
 */
class StudentEnrollmentSubject extends Model
{
    use HasFactory;

    protected $table = 'student_enrollment_subjects';

    protected $fillable = [
        'student_enrollment_id',
        'class_id',
        'subject_id',
        'grade',
        'status',
    ];

    protected $casts = [
        'grade' => 'decimal:2',
    ];

    public const STATUS_ENROLLED  = 'enrolled';
    public const STATUS_DROPPED   = 'dropped';
    public const STATUS_COMPLETED = 'completed';

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
