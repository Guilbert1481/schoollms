<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnrollmentDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'enrollment_id',
        'document_type',
        'file_path',
        'status',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id');
    }
}

