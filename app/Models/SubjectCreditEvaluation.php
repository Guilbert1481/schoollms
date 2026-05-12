<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectCreditEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'subject_id',
        'source_subject_code',
        'source_subject_title',
        'source_school',
        'source_grade',
        'source_units',
        'credited_units',
        'status',
        'reason',
        'remarks',
        'evaluated_by',
        'evaluated_at',
    ];

    protected $casts = [
        'evaluated_at' => 'datetime',
        'source_units' => 'decimal:1',
        'credited_units' => 'decimal:1',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }
}
