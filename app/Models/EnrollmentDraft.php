<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnrollmentDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'term_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
