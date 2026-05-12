<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingCertificate extends Model
{
    protected $fillable = [
        'training_enrollment_id',
        'training_type_name',
        'course_name',
        'certificate_number',
        'date_issued',
        'file_path'
    ];

    public function enrollment()
    {
        return $this->belongsTo(TrainingEnrollment::class, 'training_enrollment_id');
    }
}