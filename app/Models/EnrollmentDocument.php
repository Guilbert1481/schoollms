<?php

namespace App\Models;

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

    protected $dates = [
        'uploaded_at',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}

